<?php

function dynamic_ppm_parse_input()
{
    $dataRaw = file_get_contents('php://input');
    $data = json_decode($dataRaw, true);
    if (!is_array($data)) {
        $data = array();
    }
    if (!empty($_GET)) {
        $data = array_merge($_GET, $data);
    }
    if (!empty($_POST)) {
        $data = array_merge($_POST, $data);
    }
    return $data;
}

function dynamic_ppm_response($error, $message, $extra = array())
{
    $GLOBALS['_dynamic_ppm_api_responded'] = true;
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    $payload = array_merge(array(
        'error' => (bool) $error,
        'message' => $message
    ), $extra);
    echo json_encode($payload);
    exit;
}

function dynamic_ppm_int($data, $keys, $default = 0)
{
    foreach ($keys as $key) {
        if (isset($data[$key]) && trim((string) $data[$key]) !== '') {
            return (int) $data[$key];
        }
    }
    return (int) $default;
}

?>
