<?php
@session_start();
require_once('../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

$core = new Core();
$salary = new Salarypayroll($conn);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$m = isset($_GET['m']) ? intval($_GET['m']) : intval(date('n'));
$y = isset($_GET['y']) ? intval($_GET['y']) : intval(date('Y'));

$employee = $id ? $core->_getTableDetails($conn, 'employees', 'WHERE ID = ' . $id) : null;
if (empty($employee['ID'])) {
	http_response_code(404);
	echo 'Employee not found';
	exit;
}

$slips = $salary->listEmployeeSlips($id, 12);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Employee Profile</title>
	<?php include('../include/common-head.php'); ?>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
<div class="app-wrapper">
	<?php include('../navigation/top-header.php'); ?>
	<?php include('../navigation/side-navigation.php'); ?>
	<main class="app-main">
		<div class="app-content-header">
			<div class="container-fluid d-flex justify-content-between align-items-center">
				<div>
					<h1 class="mb-1"><?php echo htmlspecialchars($employee['Name'], ENT_QUOTES, 'UTF-8'); ?></h1>
					<p class="text-muted mb-0"><?php echo htmlspecialchars($employee['EmployeeNumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars($employee['Designation'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
				</div>
				<a href="view-employees.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>" class="btn btn-outline-secondary btn-sm">Back</a>
			</div>
		</div>
		<div class="app-content">
			<div class="container-fluid">
				<div class="row g-3">
					<div class="col-md-4">
						<div class="card">
							<div class="card-header"><strong>Profile</strong></div>
							<div class="card-body">
								<div><strong>Email:</strong> <?php echo htmlspecialchars($employee['Email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
								<div><strong>Phone:</strong> <?php echo htmlspecialchars($employee['ContactNumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
								<div><strong>Department:</strong> <?php echo htmlspecialchars($employee['Department'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
								<div><strong>Branch:</strong> <?php echo htmlspecialchars($employee['Division'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
								<div><strong>Join Date:</strong> <?php echo htmlspecialchars($employee['DateofJoining'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
							</div>
						</div>
						<div class="card mt-3">
							<div class="card-header"><strong>Compliance</strong></div>
							<div class="card-body">
								<div><strong>EPF:</strong> <?php echo !empty($employee['ApplyEPF']) ? 'Enabled' : 'Disabled'; ?></div>
								<div><strong>ESI:</strong> <?php echo !empty($employee['ApplyESI']) ? 'Enabled' : 'Disabled'; ?></div>
								<div><strong>UAN:</strong> <?php echo htmlspecialchars($employee['UANNumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
								<div><strong>EPF No:</strong> <?php echo htmlspecialchars($employee['Epf_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
								<div><strong>ESIC No:</strong> <?php echo htmlspecialchars($employee['Esic_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
							</div>
						</div>
					</div>
					<div class="col-md-8">
						<div class="card">
							<div class="card-header"><strong>Recent Salary Slips</strong></div>
							<div class="card-body p-0">
								<div class="table-responsive">
									<table class="table mb-0">
										<thead>
											<tr>
												<th>Period</th>
												<th class="text-end">Gross</th>
												<th class="text-end">Deduction</th>
												<th class="text-end">Net</th>
												<th></th>
											</tr>
										</thead>
										<tbody>
										<?php if (empty($slips)) { ?>
											<tr><td colspan="5" class="text-center text-muted py-4">No salary slips generated yet.</td></tr>
										<?php } else { foreach ($slips as $s) { ?>
											<tr>
												<td><?php echo htmlspecialchars(date('M Y', mktime(0,0,0,intval($s['PayrollMonth']),1,intval($s['PayrollYear']))), ENT_QUOTES, 'UTF-8'); ?></td>
												<td class="text-end"><?php echo number_format(floatval($s['GrossSalary']),2); ?></td>
												<td class="text-end"><?php echo number_format(floatval($s['TotalDeductions']),2); ?></td>
												<td class="text-end"><?php echo number_format(floatval($s['NetSalary']),2); ?></td>
												<td>
													<a class="btn btn-sm btn-outline-primary" target="_blank" href="../hrms-salary/view-payslip-pro.php?id=<?php echo intval($s['ID']); ?>">Professional Slip</a>
												</td>
											</tr>
										<?php }} ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>
</div>
<?php include('../include/common-footer.php'); ?>
</body>
</html>
