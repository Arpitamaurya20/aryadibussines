<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

/**
 * Optional: set a secret and send it as ?token=... or header X-Webhook-Token.
 * Leave empty to allow open ingestion (same as other public lead APIs).
 */
define('SOCIAL_LEAD_WEBHOOK_SECRET', '');

header('Content-Type: application/json; charset=utf-8');

$response = ['error' => true, 'message' => 'Invalid request'];

/**
 * Read first non-empty value from payload using several possible keys.
 */
function pick_value(array $data, array $keys)
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $data)) {
            continue;
        }
        $value = $data[$key];
        if (is_array($value)) {
            $value = implode(', ', array_filter(array_map('strval', $value)));
        }
        $value = trim((string) $value);
        if ($value !== '') {
            return $value;
        }
    }
    return null;
}

/**
 * Flatten nested lead payloads (Meta Lead Ads, generic "fields" arrays, etc.).
 */
function flatten_lead_payload(array $data)
{
    $flat = $data;

    // Meta / Facebook Lead Ads: entry[].changes[].value.field_data[]
    if (isset($data['entry']) && is_array($data['entry'])) {
        foreach ($data['entry'] as $entry) {
            if (!isset($entry['changes']) || !is_array($entry['changes'])) {
                continue;
            }
            foreach ($entry['changes'] as $change) {
                $value = $change['value'] ?? [];
                if (!is_array($value)) {
                    continue;
                }
                if (isset($value['field_data']) && is_array($value['field_data'])) {
                    foreach ($value['field_data'] as $field) {
                        $name = strtolower((string) ($field['name'] ?? ''));
                        $vals = $field['values'] ?? [];
                        if ($name !== '' && is_array($vals) && count($vals) > 0) {
                            $flat[$name] = is_array($vals[0]) ? json_encode($vals) : (string) $vals[0];
                        }
                    }
                }
                foreach ($value as $k => $v) {
                    if ($k !== 'field_data' && !is_array($v)) {
                        $flat[$k] = $v;
                    }
                }
            }
        }
        $flat['lead_source'] = $flat['lead_source'] ?? 'facebook';
    }

    // Generic: fields[] with name/value or key/value
    if (isset($data['fields']) && is_array($data['fields'])) {
        foreach ($data['fields'] as $field) {
            if (!is_array($field)) {
                continue;
            }
            $name = strtolower((string) ($field['name'] ?? $field['key'] ?? $field['label'] ?? ''));
            $val  = $field['value'] ?? $field['values'] ?? ($field['data'] ?? '');
            if (is_array($val)) {
                $val = implode(', ', array_filter(array_map('strval', $val)));
            }
            if ($name !== '' && trim((string) $val) !== '') {
                $flat[$name] = $val;
            }
        }
    }

    // data[] wrapper (some CRM connectors)
    if (isset($data['data']) && is_array($data['data']) && pick_value($flat, ['full_name', 'email', 'phone']) === null) {
        $flat = array_merge($flat, flatten_lead_payload($data['data']));
    }

    return $flat;
}

function verify_webhook_secret()
{
    if (SOCIAL_LEAD_WEBHOOK_SECRET === '') {
        return true;
    }

    $token = $_GET['token'] ?? '';
    if ($token === '' && function_exists('getallheaders')) {
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        $token   = $headers['x-webhook-token'] ?? ($headers['authorization'] ?? '');
        $token   = preg_replace('/^Bearer\s+/i', '', trim($token));
    }

    return hash_equals(SOCIAL_LEAD_WEBHOOK_SECRET, (string) $token);
}

function parse_incoming_payload()
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    $payload = [];

    if (is_array($json)) {
        $payload = $json;
    } elseif ($raw !== '' && $raw !== false) {
        parse_str($raw, $parsed);
        if (is_array($parsed) && count($parsed) > 0) {
            $payload = $parsed;
        }
    }

    if (!empty($_POST)) {
        $payload = array_merge($payload, $_POST);
    }
    if (!empty($_GET)) {
        $get = $_GET;
        unset($get['token'], $get['hub_mode'], $get['hub_verify_token'], $get['hub_challenge']);
        $payload = array_merge($payload, $get);
    }

    return [
        'raw'     => $raw !== false ? $raw : '',
        'payload' => flatten_lead_payload($payload),
    ];
}

function build_insert_row(array $data, $rawBody)
{
    $firstName = pick_value($data, ['first_name', 'firstname', 'fname']);
    $lastName  = pick_value($data, ['last_name', 'lastname', 'lname']);
    $fullName  = pick_value($data, [
        'full_name', 'fullname', 'full name', 'name', 'contact_name', 'contactname', 'leadname', 'customer_name',
    ]);

    if ($fullName === null && ($firstName !== null || $lastName !== null)) {
        $fullName = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
    }

    $subject = pick_value($data, ['subject', 'title', 'topic']);
    $message = pick_value($data, [
        'message', 'message_text', 'messagetext', 'query', 'comment', 'description', 'notes', 'body', 'inquiry',
    ]);
    if ($message === null && $subject !== null) {
        $message = $subject;
    } elseif ($message !== null && $subject !== null && stripos($message, $subject) === false) {
        $message = $subject . "\n\n" . $message;
    }

    $rawForDb = $rawBody;
    if ($rawForDb === '' || $rawForDb === false) {
        $rawForDb = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    return [
        'LeadSource'    => pick_value($data, [
            'lead_source', 'leadsource', 'source', 'platform', 'channel', 'utm_source', 'form_source', 'origin',
        ]),
        'CampaignName'  => pick_value($data, [
            'campaign_name', 'campaignname', 'campaign', 'utm_campaign', 'ad_name', 'adset_name',
        ]),
        'FullName'      => $fullName,
        'EmailAddress'  => pick_value($data, ['email', 'email_address', 'emailaddress', 'e-mail', 'mail']),
        'PhoneNumber'   => pick_value($data, [
            'phone', 'phone_number', 'phonenumber', 'mobile', 'contact_phone', 'tel', 'whatsapp',
        ]),
        'CityName'      => pick_value($data, ['city', 'city_name', 'cityname']),
        'StateName'     => pick_value($data, ['state', 'state_name', 'statename', 'region']),
        'BusinessType'  => pick_value($data, [
            'business_type', 'businesstype', 'industry', 'company_type', 'usertype',
        ]),
        'MessageText'   => $message,
        'RawResponse'   => $rawForDb,
        'IPAddress'     => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''),
        'UserAgent'     => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'CreatedDate'   => date('Y-m-d H:i:s'),
        'IsActive'      => 1,
    ];
}

function has_minimum_lead_data(array $row)
{
    $contactFields = ['FullName', 'EmailAddress', 'PhoneNumber'];
    foreach ($contactFields as $field) {
        if (!empty($row[$field])) {
            return true;
        }
    }
    return !empty($row['MessageText']) || !empty($row['LeadSource']);
}

// Meta / Facebook webhook verification (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode      = $_GET['hub_mode'] ?? '';
    $token     = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && SOCIAL_LEAD_WEBHOOK_SECRET !== '' && $token === SOCIAL_LEAD_WEBHOOK_SECRET) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $challenge;
        exit;
    }

    if (!verify_webhook_secret()) {
        http_response_code(401);
        echo json_encode(['error' => true, 'message' => 'Unauthorized']);
        exit;
    }

    echo json_encode([
        'error'   => false,
        'message' => 'Social lead webhook is active',
        'method'  => 'POST JSON or form data to submit leads',
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Method not allowed. Use POST or GET for verification.']);
    exit;
}

if (!verify_webhook_secret()) {
    http_response_code(401);
    echo json_encode(['error' => true, 'message' => 'Unauthorized']);
    exit;
}

$incoming = parse_incoming_payload();
$row      = build_insert_row($incoming['payload'], $incoming['raw']);

if (!has_minimum_lead_data($row)) {
    http_response_code(422);
    echo json_encode([
        'error'   => true,
        'message' => 'No lead data found. Send at least name, email, phone, message, or lead source.',
    ]);
    exit;
}

$dbh  = new Dbh();
$core = new Core();
$conn = $dbh->_connectodb();
$core->setTimeZone();

// Remove nulls so prepared insert only uses provided columns
$insertData = array_filter($row, static function ($v) {
    return $v !== null && $v !== '';
});

$response = $core->_InsertTableRecords_prepare($conn, 'social_media_leads', $insertData);

if (!empty($response['error']) && $response['error'] === true) {
    http_response_code(500);
    $response['message'] = 'Could not save lead. Ensure table social_media_leads exists.';
} else {
    $response['error']   = false;
    $response['message'] = 'Lead saved successfully';
    $response['lead_id'] = $response['last_insert_id'] ?? null;
    unset($response['last_insert_id']);
}

echo json_encode($response);
exit;
