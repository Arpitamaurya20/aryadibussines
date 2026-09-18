<?php
/**
 * IoT Initiative Research - analyzes corporate_tickets SQL dump and generates PPTX.
 * Run: php analyze_and_generate_ppt.php
 */

set_time_limit(0);
ini_set('memory_limit', '512M');

$sqlFile = __DIR__ . '/techxpertindia - 2026-06-07T102959.092.sql';
$outputDir = __DIR__;
$jsonOutput = $outputDir . '/iot_analysis_data.json';
$pptxOutput = $outputDir . '/TechXpert_IoT_Facilities_Management_Initiative.pptx';

if (!file_exists($sqlFile)) {
    fwrite(STDERR, "SQL file not found: $sqlFile\n");
    exit(1);
}

function unquoteSqlValue(string $v): string
{
    $v = trim($v);
    if ($v === 'NULL') {
        return '';
    }
    if (strlen($v) >= 2 && $v[0] === "'" && substr($v, -1) === "'") {
        $inner = substr($v, 1, -1);
        return str_replace(["\\'", '\\"', '\\r', '\\n', '\\\\'], ["'", '"', "\r", "\n", '\\'], $inner);
    }
    return $v;
}

function parseSqlTuple(string $tuple): array
{
    $tuple = trim($tuple);
    if ($tuple[0] === '(') {
        $tuple = substr($tuple, 1);
    }
    if (substr($tuple, -1) === ')') {
        $tuple = substr($tuple, 0, -1);
    }

    $values = [];
    $current = '';
    $inString = false;
    $len = strlen($tuple);

    for ($i = 0; $i < $len; $i++) {
        $c = $tuple[$i];
        if ($inString) {
            $current .= $c;
            if ($c === "'" && ($i + 1 >= $len || $tuple[$i + 1] !== "'")) {
                $inString = false;
            } elseif ($c === "'" && $i + 1 < $len && $tuple[$i + 1] === "'") {
                $current .= $tuple[++$i];
            }
            continue;
        }
        if ($c === "'") {
            $inString = true;
            $current .= $c;
            continue;
        }
        if ($c === ',') {
            $values[] = trim($current);
            $current = '';
            continue;
        }
        $current .= $c;
    }
    if ($current !== '') {
        $values[] = trim($current);
    }

    return array_map('unquoteSqlValue', $values);
}

function increment(array &$map, string $key, int $by = 1): void
{
    $key = trim($key);
    if ($key === '') {
        $key = '(Blank)';
    }
    if (!isset($map[$key])) {
        $map[$key] = 0;
    }
    $map[$key] += $by;
}

function topN(array $map, int $n = 15): array
{
    arsort($map);
    return array_slice($map, 0, $n, true);
}

function pct(int $part, int $total): string
{
    if ($total <= 0) {
        return '0%';
    }
    return round(($part / $total) * 100, 1) . '%';
}

function classifyIndustry(string $name): string
{
    $n = strtolower($name);
    $rules = [
        'Banking & Financial Services' => ['bank', 'hdfc', 'hsbc', 'mufg', 'finance', 'stock exchange', 'nse', 'globeop', 'dmi finance', 'tvs credit', 'pramerica', 'black rock', 'blackrock'],
        'IT & Technology' => ['infosys', 'tech', 'software', 'unacademy', 'expedia', 'f1 info', 'gebbs', 'smartworks', 'tablespace', 'skootr', 'airtel', 'bharti', 'tata comm'],
        'Real Estate & FM' => ['cbre', 'jll', 'jones lang', 'cushman', 'wakefield', 'efs', 'iss facility', 'rmz', 'dlf', 'prestige', 'smartwork'],
        'Telecom & Media' => ['vodafone', 'idea', 'urban company'],
        'Manufacturing & Automotive' => ['maruti', 'godrej', 'trident', 'alco'],
        'Food & Retail' => ['jubilant', 'compass', 'tablez', 'lulu', 'super market', 'food'],
        'Healthcare & Pharma' => ['piramal', 'alcon', 'healthcare', 'pharma'],
        'Consulting & Professional Services' => ['ernst', 'young', 'ey ', 'heidrick', 'struggles'],
        'Insurance' => ['insurance', 'pramerica'],
        'Government & Embassy' => ['embassy', 'italian embassy'],
    ];
    foreach ($rules as $vertical => $keywords) {
        foreach ($keywords as $kw) {
            if (strpos($n, $kw) !== false) {
                return $vertical;
            }
        }
    }
    return 'Corporate / Other';
}

function extractIssueKeywords(string $text): array
{
    $text = strtolower($text);
    $patterns = [
        'AC / Cooling Failure' => ['ac not', 'a/c', 'air condition', 'cooling', 'hvac', 'chiller', 'fcu', 'ahu', 'compressor', 'gas leak', 'not cooling', 'temperature'],
        'Electrical / Power' => ['power', 'electric', 'mcb', 'tripping', 'short circuit', 'voltage', 'light not', 'lights not', 'db ', 'panel', 'wiring', 'socket', 'switch', 'phase'],
        'Plumbing / Leakage' => ['leak', 'leakage', 'pipe', 'water', 'drain', 'seepage', 'tap', 'flush', 'toilet', 'bathroom', 'plumb'],
        'Fire & Safety' => ['fire', 'extinguisher', 'smoke', 'alarm', 'sprinkler', 'emergency exit'],
        'UPS / Power Backup' => ['ups', 'battery', 'inverter', 'backup power', 'dg ', 'generator'],
        'CCTV / Security' => ['cctv', 'camera', 'dvr', 'nvr', 'access control', 'biometric'],
        'Pest / Hygiene' => ['pest', 'rat', 'cockroach', 'smell', 'dead', 'fumigation'],
        'Civil / Carpentry' => ['door', 'lock', 'chair', 'table', 'carpet', 'tile', 'wall', 'ceiling', 'paint', 'glass', 'carpent'],
        'Lift / Elevator' => ['lift', 'elevator', 'escalator'],
        'Network / IT Infra' => ['network', 'lan', 'wifi', 'router', 'server', 'rack'],
    ];

    $matched = [];
    foreach ($patterns as $category => $kws) {
        foreach ($kws as $kw) {
            if (strpos($text, $kw) !== false) {
                $matched[$category] = true;
                break;
            }
        }
    }
    return array_keys($matched);
}

function mapIotSolution(string $service, string $subservice, string $issueCategory): array
{
    $s = strtolower($service . ' ' . $subservice . ' ' . $issueCategory);

    $solutions = [];

    if (preg_match('/hvac|ac |cooling|chiller|fcu|ahu|temperature/', $s)) {
        $solutions[] = [
            'technology' => 'Smart HVAC IoT Sensors + BMS Gateway',
            'sensors' => 'Supply/Return temp, humidity, CO2, filter pressure, compressor runtime',
            'platform' => 'MQTT/Modbus to TechXpert ERP; predictive PM alerts',
            'benefit' => '30-40% reduction in breakdown tickets; auto ticket before user complaint',
        ];
    }
    if (preg_match('/electr|power|light|mcb|voltage|panel|phase/', $s)) {
        $solutions[] = [
            'technology' => 'Smart Energy & Power Quality Monitoring',
            'sensors' => 'Smart MCB/energy meters, voltage/current sensors, circuit branch monitoring',
            'platform' => 'Real-time load dashboard; trip/root-cause alerts to ERP ticket',
            'benefit' => 'Prevent outages; optimize energy; reduce electrician reactive calls',
        ];
    }
    if (preg_match('/plumb|leak|water|pipe|drain|seepage/', $s)) {
        $solutions[] = [
            'technology' => 'Water Leak & Flow IoT System',
            'sensors' => 'Acoustic/leak rope sensors, flow meters, water level, pressure transducers',
            'platform' => 'Zone-based alerts mapped to branch/floor in ERP',
            'benefit' => 'Early leak detection avoids ceiling damage & multi-floor incidents',
        ];
    }
    if (preg_match('/fire|extinguish|smoke|sprinkler|alarm/', $s)) {
        $solutions[] = [
            'technology' => 'Connected Fire & Life Safety Monitoring',
            'sensors' => 'Smart smoke/heat, panel health, extinguisher pressure tags (NFC/LoRa)',
            'platform' => 'Compliance dashboard + auto PPM ticket scheduling',
            'benefit' => 'Audit-ready logs; reduced compliance failures',
        ];
    }
    if (preg_match('/ups|battery|inverter|generator|dg /', $s)) {
        $solutions[] = [
            'technology' => 'UPS/DG Remote Monitoring',
            'sensors' => 'Battery health, load %, fuel level, runtime, temperature',
            'platform' => 'Critical alert escalation in ERP with SLA',
            'benefit' => 'Avoid downtime in banking/IT clients',
        ];
    }
    if (preg_match('/cctv|camera|dvr|access|biometric|security/', $s)) {
        $solutions[] = [
            'technology' => 'Smart Security & Video Analytics',
            'sensors' => 'Camera health ping, edge AI analytics, door access logs',
            'platform' => 'Unified FM dashboard; auto ticket on device offline',
            'benefit' => 'Proactive NVR/camera uptime monitoring',
        ];
    }
    if (preg_match('/lift|elevator|escalator/', $s)) {
        $solutions[] = [
            'technology' => 'Elevator IoT Condition Monitoring',
            'sensors' => 'Vibration, door cycle count, ride quality, fault code API',
            'platform' => 'OEM API + ERP integration for predictive maintenance',
            'benefit' => 'Reduce trapped-passenger incidents',
        ];
    }
    if (preg_match('/pest|hygiene|smell/', $s)) {
        $solutions[] = [
            'technology' => 'Environmental & Occupancy IoT',
            'sensors' => 'Air quality (VOC/CO2), occupancy, smart trap monitoring',
            'platform' => 'Scheduled service triggers based on usage/IAQ thresholds',
            'benefit' => 'Data-driven housekeeping & pest control routing',
        ];
    }

    if (empty($solutions)) {
        $solutions[] = [
            'technology' => 'Generic Asset Health Tag (RFID/NFC/QR + Sensor)',
            'sensors' => 'Asset ID, last service, optional temp/vibration tag',
            'platform' => 'TechXpert asset registry with IoT telemetry overlay',
            'benefit' => 'Foundation for phased IoT rollout per branch',
        ];
    }

    return $solutions;
}

echo "Loading SQL dump...\n";

$corporates = [];
$serviceCounts = [];
$subserviceCounts = [];
$serviceSubCounts = [];
$typeCounts = [];
$corporateCounts = [];
$industryCounts = [];
$issueKeywordCounts = [];
$serviceIssueCounts = [];
$sampleMessages = [];
$totalTickets = 0;

$mode = null;
$handle = fopen($sqlFile, 'r');
if (!$handle) {
    fwrite(STDERR, "Cannot open SQL file\n");
    exit(1);
}

$lineNum = 0;
while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if ($lineNum % 250000 === 0) {
        echo "  Processed $lineNum lines, tickets: $totalTickets\n";
    }

    if (strpos($line, 'INSERT INTO `corporate`') !== false) {
        $mode = 'corporate';
        continue;
    }
    if (strpos($line, 'CREATE TABLE') !== false && strpos($line, '`corporate`') === false && $mode === 'corporate') {
        $mode = null;
    }
    if (strpos($line, 'INSERT INTO `corporate_tickets`') !== false) {
        $mode = 'tickets';
    }
    if (strpos($line, 'CREATE TABLE') !== false && strpos($line, '`corporate_tickets`') === false && $mode === 'tickets') {
        $mode = null;
    }

    if ($mode === 'corporate' && preg_match_all('/\((\d+),\s*\'((?:[^\'\\\\]|\\\\.|\'\')*)\'/', $line, $m, PREG_SET_ORDER)) {
        foreach ($m as $match) {
            $corporates[(int) $match[1]] = str_replace("\\'", "'", $match[2]);
        }
        continue;
    }

    if ($mode !== 'tickets') {
        continue;
    }

    if (strpos($line, '(') === false) {
        continue;
    }

    preg_match_all('/\([^;]*\)/', $line, $tuples);
    foreach ($tuples[0] as $tuple) {
        if (strlen($tuple) < 40) {
            continue;
        }
        $cols = parseSqlTuple($tuple);
        if (count($cols) < 17) {
            continue;
        }

        $corporateId = (int) $cols[2];
        $type = $cols[4];
        $service = $cols[12];
        $subservice = $cols[13];
        $message = $cols[16];
        $status = $cols[35] ?? '';

        $totalTickets++;
        increment($serviceCounts, $service);
        increment($subserviceCounts, $subservice);
        increment($typeCounts, $type);
        increment($corporateCounts, (string) $corporateId);

        $ssKey = $service . ' → ' . $subservice;
        increment($serviceSubCounts, $ssKey);

        $corpName = $corporates[$corporateId] ?? ('Corporate #' . $corporateId);
        $industry = classifyIndustry($corpName);
        increment($industryCounts, $industry);

        $categories = extractIssueKeywords($message . ' ' . $subservice . ' ' . $service);
        if (empty($categories)) {
            increment($issueKeywordCounts, 'General / Other');
            $categories = ['General / Other'];
        }
        foreach ($categories as $cat) {
            increment($issueKeywordCounts, $cat);
            increment($serviceIssueCounts, $service . ' | ' . $cat);
        }

        if (count($sampleMessages) < 500) {
            $sampleMessages[] = [
                'service' => $service,
                'subservice' => $subservice,
                'message' => mb_substr(trim($message), 0, 180),
                'corporate' => $corpName,
                'categories' => $categories,
            ];
        }
    }
}
fclose($handle);

echo "Total tickets analyzed: $totalTickets\n";

$topServices = topN($serviceCounts, 12);
$topSubservices = topN($subserviceCounts, 15);
$topServiceSub = topN($serviceSubCounts, 15);
$topIndustries = topN($industryCounts, 10);
$topIssues = topN($issueKeywordCounts, 12);

$topCorporateDetailed = [];
foreach (topN($corporateCounts, 10) as $cid => $count) {
    $name = $corporates[(int) $cid] ?? ('Corporate #' . $cid);
    $topCorporateDetailed[] = [
        'id' => (int) $cid,
        'name' => $name,
        'tickets' => $count,
        'industry' => classifyIndustry($name),
        'share' => pct($count, $totalTickets),
    ];
}

$iotRoadmap = [];
$priorityServices = array_slice(array_keys($topServices), 0, 5);
foreach ($priorityServices as $svc) {
    $relatedIssues = [];
    foreach ($topIssues as $issue => $cnt) {
        foreach (mapIotSolution($svc, '', $issue) as $sol) {
            $relatedIssues[$issue] = $sol;
        }
    }
    $iotRoadmap[] = [
        'service_vertical' => $svc,
        'ticket_count' => $topServices[$svc],
        'share' => pct($topServices[$svc], $totalTickets),
        'iot_solutions' => mapIotSolution($svc, '', implode(' ', array_keys($topIssues))),
    ];
}

$focusVertical = array_key_first($topServices);
$focusCount = $topServices[$focusVertical];

$analysis = [
    'generated_at' => date('Y-m-d H:i:s'),
    'total_tickets' => $totalTickets,
    'top_services' => $topServices,
    'top_subservices' => $topSubservices,
    'top_service_subservice' => $topServiceSub,
    'top_industries' => $topIndustries,
    'top_issue_categories' => $topIssues,
    'top_corporates' => $topCorporateDetailed,
    'focus_fm_vertical' => [
        'name' => $focusVertical,
        'tickets' => $focusCount,
        'share' => pct($focusCount, $totalTickets),
    ],
    'iot_roadmap' => $iotRoadmap,
    'sample_messages' => array_slice($sampleMessages, 0, 20),
];

file_put_contents($jsonOutput, json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Analysis JSON saved: $jsonOutput\n";

// --- PPTX Generation (Office Open XML) ---
require_once __DIR__ . '/pptx_builder.php';

buildIotPresentation($analysis, $pptxOutput);
echo "Presentation saved: $pptxOutput\n";
