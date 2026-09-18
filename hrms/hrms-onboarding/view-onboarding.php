<?php
@session_start();
require_once('../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();
$core = new Core();

$flashOk = isset($_GET['ok']) ? $_GET['ok'] : '';
$flashErr = isset($_GET['err']) ? $_GET['err'] : '';

$recentEmployees = $core->_getTableRecords($conn, 'employees', 'WHERE IFNULL(IsActive,1)=1 ORDER BY ID DESC LIMIT 10');
$daysArray = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Employee Onboarding</title>
	<?php include('../include/common-head.php'); ?>
	<style>
		.onb-tab-pane { border:1px solid #e5e7eb; border-top:0; border-radius:0 0 10px 10px; padding:16px; }
	</style>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
<div class="app-wrapper">
	<?php include('../navigation/top-header.php'); ?>
	<?php include('../navigation/side-navigation.php'); ?>
	<main class="app-main">
		<div class="app-content-header">
			<div class="container-fluid">
				<h1 class="mb-1">Employee Onboarding</h1>
				<p class="text-muted mb-0">HRMS onboarding module based on existing <code>employees</code> SQL structure.</p>
			</div>
		</div>
		<div class="app-content">
			<div class="container-fluid">
				<?php if ($flashOk !== '') { ?><div class="alert alert-success"><?php echo htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
				<?php if ($flashErr !== '') { ?><div class="alert alert-danger"><?php echo htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>

				<div class="card mb-3">
					<div class="card-header"><strong>Required Data Scope</strong></div>
					<div class="card-body small">
						Uses existing table: <code>employees</code>. No disturbance to old <code>admin/employees</code> module.
						Sections covered: Personal, Job, Salary, Statutory, Bank, Documents.
					</div>
				</div>

				<div class="card mb-3">
					<div class="card-header"><strong>Onboard New Employee</strong></div>
					<div class="card-body">
						<form method="post" action="action/save-onboarding.php" enctype="multipart/form-data">
							<ul class="nav nav-tabs" id="onbTabs" role="tablist">
								<li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-personal" type="button">Personal</button></li>
								<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-job" type="button">Job</button></li>
								<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-salary" type="button">Salary</button></li>
								<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-statutory" type="button">Statutory</button></li>
								<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bank" type="button">Bank & Docs</button></li>
							</ul>

							<div class="tab-content onb-tab-pane">
								<div class="tab-pane fade show active" id="tab-personal">
									<div class="row g-3">
										<div class="col-md-3"><label class="form-label">Employee Number *</label><input required name="EmployeeNumber" class="form-control" placeholder="TECHX0001"></div>
										<div class="col-md-3"><label class="form-label">Name *</label><input required name="Name" class="form-control"></div>
										<div class="col-md-3"><label class="form-label">Father Name</label><input name="FatherName" class="form-control"></div>
										<div class="col-md-3"><label class="form-label">Gender</label><select name="Gender" class="form-select"><option value="">Select</option><option>His</option><option>Her</option></select></div>
										<div class="col-md-4"><label class="form-label">Email</label><input type="email" name="Email" class="form-control"></div>
										<div class="col-md-4"><label class="form-label">Personal Email</label><input type="email" name="PersonalEmail" class="form-control"></div>
										<div class="col-md-4"><label class="form-label">Contact Number *</label><input required name="ContactNumber" class="form-control"></div>
										<div class="col-md-4"><label class="form-label">City</label><input name="City" class="form-control"></div>
										<div class="col-md-4"><label class="form-label">State</label><input name="State" class="form-control"></div>
										<div class="col-md-4"><label class="form-label">Address (Others)</label><input name="OthersNote" class="form-control" placeholder="Optional note"></div>
									</div>
								</div>

								<div class="tab-pane fade" id="tab-job">
									<div class="row g-3">
										<div class="col-md-3"><label class="form-label">Designation *</label><input required name="Designation" class="form-control"></div>
										<div class="col-md-3"><label class="form-label">Department *</label><input required name="Department" class="form-control"></div>
										<div class="col-md-3"><label class="form-label">Division / Branch</label><input name="Division" class="form-control"></div>
										<div class="col-md-3"><label class="form-label">Date of Joining *</label><input required type="date" name="DateofJoining" class="form-control"></div>
										<div class="col-md-3"><label class="form-label">Weekly Off</label><select name="WeeklyOff" class="form-select"><?php foreach($daysArray as $d){ echo '<option value="'.htmlspecialchars($d, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($d, ENT_QUOTES, 'UTF-8').'</option>'; } ?></select></div>
										<div class="col-md-3"><label class="form-label">Supervisor</label><input name="Supervisor" class="form-control"></div>
										<div class="col-md-3"><label class="form-label">Work Type</label><select name="WorkType" class="form-select"><option value="Employee">Employee</option><option value="Vendor">Vendor</option></select></div>
										<div class="col-md-3"><label class="form-label">Apply EPF</label><select name="ApplyEPF" class="form-select"><option value="1">Yes</option><option value="0">No</option></select></div>
										<div class="col-md-3"><label class="form-label">Apply ESI</label><select name="ApplyESI" class="form-select"><option value="1">Yes</option><option value="0">No</option></select></div>
									</div>
								</div>

								<div class="tab-pane fade" id="tab-salary">
									<div class="row g-3">
										<div class="col-md-2"><label class="form-label">Basic</label><input name="Basic" class="form-control" value="0"></div>
										<div class="col-md-2"><label class="form-label">DA</label><input name="DA" class="form-control" value="0"></div>
										<div class="col-md-2"><label class="form-label">HRA</label><input name="HRA" class="form-control" value="0"></div>
										<div class="col-md-2"><label class="form-label">Conv. Allow.</label><input name="ConvenienceAllowance" class="form-control" value="0"></div>
										<div class="col-md-2"><label class="form-label">Bonus</label><input name="Bonus" class="form-control" value="0"></div>
										<div class="col-md-2"><label class="form-label">Health Insurance</label><input name="HealthInsurance" class="form-control" value="0"></div>
										<div class="col-md-2"><label class="form-label">Others</label><input name="Others" class="form-control" value="0"></div>
									</div>
								</div>

								<div class="tab-pane fade" id="tab-statutory">
									<div class="row g-3">
										<div class="col-md-3"><label class="form-label">UAN Number</label><input name="UANNumber" class="form-control"></div>
										<div class="col-md-3"><label class="form-label">EPF Number</label><input name="Epf_number" class="form-control"></div>
										<div class="col-md-3"><label class="form-label">ESIC Number</label><input name="Esic_number" class="form-control"></div>
										<div class="col-md-3"><label class="form-label">PAN *</label><input required name="PAN" class="form-control"></div>
										<div class="col-md-3"><label class="form-label">Aadhar *</label><input required name="Aadhar" class="form-control"></div>
									</div>
								</div>

								<div class="tab-pane fade" id="tab-bank">
									<div class="row g-3">
										<div class="col-md-4"><label class="form-label">Bank Account Name</label><input name="BankAccountName" class="form-control"></div>
										<div class="col-md-4"><label class="form-label">Bank Account Number</label><input name="BankAccountNumber" class="form-control"></div>
										<div class="col-md-4"><label class="form-label">Profile Image</label><input type="file" name="ProfileImage" class="form-control"></div>
										<div class="col-md-4"><label class="form-label">PAN Image</label><input type="file" name="PANImage" class="form-control"></div>
										<div class="col-md-4"><label class="form-label">Aadhar Image</label><input type="file" name="AadharImage" class="form-control"></div>
										<div class="col-md-4"><label class="form-label">Police Verification</label><input type="file" name="PoliceVerificationImage" class="form-control"></div>
									</div>
								</div>
							</div>
							<div class="mt-3 d-flex gap-2">
								<button class="btn btn-success" type="submit">Onboard Employee</button>
								<a class="btn btn-outline-secondary" href="../hrms-employees/view-employees">View Employee Directory</a>
							</div>
						</form>
					</div>
				</div>

				<div class="card">
					<div class="card-header"><strong>Recently Onboarded</strong></div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table mb-0">
								<thead><tr><th>Name</th><th>Code</th><th>Department</th><th>Designation</th><th>State</th><th>Joined</th></tr></thead>
								<tbody>
								<?php if (empty($recentEmployees)) { ?>
									<tr><td colspan="6" class="text-center text-muted py-4">No records found.</td></tr>
								<?php } else { foreach ($recentEmployees as $r) { ?>
									<tr>
										<td><?php echo htmlspecialchars($r['Name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
										<td><?php echo htmlspecialchars($r['EmployeeNumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
										<td><?php echo htmlspecialchars($r['Department'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
										<td><?php echo htmlspecialchars($r['Designation'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
										<td><?php echo htmlspecialchars($r['State'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
										<td><?php echo htmlspecialchars($r['DateofJoining'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
									</tr>
								<?php }} ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

			</div>
		</div>
	</main>
</div>
<?php include('../include/common-footer.php'); ?>
<?php include('../include/common-script.php'); ?>
</body>
</html>
