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
$homeHref = 'index.php?EmployeeID=' . $employeeId;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Profile</title>
    <link rel="stylesheet" href="work-zone.css">
</head>
<body>
<div class="wz-app">
    <header class="wz-hero mp-hero">
        <div class="wz-topbar">
            <a class="wz-back" href="<?php echo htmlspecialchars($homeHref); ?>" aria-label="Back">&#8592;</a>
            <h1 class="wz-title">My Profile</h1>
            <span class="wz-icon-btn" aria-hidden="true">&#8942;</span>
        </div>
        <div class="mp-card">
            <div class="mp-avatar" id="mpAvatar">A</div>
            <div>
                <p class="mp-name" id="mpName">Loading...</p>
                <p class="mp-role" id="mpRole"></p>
                <span class="mp-badge" id="mpBadge">Active</span>
            </div>
        </div>
    </header>

    <div class="mp-stats">
        <div class="mp-stat">
            <span>Profile<br>Completed</span>
            <b id="mpComplete">—</b>
        </div>
        <div class="mp-stat">
            <span>Account<br>Status</span>
            <b id="mpStatus">—</b>
        </div>
        <div class="mp-stat">
            <span>Member<br>Since</span>
            <b id="mpSince">—</b>
        </div>
    </div>

    <div class="mp-lists" id="mpLists">
        <p class="wz-status" id="wzStatus">Loading from get_employee_info.php...</p>
    </div>
</div>
<script>
(function () {
  var employeeId = String(<?php echo (int) $employeeId; ?>);
  var wrap = document.getElementById('mpLists');
  var status = document.getElementById('wzStatus');
  var apiUrl = (location.hostname === 'localhost' || location.hostname === '127.0.0.1')
    ? '../../api/get_employee_info.php'
    : 'https://techxpertindia.in/api/get_employee_info.php';

  function dash(v) {
    if (v === null || v === undefined) return '—';
    v = String(v).trim();
    if (!v || v === '0' && arguments[1] === 'allowZero') return v === '0' ? '0' : '—';
    return v ? v : '—';
  }

  function val(v) {
    if (v === null || v === undefined) return '—';
    v = String(v).trim();
    return v === '' ? '—' : v;
  }

  function formatDate(v) {
    if (!v) return '—';
    var d = new Date(v);
    if (isNaN(d.getTime())) return val(v);
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
  }

  function item(icon, label, value) {
    return '<div class="mp-item"><div class="mp-ico">' + icon + '</div><div><label>' + label + '</label><strong>' + val(value) + '</strong></div></div>';
  }

  function section(title, rowsHtml) {
    return '<section class="mp-list"><div class="mp-list-head"><h3>' + title + '</h3></div>' + rowsHtml + '</section>';
  }

  function filledCount(values) {
    return values.filter(function (v) { return v !== null && v !== undefined && String(v).trim() !== ''; }).length;
  }

  fetch(apiUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ EmployeeID: employeeId })
  })
    .then(function (res) { return res.json(); })
    .then(function (json) {
      if (!json || json.error) {
        status.className = 'wz-error';
        status.textContent = (json && json.message) ? json.message : 'Unable to load profile';
        return;
      }

      var d = json.data || {};
      var p = json.profile || {};
      var personal = p.personal || {};
      var employment = p.employment || {};
      var contact = p.contact || {};
      var documents = p.documents || {};
      var bank = p.bank || {};

      function pick() {
        for (var i = 0; i < arguments.length; i++) {
          var v = arguments[i];
          if (v !== null && v !== undefined && String(v).trim() !== '') {
            return v;
          }
        }
        return '';
      }

      var name = pick(p.name, d.Name, p.username);
      var designation = pick(p.designation, d.Designation);
      var username = pick(p.username);
      var father = pick(personal.father_name, d.FatherName);
      var gender = pick(personal.gender, d.Gender);
      var empNo = pick(employment.employee_number, d.EmployeeNumber);
      var doj = pick(employment.date_of_joining, d.DateofJoining);
      var dept = pick(employment.department, d.Department);
      var division = pick(d.Division, (p.divisions || []).join(', '));
      var roles = (p.roles && p.roles.length) ? p.roles.join(', ') : '';
      var supervisor = pick(p.supervisor_name, d.SupervisorName);
      var weeklyOff = pick(employment.weekly_off, d.WeeklyOff);
      var city = pick(employment.city, d.City);
      var state = pick(employment.state, d.State);
      var email = pick(contact.official_email, d.Email);
      var personalEmail = pick(contact.personal_email, d.PersonalEmail);
      var phone = pick(contact.contact_number, d.ContactNumber);
      var uan = pick(documents.uan_number, d.UANNumber);
      var pan = pick(documents.pan, d.PAN);
      var aadhar = pick(documents.aadhar, d.Aadhar);
      var bankName = pick(bank.account_name, d.BankAccountName);
      var bankNo = pick(bank.account_number, d.BankAccountNumber);

      var avatar = document.getElementById('mpAvatar');
      if (p.photo_url) {
        avatar.innerHTML = '<img alt="" src="' + p.photo_url + '">';
      } else {
        avatar.textContent = (name || 'E').charAt(0).toUpperCase();
      }
      document.getElementById('mpName').textContent = name || 'Employee';
      document.getElementById('mpRole').textContent = designation;
      var isActive = String(d.IsActive) !== '0';
      document.getElementById('mpBadge').textContent = isActive ? 'Active' : 'Inactive';
      document.getElementById('mpStatus').textContent = isActive ? 'Active' : 'Inactive';
      document.getElementById('mpSince').textContent = formatDate(doj || d.CreatedDate);

      var checkFields = [name, username, father, gender, designation, empNo, dept, email, phone, uan, pan, aadhar, bankName, bankNo, city, state];
      document.getElementById('mpComplete').textContent =
        Math.round((filledCount(checkFields) / checkFields.length) * 100) + '%';

      wrap.innerHTML =
        section('Personal Information',
          item('👤', 'Name', name) +
          item('@', 'Username', username) +
          item('👨', 'Father Name', father) +
          item('⚧', 'Gender', gender) +
          item('💼', 'Designation', designation)
        ) +
        section('Employment',
          item('#', 'Employee ID', empNo) +
          item('📅', 'Date of Joining', formatDate(doj)) +
          item('🏢', 'Department', dept) +
          item('📂', 'Division', division) +
          item('🏷', 'Roles', roles) +
          item('👤', 'Supervisor', supervisor) +
          item('📅', 'Weekly Off', weeklyOff) +
          item('📍', 'City', city) +
          item('🗺', 'State', state)
        ) +
        section('Contact',
          item('@', 'Official Email', email) +
          item('✉', 'Personal Email', personalEmail) +
          item('☎', 'Phone Number', phone)
        ) +
        section('Documents & Identity',
          item('#', 'UAN Number', uan) +
          item('🪪', 'PAN Number', pan) +
          item('🪪', 'Aadhaar Number', aadhar) +
          item('#', 'EPF Number', d.Epf_number) +
          item('#', 'ESIC Number', d.Esic_number)
        ) +
        section('Bank Details',
          item('🏦', 'Bank Account Name', bankName) +
          item('💳', 'Bank Account Number', bankNo)
        ) +
        section('Salary',
          item('₹', 'Basic', d.Basic) +
          item('₹', 'DA', d.DA) +
          item('₹', 'HRA', d.HRA) +
          item('₹', 'Bonus', d.Bonus) +
          item('₹', 'Convenience Allowance', d.ConvenienceAllowance) +
          item('₹', 'Health Insurance', d.HealthInsurance) +
          item('₹', 'Others', d.Others) +
          item('₹', 'Gross', d.Gross) +
          item('₹', 'In-Hand Salary', d.InHandSalary)
        );
    })
    .catch(function () {
      status.className = 'wz-error';
      status.textContent = 'Profile API request failed';
    });
})();
</script>
</body>
</html>
