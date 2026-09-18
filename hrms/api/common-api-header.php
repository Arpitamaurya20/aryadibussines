<?php
@session_start();
require_once __DIR__ . '/../include/autoloader.inc.php';
header('Access-Control-Allow-Origin: *');
//header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin, Accept, X-Requested-With, Content-Type, Authorization, Access-Control-Request-Method, Access-Control-Request-Headers');
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Asia/Kolkata');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
?>