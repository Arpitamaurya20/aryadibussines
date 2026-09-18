<?php
/**
 * Public secure salary slip download page (main /api folder).
 * Employee opens link from email/WhatsApp/mobile — download PDF only.
 */
require_once __DIR__ . '/inc/payslip_bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

$salary = api_payslip_service();
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$verified = $salary->verifyPayslipShareToken($token);

if (empty($verified['valid'])) {
	http_response_code(403);
	$errMsg = htmlspecialchars($verified['message'] ?? 'Invalid or expired link', ENT_QUOTES, 'UTF-8');
	?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Salary slip unavailable</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 480px;">
	<div class="alert alert-danger"><?php echo $errMsg; ?></div>
	<p class="text-muted small mb-0">Contact HR/Accounts for a new link.</p>
</div>
</body>
</html>
	<?php
	exit;
}

$data = $salary->getSlipWithLines(intval($verified['slip_id']));
if ($data === null) {
	http_response_code(404);
	echo 'Salary slip not found';
	exit;
}

$emp = $data['employee'];
$slip = $data['slip'];
$monthTitle = date('F Y', mktime(0, 0, 0, intval($slip['PayrollMonth']), 1, intval($slip['PayrollYear'])));
$empName = htmlspecialchars($emp['Name'] ?? 'Employee', ENT_QUOTES, 'UTF-8');
$pdfUrl = '../hrms/hrms-salary/view-payslip-pro.php?id=' . intval($verified['slip_id'])
	. '&company_id=' . intval($verified['company_id'])
	. '&pdf=download&token=' . rawurlencode($token);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Download salary slip — <?php echo $empName; ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 520px;">
	<div class="card shadow-sm">
		<div class="card-body text-center p-4">
			<h1 class="h5 mb-2">Salary slip — <?php echo htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
			<p class="text-muted mb-4"><?php echo $empName; ?></p>
			<a href="<?php echo htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-lg w-100">Download salary slip (PDF)</a>
			<p class="text-muted small mt-3 mb-0">Personal secure link. Download only.</p>
		</div>
	</div>
</div>
</body>
</html>
