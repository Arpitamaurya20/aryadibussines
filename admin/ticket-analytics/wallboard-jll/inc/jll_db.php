<?php
declare(strict_types=1);

/** Standalone DB — no common_controllers (keeps portal isolated). */
function jll_connect(): ?mysqli
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (strpos($host, 'localhost') !== false) {
        $servername = 'localhost';
        $dbusername = 'root';
        $password = '';
        $dbname = 'techxpertindia';
    } elseif (strpos($host, 'techxpertgroup.in') !== false) {
        $servername = 'localhost';
        $dbusername = 'techxper_techxpertindia';
        $password = 'NewTechXpert@123!';
        $dbname = 'techxper_techxpertindia';
    } else {
        $servername = 'localhost';
        $dbusername = 'root';
        $password = 'NewTechXpert@123';
        $dbname = 'techxpertindia';
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($servername, $dbusername, $password, $dbname);
    if ($conn->connect_error) {
        return null;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
