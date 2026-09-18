<?php
@session_start();
require_once('../include/autoloader.inc.php');
$conf = new Conf();

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

$salary = new Salarypayroll($conn);
$rules = $salary->getRulesMap();

$y = isset($_GET['y']) ? intval($_GET['y']) : intval(date('Y'));
$m = isset($_GET['m']) ? intval($_GET['m']) : intval(date('n'));
if ($m < 1 || $m > 12) $m = intval(date('n'));

$flashOk = isset($_GET['ok']) ? $_GET['ok'] : '';
$flashErr = isset($_GET['err']) ? $_GET['err'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Salary Rules</title>
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
			<div class="container-fluid">
				<h1 class="mb-1">Salary Rules Engine</h1>
				<p class="text-muted mb-0">Maintain global payroll rules here. Payroll generation module is separate.</p>
			</div>
		</div>
		<div class="app-content">
			<div class="container-fluid">
				<?php if ($flashOk !== '') { ?><div class="alert alert-success"><?php echo htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
				<?php if ($flashErr !== '') { ?><div class="alert alert-danger"><?php echo htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
				<div class="card">
					<div class="card-header"><strong>Calculation rules</strong></div>
					<div class="card-body">
						<form action="action/save-rule-action.php" method="post" class="row g-3">
							<input type="hidden" name="return_y" value="<?php echo $y; ?>">
							<input type="hidden" name="return_m" value="<?php echo $m; ?>">
							<input type="hidden" name="return_page" value="view-rules.php">
							<div class="col-md-10">
								<label class="form-label">Bulk update (optional)</label>
								<p class="small text-muted mb-2">One rule per line: <code>RuleKey=RuleValue</code>.</p>
								<textarea name="bulk_rules" class="form-control font-monospace" rows="16"><?php
foreach ($rules as $k => $v) {
	echo htmlspecialchars($k . '=' . $v, ENT_QUOTES, 'UTF-8') . "\n";
}
?></textarea>
							</div>
							<div class="col-md-2 d-flex align-items-end">
								<button type="submit" class="btn btn-primary w-100">Save rules</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</main>
</div>
<?php include('../include/common-footer.php'); ?>
</body>
</html>
