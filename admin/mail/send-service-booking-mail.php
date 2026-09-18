<?php

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

require_once __DIR__ . '/include/service-booking-mail-core.php';
sendServiceBookingMail($data);
