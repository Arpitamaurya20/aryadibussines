<?php
session_start();
include('../controllers/common_controllers.php');
$UserType = SessionCheck();
$employeeId = 0;
if (isset($_SESSION['Roles']['EmployeeID']) && is_numeric($_SESSION['Roles']['EmployeeID'])) {
    $employeeId = (int) $_SESSION['Roles']['EmployeeID'];
}
if ($employeeId <= 0 && isset($_GET['EmployeeID']) && is_numeric($_GET['EmployeeID'])) {
    $employeeId = (int) $_GET['EmployeeID'];
}
if ($employeeId <= 0) {
    $employeeId = 1;
}
$profileHref = 'profile.php?EmployeeID=' . $employeeId;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Work Zone</title>
    <link rel="stylesheet" href="work-zone.css">
</head>
<body>
<div class="wz-app">
    <header class="wz-hero">
        <div class="wz-topbar">
            <a class="wz-back" href="../dashboard/analytics_dashboard" aria-label="Back">&#8592;</a>
            <h1 class="wz-title">My Work Zone</h1>
            <span class="wz-icon-btn" aria-hidden="true">&#128100;</span>
        </div>
        <p class="wz-hello" id="wzHello">Hello</p>
        <div class="wz-attendance">
            <h3>Today's attendance</h3>
            <div class="wz-punch-row">
                <div class="wz-punch">
                    <span>Punch In</span>
                    <strong id="wzPunchIn">--:--</strong>
                </div>
                <div class="wz-punch">
                    <span>Punch Out</span>
                    <strong id="wzPunchOut">--:--</strong>
                </div>
            </div>
        </div>
    </header>

    <main class="wz-body">
        <p class="wz-section-label">My Workspace</p>
        <p class="wz-section-sub">Open a module to continue your work.</p>
        <div class="wz-grid">
            <a class="wz-tile" href="<?php echo htmlspecialchars($profileHref); ?>">
                <span class="wz-tile-icon">P</span>
                <strong>Profile</strong>
                <span>Personal info</span>
            </a>
            <a class="wz-tile" href="javascript:void(0)">
                <span class="wz-tile-icon">L</span>
                <strong>Leave</strong>
                <span>Apply and track</span>
            </a>
            <a class="wz-tile" href="javascript:void(0)">
                <span class="wz-tile-icon">A</span>
                <strong>Attendance</strong>
                <span>Punch in/out</span>
            </a>
            <a class="wz-tile" href="javascript:void(0)">
                <span class="wz-tile-icon">C</span>
                <strong>Convenience</strong>
                <span>Claims &amp; charges</span>
            </a>
            <a class="wz-tile" href="javascript:void(0)">
                <span class="wz-tile-icon">H</span>
                <strong>History</strong>
                <span>Working ledger</span>
            </a>
            <a class="wz-tile" href="javascript:void(0)">
                <span class="wz-tile-icon">K</span>
                <strong>Employee KPI</strong>
                <span>Performance</span>
            </a>
            <a class="wz-tile" href="javascript:void(0)">
                <span class="wz-tile-icon">HR</span>
                <strong>HR Helpdesk</strong>
                <span>Support &amp; help</span>
            </a>
            <a class="wz-tile" href="javascript:void(0)">
                <span class="wz-tile-icon">R</span>
                <strong>Regularization</strong>
                <span>WFH / Corrections</span>
            </a>
        </div>
    </main>
</div>
<script>
(function () {
  var employeeId = <?php echo (int) $employeeId; ?>;
  if (!employeeId) {
    return;
  }
  fetch((location.hostname === 'localhost' || location.hostname === '127.0.0.1')
    ? '../../api/get_employee_info.php'
    : 'https://techxpertindia.in/api/get_employee_info.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ EmployeeID: String(employeeId) })
  })
    .then(function (res) { return res.json(); })
    .then(function (json) {
      if (!json || json.error) {
        return;
      }
      var name = (json.profile && json.profile.name) || (json.data && json.data.Name) || '';
      if (name) {
        document.getElementById('wzHello').textContent = 'Hello, ' + name;
      }
    })
    .catch(function () {});
})();
</script>
</body>
</html>
