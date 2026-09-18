<?php

if (!isset($_GET['lat']) || !isset($_GET['lng'])) {
    exit("Invalid Location");
}

$lat = $_GET['lat'];
$lng = $_GET['lng'];

$api_key = "AIzaSyDU0suOSG-X34RvDzawjDGHbX1C5JrHHsw"; // <-- Put your real API key here

$url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=".$lat.",".$lng."&key=".$api_key;

$response = file_get_contents($url);

if ($response === FALSE) {
    exit("Unable to fetch address");
}

$data = json_decode($response, true);

if ($data['status'] == "OK") {
    echo $data['results'][0]['formatted_address'];
} else {
    echo "Address Not Found";
}
