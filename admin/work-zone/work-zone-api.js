window.WORK_ZONE_EMPLOYEE_API = 'https://techxpertindia.in/api/get_employee_info.php';

function loadEmployeeInfo(employeeId) {
  return fetch(window.WORK_ZONE_EMPLOYEE_API, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ EmployeeID: Number(employeeId) })
  }).then(function (res) { return res.json(); });
}

function dashValue(v) {
  v = (v === null || v === undefined) ? '' : String(v).trim();
  return v ? v : '—';
}

function formatJoinDate(v) {
  if (!v) return '—';
  var d = new Date(v);
  if (isNaN(d.getTime())) return dashValue(v);
  var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
}

function mapEmployeeProfile(json) {
  var p = json && json.profile ? json.profile : null;
  var d = json && json.data ? json.data : {};
  if (p) {
    return {
      name: p.name || d.Name || '',
      username: p.username || '',
      designation: p.designation || d.Designation || '',
      photo_url: p.photo_url || d.ProfileImage || '',
      is_active: !(d.IsActive === '0' || d.IsActive === 0),
      date_of_joining: (p.employment && p.employment.date_of_joining) || d.DateofJoining || d.CreatedDate || '',
      email: (p.contact && p.contact.official_email) || d.Email || '',
      phone: (p.contact && p.contact.contact_number) || d.ContactNumber || '',
      uan: (p.documents && p.documents.uan_number) || d.UANNumber || '',
      bank_name: (p.bank && p.bank.account_name) || d.BankAccountName || '',
      bank_number: (p.bank && p.bank.account_number) || d.BankAccountNumber || '',
      pan: (p.documents && p.documents.pan) || d.PAN || ''
    };
  }
  return {
    name: d.Name || '',
    username: '',
    designation: d.Designation || '',
    photo_url: d.ProfileImage || '',
    is_active: !(d.IsActive === '0' || d.IsActive === 0),
    date_of_joining: d.DateofJoining || d.CreatedDate || '',
    email: d.Email || '',
    phone: d.ContactNumber || '',
    uan: d.UANNumber || '',
    bank_name: d.BankAccountName || '',
    bank_number: d.BankAccountNumber || '',
    pan: d.PAN || ''
  };
}
