<?php

function xmlEsc(string $s): string
{
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function buildIotPresentation(array $analysis, string $outputPath): void
{
    $slides = [];

    $slides[] = titleSlide(
        'IoT Initiative for TechXpert FM Portal',
        'Data-Driven Facilities Management Transformation',
        'Tech Meeting | ' . date('F j, Y') . "\nBased on " . number_format($analysis['total_tickets']) . ' corporate service tickets'
    );

    $slides[] = bulletSlide('Executive Summary', [
        'Analyzed ' . number_format($analysis['total_tickets']) . ' tickets from TechXpert ERP (corporate_tickets table)',
        'Highest ticket volume: Carpentry (19.9%) - mostly manual FM, limited IoT fit',
        'IoT priority vertical: Electrical + Electrician (~23% tickets, 16,336 power/light issues in messages)',
        'Top client segment: Urban Company (49.7% tickets) + JLL/CBRE FM accounts',
        'Recommendation: Phased IoT for HVAC, Electrical, Plumbing integrated with ERP ticketing',
        'Goal: Predictive maintenance - sensor alert creates ticket before user complaint',
    ]);

    $svcLines = ['FM Service Vertical - Ticket Volume Rankings:'];
    $i = 1;
    foreach ($analysis['top_services'] as $name => $count) {
        $svcLines[] = $i . '. ' . $name . ' - ' . number_format($count) . ' (' . pctVal($count, $analysis['total_tickets']) . ')';
        $i++;
        if ($i > 10) {
            break;
        }
    }
    $slides[] = bulletSlide('Which Vertical Generates Most Issues?', $svcLines);

    $subLines = ['Top Sub-Services (Root Work Categories):'];
    $i = 1;
    foreach ($analysis['top_subservices'] as $name => $count) {
        $subLines[] = $i . '. ' . $name . ' - ' . number_format($count);
        $i++;
        if ($i > 10) {
            break;
        }
    }
    $slides[] = bulletSlide('Top Issue Types (Subservice Analysis)', $subLines);

    $issueLines = ['Issue themes extracted from ticket Message field:'];
    $i = 1;
    foreach ($analysis['top_issue_categories'] as $name => $count) {
        $issueLines[] = $i . '. ' . $name . ' - ' . number_format($count) . ' ticket hits';
        $i++;
        if ($i > 8) {
            break;
        }
    }
    $slides[] = bulletSlide('Message-Based Issue Pattern Analysis', $issueLines);

    $indLines = ['Client Industry Segments (by corporate account):'];
    $i = 1;
    foreach ($analysis['top_industries'] as $name => $count) {
        $indLines[] = $i . '. ' . $name . ' - ' . number_format($count) . ' (' . pctVal($count, $analysis['total_tickets']) . ')';
        $i++;
    }
    $slides[] = bulletSlide('Industry Vertical Distribution', $indLines);

    $corpLines = ['Highest ticket-generating client accounts:'];
    foreach ($analysis['top_corporates'] as $c) {
        $corpLines[] = '- ' . $c['name'] . ' [' . $c['industry'] . '] - ' . number_format($c['tickets']) . ' (' . $c['share'] . ')';
    }
    $slides[] = bulletSlide('Top Corporate Clients Driving Volume', $corpLines);

    $focus = 'Electrical / MEP';
    $slides[] = bulletSlide('IoT Focus Vertical: ' . $focus, [
        'Electrical service: 9,370 tickets (16%) + Electrician: 4,001 (6.8%) = ~13,371 tickets',
        'Message analysis: 16,336 Electrical/Power issues - lights, MCB trip, socket, wiring',
        'Top sub-issues: Celling Lights Not Working (1,699), Electrician Required (2,619)',
        'Reactive model today: user reports failure -> technician visit -> business downtime',
        'IoT stack: Smart energy meters, branch circuit monitoring, light circuit health sensors',
        'ERP mapping: Service=Electrical, Subservice auto-set, BranchAssetID linked to meter/sensor',
    ]);

    $samples = ['Real ticket messages driving IoT design:'];
    foreach (array_slice($analysis['sample_messages'], 0, 8) as $s) {
        $msg = preg_replace('/\s+/', ' ', $s['message']);
        $samples[] = '- [' . $s['subservice'] . '] ' . mb_substr($msg, 0, 90);
    }
    $slides[] = bulletSlide('Sample Issues from Ticket Messages', $samples);

    foreach (array_slice($analysis['iot_roadmap'], 0, 4) as $road) {
        $lines = [
            'Volume: ' . number_format($road['ticket_count']) . ' tickets (' . $road['share'] . ')',
        ];
        foreach ($road['iot_solutions'] as $idx => $sol) {
            if ($idx >= 2) {
                break;
            }
            $lines[] = '-> ' . $sol['technology'];
            $lines[] = '   Sensors: ' . $sol['sensors'];
            $lines[] = '   ERP: ' . $sol['platform'];
            $lines[] = '   Benefit: ' . $sol['benefit'];
        }
        $slides[] = bulletSlide('IoT Solution: ' . $road['service_vertical'], $lines);
    }

    $slides[] = twoColumnSlide(
        'IoT-to-Issue Mapping Matrix',
        [
            'AC Not Cooling / HVAC',
            '-> Temp/humidity/CO2 sensors + BMS',
            '',
            'Power / Lights / MCB Trip',
            '-> Smart energy meters and branch monitoring',
            '',
            'Water Leak / Seepage',
            '-> Leak rope + flow meters at risers',
            '',
            'Fire / Extinguisher PPM',
            '-> Connected panels + NFC tags',
        ],
        [
            'UPS / Battery / DG',
            '-> Remote battery and load monitoring',
            '',
            'CCTV Offline / DVR',
            '-> Device heartbeat + auto ticket',
            '',
            'Lift / Elevator Fault',
            '-> OEM fault API + vibration IoT',
            '',
            'Pest / IAQ Complaints',
            '-> Occupancy + air quality sensors',
        ]
    );

    $slides[] = bulletSlide('Proposed IoT Architecture for TechXpert ERP', [
        'Layer 1 - Edge: Sensors (LoRaWAN / Wi-Fi / Modbus / BACnet gateways)',
        'Layer 2 - IoT Hub: AWS IoT Core / Azure IoT Hub / on-prem MQTT broker',
        'Layer 3 - Rules Engine: Threshold alerts, anomaly detection, SLA routing',
        'Layer 4 - TechXpert ERP: Auto-create corporate_tickets with Service/Subservice',
        'Layer 5 - Dashboard: Branch asset map, live telemetry, PPM compliance KPIs',
        'Security: TLS, device certificates, role-based access per CorporateID/BranchID',
    ]);

    $slides[] = bulletSlide('Phase 1 Pilot - 90 Days (Recommended)', [
        'Week 1-2: Select 1 banking + 1 IT client site (highest ticket density)',
        'Week 3-4: Deploy HVAC + electrical monitoring on top 20 assets per site',
        'Week 5-8: Integrate MQTT to PHP webhook to auto ticket creation in ERP',
        'Week 9-10: Train FM team; define alert-to-SLA rules and escalation matrix',
        'Week 11-12: Measure KPIs - MTTR, repeat tickets, proactive vs reactive ratio',
        'Success criteria: 25% reduction in critical HVAC/electrical tickets at pilot sites',
    ]);

    $slides[] = bulletSlide('Business Impact and ROI Indicators', [
        'Reduce repeat breakdown tickets through predictive maintenance scheduling',
        'Lower client SLA breaches - especially Banking and IT segments',
        'Technician dispatch optimization using sensor location and severity data',
        'New revenue: IoT-enabled FM contract upsell for CBRE/JLL/EFS-style accounts',
        'Data asset: Historical telemetry improves quotation accuracy',
        'Competitive differentiation vs traditional break-fix FM vendors',
    ]);

    $slides[] = bulletSlide('Action Items for Tech Team', [
        '1. Approve IoT pilot: Electrical + HVAC monitoring at JLL/DMI Finance pilot sites',
        '2. Define ERP webhook for sensor-triggered auto ticket in corporate_tickets',
        '3. Extend BranchAssetID registry with IoT device ID and telemetry endpoint',
        '4. Build FM IoT dashboard (branch map + live alerts + ticket deep-link)',
        '5. Evaluate partners: Schneider/Siemens BMS, LoRaWAN gateways, smart energy meters',
        '6. Phase 2: Plumbing leak sensors + UPS monitoring for banking SLA clients',
    ]);

    $slides[] = titleSlide(
        'Thank You',
        'Questions and Discussion',
        'TechXpert Facilities India Pvt. Ltd.' . "\nIoT Initiative - Facilities Management Portal"
    );

    writePptx($slides, $outputPath);
}

function pctVal(int $part, int $total): string
{
    if ($total <= 0) {
        return '0%';
    }
    return round(($part / $total) * 100, 1) . '%';
}

function titleSlide(string $title, string $subtitle, string $footer): array
{
    return ['type' => 'title', 'title' => $title, 'subtitle' => $subtitle, 'footer' => $footer];
}

function bulletSlide(string $title, array $bullets): array
{
    return ['type' => 'bullet', 'title' => $title, 'bullets' => $bullets];
}

function twoColumnSlide(string $title, array $left, array $right): array
{
    return ['type' => 'two_col', 'title' => $title, 'left' => $left, 'right' => $right];
}

function ensureDir(string $path): void
{
    if (is_dir($path)) {
        return;
    }
    ensureDir(dirname($path));
    mkdir($path);
}

function writePptx(array $slides, string $outputPath): void
{
    $tmp = __DIR__ . '/_pptx_build_' . uniqid();
    ensureDir("$tmp/_rels");
    ensureDir("$tmp/docProps");
    ensureDir("$tmp/ppt/slides/_rels");
    ensureDir("$tmp/ppt/_rels");
    ensureDir("$tmp/ppt/theme");
    ensureDir("$tmp/ppt/media");

    $slideTargets = [];
    $slideCount = count($slides);

    for ($i = 0; $i < $slideCount; $i++) {
        $num = $i + 1;
        $slideXml = renderSlideXml($slides[$i]);
        file_put_contents("$tmp/ppt/slides/slide{$num}.xml", $slideXml);
        file_put_contents(
            "$tmp/ppt/slides/_rels/slide{$num}.xml.rels",
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>' .
            '</Relationships>'
        );
        $slideTargets[] = '<Relationship Id="rId' . ($i + 2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide' . $num . '.xml"/>';
    }

    ensureDir("$tmp/ppt/slideLayouts/_rels");
    file_put_contents("$tmp/ppt/slideLayouts/slideLayout1.xml", blankLayoutXml());
    file_put_contents(
        "$tmp/ppt/slideLayouts/_rels/slideLayout1.xml.rels",
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="../slideMasters/slideMaster1.xml"/>' .
        '</Relationships>'
    );

    ensureDir("$tmp/ppt/slideMasters/_rels");
    file_put_contents("$tmp/ppt/slideMasters/slideMaster1.xml", slideMasterXml());
    file_put_contents(
        "$tmp/ppt/slideMasters/_rels/slideMaster1.xml.rels",
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>' .
        '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="../theme/theme1.xml"/>' .
        '</Relationships>'
    );

    file_put_contents("$tmp/ppt/theme/theme1.xml", themeXml());

    $sldIds = '';
    for ($i = 0; $i < $slideCount; $i++) {
        $id = 256 + $i;
        $sldIds .= '<p:sldId id="' . $id . '" r:id="rId' . ($i + 2) . '"/>';
    }

    file_put_contents(
        "$tmp/ppt/presentation.xml",
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<p:presentation xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">' .
        '<p:sldMasterIdLst><p:sldMasterId id="2147483648" r:id="rId1"/></p:sldMasterIdLst>' .
        '<p:sldIdLst>' . $sldIds . '</p:sldIdLst>' .
        '<p:sldSz cx="9144000" cy="6858000" type="screen4x3"/>' .
        '<p:notesSz cx="6858000" cy="9144000"/>' .
        '</p:presentation>'
    );

    $presRels = '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="slideMasters/slideMaster1.xml"/>';
    $presRels .= implode('', $slideTargets);
    file_put_contents(
        "$tmp/ppt/_rels/presentation.xml.rels",
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        $presRels .
        '</Relationships>'
    );

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
        '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
        '<Default Extension="xml" ContentType="application/xml"/>' .
        '<Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/>' .
        '<Override PartName="/ppt/slideMasters/slideMaster1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideMaster+xml"/>' .
        '<Override PartName="/ppt/slideLayouts/slideLayout1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/>' .
        '<Override PartName="/ppt/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>' .
        '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>' .
        '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';
    for ($i = 1; $i <= $slideCount; $i++) {
        $contentTypes .= '<Override PartName="/ppt/slides/slide' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>';
    }
    $contentTypes .= '</Types>';
    file_put_contents("$tmp/[Content_Types].xml", $contentTypes);

    file_put_contents(
        "$tmp/_rels/.rels",
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/>' .
        '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>' .
        '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>' .
        '</Relationships>'
    );

    file_put_contents(
        "$tmp/docProps/core.xml",
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
        '<dc:title>TechXpert IoT FM Initiative</dc:title>' .
        '<dc:creator>TechXpert Facilities India</dc:creator>' .
        '<dcterms:created xsi:type="dcterms:W3CDTF">' . date('Y-m-d\TH:i:s\Z') . '</dcterms:created>' .
        '</cp:coreProperties>'
    );

    file_put_contents(
        "$tmp/docProps/app.xml",
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">' .
        '<Application>TechXpert IoT Generator</Application>' .
        '<Slides>' . $slideCount . '</Slides>' .
        '</Properties>'
    );

    if (file_exists($outputPath)) {
        unlink($outputPath);
    }

    $zipOk = false;
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE) === true) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tmp, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($files as $file) {
                $filePath = $file->getRealPath();
                $relative = substr($filePath, strlen($tmp) + 1);
                $relative = str_replace('\\', '/', $relative);
                if ($file->isDir()) {
                    continue;
                }
                $zip->addFile($filePath, $relative);
            }
            $zip->close();
            $zipOk = true;
        }
    }

    if (!$zipOk) {
        $zipPath = $outputPath . '.zip';
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }
        $tmpEsc = str_replace("'", "''", $tmp);
        $zipEsc = str_replace("'", "''", $zipPath);
        $cmd = "powershell -NoProfile -Command \"Compress-Archive -Path '$tmpEsc\\*' -DestinationPath '$zipEsc' -Force\"";
        exec($cmd, $out, $code);
        if ($code !== 0 || !file_exists($zipPath)) {
            deleteDir($tmp);
            throw new RuntimeException('Cannot create PPTX archive');
        }
        rename($zipPath, $outputPath);
    }

    deleteDir($tmp);
}

function deleteDir(string $dir): void
{
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? deleteDir($path) : unlink($path);
    }
    rmdir($dir);
}

function renderSlideXml(array $slide): string
{
    $shapes = '';
    $bg = '<p:bg><p:bgPr><a:solidFill><a:srgbClr val="0B1F3A"/></a:solidFill><a:effectLst/></p:bgPr></p:bg>';

    if ($slide['type'] === 'title') {
        $shapes .= '<p:sp><p:nvSpPr><p:cNvPr id="98" name="Bar"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x="600000" y="1600000"/><a:ext cx="7800000" cy="80000"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:solidFill><a:srgbClr val="00897B"/></a:solidFill><a:ln><a:noFill/></a:ln></p:spPr><p:txBody><a:bodyPr/><a:lstStyle/><a:p><a:endParaRPr lang="en-US"/></a:p></p:txBody></p:sp>';
        $shapes .= textBox($slide['title'], 600000, 1800000, 7800000, 1200000, 4400, 'FFFFFF', true, 'ctr');
        $shapes .= textBox($slide['subtitle'], 900000, 3000000, 7200000, 800000, 2400, '4FC3F7', false, 'ctr');
        $shapes .= textBox($slide['footer'], 600000, 4200000, 7800000, 900000, 1600, 'B0BEC5', false, 'ctr');
    } elseif ($slide['type'] === 'two_col') {
        $shapes .= textBox($slide['title'], 400000, 200000, 8200000, 700000, 3200, 'FFFFFF', true, 'l');
        $shapes .= textBox(implode("\n", $slide['left']), 400000, 1000000, 4200000, 5200000, 1600, 'ECEFF1', false, 'l');
        $shapes .= textBox(implode("\n", $slide['right']), 4700000, 1000000, 4200000, 5200000, 1600, 'ECEFF1', false, 'l');
    } else {
        $shapes .= textBox($slide['title'], 400000, 200000, 8200000, 700000, 3200, '4FC3F7', true, 'l');
        $shapes .= textBox(implode("\n", $slide['bullets']), 500000, 1000000, 8100000, 5400000, 1700, 'FFFFFF', false, 'l');
    }

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">' .
        $bg .
        '<p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>' .
        $shapes .
        '</p:spTree></p:sld>';
}

function textBox(string $text, int $x, int $y, int $cx, int $cy, int $fontSize, string $color, bool $bold, string $align): string
{
    static $id = 2;
    $id++;
    $lines = explode("\n", $text);
    $paragraphs = '';
    foreach ($lines as $line) {
        $line = xmlEsc($line);
        $bTag = $bold ? '<a:b/>' : '';
        $paragraphs .= '<a:p><a:pPr algn="' . $align . '"/><a:r><a:rPr lang="en-US" sz="' . $fontSize . '" dirty="0">' . $bTag . '<a:solidFill><a:srgbClr val="' . $color . '"/></a:solidFill><a:latin typeface="Segoe UI"/></a:rPr><a:t>' . $line . '</a:t></a:r><a:endParaRPr lang="en-US" sz="' . $fontSize . '"/></a:p>';
    }

    return '<p:sp><p:nvSpPr><p:cNvPr id="' . $id . '" name="TextBox ' . $id . '"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr>' .
        '<p:spPr><a:xfrm><a:off x="' . $x . '" y="' . $y . '"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>' .
        '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></p:spPr>' .
        '<p:txBody><a:bodyPr wrap="square" rtlCol="0"><a:spAutoFit/></a:bodyPr><a:lstStyle/>' .
        $paragraphs .
        '</p:txBody></p:sp>';
}

function blankLayoutXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<p:sldLayout xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" type="blank" preserve="1">' .
        '<p:cSld name="Blank"><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr></p:spTree></p:cSld>' .
        '<p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sldLayout>';
}

function slideMasterXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<p:sldMaster xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">' .
        '<p:cSld><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr></p:spTree></p:cSld>' .
        '<p:clrMap bg1="lt1" tx1="dk1" bg2="lt2" tx2="dk2" accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" hlink="hlink" folHlink="folHlink"/>' .
        '<p:sldLayoutIdLst><p:sldLayoutId id="2147483649" r:id="rId1"/></p:sldLayoutIdLst></p:sldMaster>';
}

function themeXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="TechXpert Theme">' .
        '<a:themeElements><a:clrScheme name="TechXpert"><a:dk1><a:sysClr val="windowText" lastClr="000000"/></a:dk1><a:lt1><a:sysClr val="window" lastClr="FFFFFF"/></a:lt1>' .
        '<a:accent1><a:srgbClr val="00897B"/></a:accent1></a:clrScheme>' .
        '<a:fontScheme name="Segoe"><a:majorFont><a:latin typeface="Segoe UI"/></a:majorFont><a:minorFont><a:latin typeface="Segoe UI"/></a:minorFont></a:fontScheme>' .
        '<a:fmtScheme name="Office"><a:fillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:fillStyleLst></a:fmtScheme>' .
        '</a:themeElements></a:theme>';
}
