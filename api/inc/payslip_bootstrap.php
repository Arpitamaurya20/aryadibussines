<?php
/**
 * Shared HRMS salary slip helpers for root /api and admin profile.
 * Loads HRMS classes directly (avoids admin autoloader looking in admin/classes).
 */
$hrmsClassesDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'hrms' . DIRECTORY_SEPARATOR . 'classes';
if (!class_exists('Core', false)) {
	require_once $hrmsClassesDir . DIRECTORY_SEPARATOR . 'core.class.php';
}
if (!class_exists('Salarypayroll', false)) {
	require_once $hrmsClassesDir . DIRECTORY_SEPARATOR . 'salarypayroll.class.php';
}

function api_payslip_conn()
{
	static $conn = null;
	if ($conn === null) {
		if (!class_exists('Dbh', false)) {
			require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'hrms' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'dbh.class.php';
		}
		$dbh = new Dbh();
		$conn = $dbh->_connectodb();
	}
	return $conn;
}

/**
 * @param mysqli|null $conn Optional existing connection (e.g. admin panel _connectodb).
 */
function api_payslip_service($conn = null)
{
	static $salary = null;
	static $boundConn = null;
	$useConn = $conn ?? api_payslip_conn();
	if ($salary === null || ($conn !== null && $boundConn !== $useConn)) {
		$salary = new Salarypayroll($useConn);
		$boundConn = $useConn;
	}
	return $salary;
}
