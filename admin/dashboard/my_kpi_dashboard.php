<?php 
session_start(); 
// var_dump($_SESSION);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="description" content="Aryadibusiness Analytics Dashboard">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no, minimal-ui">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
include('../includes/common_head_content.php');
include('../includes/autoloader.inc.php');
include('../controllers/common_controllers.php');
$UserType = SessionCheck();
setNavigation($_SESSION['Roles']);
$conn = _connectodb();
$core = new Core();
$core->setTimeZone();
$employeeID = $_SESSION['Roles']['EmployeeID'] ?? '';
?>
<style>
.kpi-dashboard{padding:30px 15px;}
.dashboard-header{background:#fff;border-radius:14px;padding:25px 30px;margin-bottom:25px;box-shadow:0 4px 12px rgba(0,0,0,.05);}
.dashboard-title{font-size:26px;font-weight:700;color:#2c3e50;display:flex;align-items:center;gap:12px;}
.filter-btn{display:flex;align-items:center;gap:8px;border-radius:10px;padding:10px 18px;font-weight:500;}
.kpi-container{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;}
.kpi-card{background:#fff;border-radius:14px;padding:24px;box-shadow:0 4px 12px rgba(0,0,0,.06);transition:.25s;border-top:4px solid transparent;position:relative;}
.kpi-card:hover{transform:translateY(-3px);box-shadow:0 8px 18px rgba(0,0,0,.1);}
.kpi-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;}
.kpi-title{font-weight:600;font-size:17px;color:#2c3e50;display:flex;align-items:center;gap:8px;}
.kpi-icon{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;}
.performance-badge{padding:5px 12px;border-radius:20px;font-size:11px;font-weight:600;text-transform:uppercase;}
.badge-excellent{background:linear-gradient(135deg,#2ecc71,#27ae60);color:#fff;}
.badge-good{background:linear-gradient(135deg,#3498db,#2980b9);color:#fff;}
.badge-average{background:linear-gradient(135deg,#f39c12,#e67e22);color:#fff;}
.badge-poor{background:linear-gradient(135deg,#e74c3c,#c0392b);color:#fff;}
.meter-container{height:140px;display:flex;align-items:center;justify-content:center;position:relative;}
.meter-value{position:absolute;font-weight:700;font-size:22px;color:#2c3e50;}
.kpi-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:12px;margin-top:18px;border-top:1px solid #eee;padding-top:18px;}
.stat-item{text-align:center;background:#f8f9fa;border-radius:10px;padding:12px 8px;}
.stat-number{font-size:18px;font-weight:700;color:#2c3e50;margin-bottom:4px;}
.stat-label{font-size:11px;color:#6c757d;font-weight:500;text-transform:uppercase;}
.loading-container,.no-data-container{background:#fff;border-radius:14px;padding:40px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,.05);}
.loading-container i{font-size:36px;color:#3498db;animation:spin 2s linear infinite;}
@keyframes spin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}
@media(max-width:768px){.dashboard-title{font-size:22px;}}

/* .page-wrapper { display: flex; flex-direction: column; min-height: 100vh; font-family: 'Poppins', sans-serif; } */
/* .page-inner { flex: 1; display: flex; }
.page-content-wrapper { flex: 1; padding: 20px; } */
/* .dashboard-header { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 25px 30px; border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,.05); margin-bottom: 25px; }
.dashboard-title { font-size: 26px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
.kpi-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
.btn { cursor: pointer; border-radius: 10px; padding: 10px 18px; font-weight: 500; border: 1px solid #007bff; background-color: #fff; color: #007bff; transition: 0.25s; }
.btn:hover { background-color: #007bff; color: #fff; } */


.offcanvas { 
  position: fixed; top: 0; right: -350px; width: 350px; height: 100%; background: #fff; box-shadow: -3px 0 15px rgba(0,0,0,.1); transition: 0.3s; z-index: 9999; overflow-y: auto; padding: 20px; }
.offcanvas.show { right: 0; }
.btn-close { background: none; border: none; font-size: 24px; cursor: pointer; }

select, input[type="date"], input[type="text"], input[type="number"] {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
}
label { display: block; font-weight: 500; margin-bottom: 6px; font-size: 14px; }

</style>
</head>
<body>
<input type="hidden" id="UserType" value="<?php echo $UserType;?>">
<input type="hidden" id="LoggedEmployeeID" value="<?php echo $employeeID;?>">
<div class="page-wrapper">
    <div class="page-inner">
        <?php include('../navigation/admin_navigation.php'); ?>
        <div class="page-content-wrapper">
            <?php include('../includes/common_header.php'); ?>
            <main class="page-content">
               
                <div class="kpi-dashboard">
                     <a href="https://techxpertindia.in/admin/employees/view_profile_details" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>

                    <div class="dashboard-header d-flex justify-content-between align-items-center mt-4">
                        <h1 class="dashboard-title"><i class="bi bi-speedometer2 text-primary"></i>Performance KPI Dashboard</h1>
                        <button class="btn btn-outline-primary filter-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterCanvas"><i class="bi bi-funnel-fill"></i>Filter</button>
                    </div>

                    <div id="employeeDetails" class="mb-4"></div>
                    
                    <!-- Filter Offcanvas -->
                    <div id="filterCanvas" class="offcanvas">
                            <div class="offcanvas-header">
                                <h5>Filter Options</h5>
                                <button class="btn-close" onclick="toggleCanvas()">&times;</button>
                            </div>
                            <div class="offcanvas-body">
                            <form id="filterForm" class="row g-3">
                                <!-- Admin-only selects -->
                                <div class="col-12 role-employee-selects">
                                    <label class="form-label">Select Role</label>
                                    <select class="form-select" id="role" name="role">
                                        <option value="">-- Select Role --</option>
                                        <option value="Technician">Technician</option>
                                        <option value="City Lead">City Lead</option>
                                        <option value="Region Lead">Region Lead</option>
                                        <option value="Vendor">Vendor</option>
                                        <option value="Account Manager">Account Manager</option>
                                        <option value="Branch Account Manager">Branch Account Manager</option>
                                    </select>
                                </div>
                                <div class="col-12 role-employee-selects">
                                    <label class="form-label">Select Employee</label>
                                    <select class="form-select" id="employee" name="employee">
                                        <option value="">-- Select Employee --</option>
                                    </select>
                                </div>

                                <!-- Date range (always visible) -->
                                <div class="col-md-6">
                                    <label class="form-label">From Date</label>
                                    <input type="date" class="form-control" name="date_from" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">To Date</label>
                                    <input type="date" class="form-control" name="date_to" required>
                                </div>

                                <div class="col-12">
                                    <button class="btn btn-success w-100"><i class="bi bi-check2-circle"></i> Apply Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div id="statusContainer"></div>
                    <div class="kpi-container" id="kpiContainer"></div>
                </div>
            </main>
        </div>
    </div>
</div>

<?php
include('../includes/common_modules.php');
include('../includes/common_scripts.php');
?>
<script>
const employeeSelect = document.getElementById("employee");
const roleSelect = document.getElementById("role");
const filterForm = document.getElementById("filterForm");
const dateFromInput = filterForm.querySelector("input[name='date_from']");
const dateToInput = filterForm.querySelector("input[name='date_to']");
const UserType = document.getElementById("UserType").value;
const empID = document.getElementById("LoggedEmployeeID").value;

// Set date range to current month by default
const today = new Date();
const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
dateFromInput.value = firstDay.toISOString().slice(0,10);
dateToInput.value = today.toISOString().slice(0,10);

// ------------------ Role & Employee Handling ------------------
if(UserType !== 'Admin'){
    // Hide role & employee selects
    document.querySelectorAll('.role-employee-selects').forEach(el=>el.style.display='none');

    // Auto-set employee as logged-in user
    employeeSelect.innerHTML = `<option value="${empID}" selected>Myself</option>`;

    // Auto-submit filter when date changes
    dateFromInput.addEventListener('change', ()=> $(filterForm).submit());
    dateToInput.addEventListener('change', ()=> $(filterForm).submit());

    // Auto-submit filter initially
    setTimeout(()=> $(filterForm).submit(), 500);

}else{
    // Admin: dynamic employee list based on role
    roleSelect.addEventListener("change", function () {
        employeeSelect.innerHTML = '<option value="">-- Select Employee --</option>';
        if (this.value) {
            fetch("ajax/getEmployeesByRole.php?role=" + this.value)
            .then(r=>r.json()).then(data=>{
                if(data.success){
                    data.employees.forEach(e=>{
                        const opt = document.createElement("option");
                        opt.value = e.id;
                        opt.textContent = `${e.name} (${e.employee_number})`;
                        employeeSelect.appendChild(opt);
                    });
                }
            });
        }
    });
}

// ------------------ KPI Form Submit ------------------
$("#filterForm").on("submit", function (e) {
    e.preventDefault();
    showLoading();

    const params = $(this).serialize();

    Promise.all([
        fetch("ajax/get_detailed_assignment_kpi.php?" + params).then(r => r.json()),
        fetch("ajax/get_detailed_quatation_kpi.php?" + params).then(r => r.json()),
        fetch("ajax/get_detailed_closed.php?" + params).then(r => r.json()),
        fetch("ajax/get_detailed_attendance.php?" + params).then(r => r.json())
    ])
.then(([assign, quote, closed, attendance]) => {
    kpiContainer.innerHTML = '';
    let anyData = false;
    let percentages = [];

    // ✅ Check if employee is "Executive-type"
    const onlyAttendance = isExecutiveDesignation(currentEmployeeDesignation);

    // Only show Assignment, Quotation, Closed for non-Executives
    if (!onlyAttendance) {
        if (assign.success && assign.data.length) {
            renderAssignment(assign.data);
            percentages.push(avg(assign.data.map(x => parseFloat(x['Performance%'])||0)));
            anyData = true;
        }
        if (quote.success && quote.data.length) {
            renderQuotation(quote.data);
            anyData = true;
        }
        // if (closed.success && closed.data) {
        //     renderClosed([closed.data]);
        //     percentages.push(parseFloat(closed.data['Performance%'])||0);
        //     anyData = true;
        // }
        if (closed.success && closed.data) {
    renderClosed([closed.data]);

    // ✅ Determine performance for averaging
    let perf;
    if ((closed.data.AvailableTickets || 0) === 0) {
        // No tickets → treat as 100%
        perf = 100;
    } else {
        // Has tickets → use actual Performance%
        perf = parseFloat(closed.data['Performance%']) || 0;
    }

    if (perf > 0) percentages.push(perf);
    anyData = true;
}

    }

    // ✅ Attendance KPI — always shown
    if (attendance.success && attendance.data) {
        renderAttendance([attendance.data]);
        percentages.push(parseFloat(attendance.data['Performance%'])||0);
        anyData = true;
    }

    // ✅ Salary KPI — always shown
    if (percentages.length) {
        const overall = avg(percentages);
        const empIdVal = $("#employee").val();
        if(empIdVal){
            fetch("ajax/get_employee_salary.php?employee=" + empIdVal)
            .then(r=>r.json())
            .then(sal=>{
                if(sal.success){
                    const fullSalary = parseFloat(sal.data.TotalSalary);
                    let payable = fullSalary;
                    if(overall < 89.5){
                        payable = (overall/100)*fullSalary;
                    }
                    renderSalary(fullSalary, payable, overall);
                }
            });
        }
    }

    if (!anyData) showNoData();
    else statusContainer.innerHTML = '';
})

    .catch(err => showError(err));

    const empIdVal = $("#employee").val();
    if (empIdVal) {
        fetch("ajax/get_employee_details.php?employee=" + empIdVal)
        .then(r => r.json())
        .then(emp => {
            if (emp.success) {
                renderEmployeeDetails(emp.data);
            } else {
                document.getElementById("employeeDetails").innerHTML = "";
            }
        });
    }
});

// ------------------ KPI Renderers & Helpers ------------------
const kpiContainer = document.getElementById('kpiContainer');
const statusContainer = document.getElementById('statusContainer');
let charts = {};

function showLoading(){kpiContainer.innerHTML='';statusContainer.innerHTML=`<div class="loading-container"><i class="bi bi-hourglass-split"></i><h5 class="mt-3">Loading Performance Data...</h5></div>`;}
function showNoData(){statusContainer.innerHTML=`<div class="no-data-container"><i class="bi bi-inbox mb-3"></i><h5>No Data Found</h5><p>Try different filters.</p></div>`;}
function showError(msg){statusContainer.innerHTML=`<div class="no-data-container text-danger"><i class="bi bi-exclamation-triangle mb-3"></i><h5>Error</h5><p>${msg}</p></div>`;}
function avg(arr){return arr.reduce((a,b)=>a+b,0)/arr.length;}
function uniqueID(){return Date.now()+Math.floor(Math.random()*10000);}
function getStatus(p){if(p>=90)return {txt:'Excellent',cls:'badge-excellent'};if(p>=80)return {txt:'Good',cls:'badge-good'};if(p>=70)return {txt:'Average',cls:'badge-average'};return {txt:'Poor',cls:'badge-poor'};}
function getColor(p,isAssign){const cAssign=['#e74c3c','#f39c12','#3498db','#2ecc71'];const cQuote=['#c0392b','#e67e22','#2980b9','#27ae60'];const arr=isAssign?cAssign:cQuote;return p>=90?arr[3]:p>=80?arr[2]:p>=70?arr[1]:arr[0];}

function drawMeter(id,val,color){const ctx=document.getElementById(id).getContext('2d');if(charts[id])charts[id].destroy();charts[id]=new Chart(ctx,{type:'doughnut',data:{datasets:[{data:[val,100-val],backgroundColor:[color,'#f0f0f0'],borderWidth:0,cutout:'70%'}]},options:{plugins:{legend:{display:false},tooltip:{enabled:false}},animation:{duration:1200}}});}

function kpiCard(title,icon,color,val,canvasId,stats){
    const card=document.createElement('div');
    const status=getStatus(val);
    card.className='kpi-card';
    card.innerHTML=`<div class="kpi-header"><div class="kpi-title"><div class="kpi-icon" style="background:${color}"><i class="${icon}"></i></div>${title}</div><div class="performance-badge ${status.cls}">${status.txt}</div></div><div class="meter-container"><canvas id="${canvasId}" width="120" height="120"></canvas><div class="meter-value">${val.toFixed(1)}%</div></div><div class="kpi-stats">${stats.map(s=>`<div class="stat-item"><span class="stat-number">${s.value}</span><p class="stat-label">${s.label}</p></div>`).join('')}</div>`;
    return card;
}

function renderAssignment(data){data.forEach(item=>{const val=parseFloat(item['Performance%'])||0;const id="assign_"+uniqueID();kpiContainer.appendChild(kpiCard('Assignment Performance','bi-person-check','#3498db',val,id,[{label:'Total Tickets',value:item.TotalTickets},{label:'Within 1 Hour',value:item.Within1Hour},{label:'After 1 Hour',value:item.After1Hour},{label:'Compliance',value:Math.round((item.Within1Hour/item.TotalTickets)*100)+'%'}]));drawMeter(id,val,getColor(val,true));});}
function renderQuotation(data){data.forEach(item=>{const val=parseFloat(item['Performance%'])||0;const id="quote_"+uniqueID();kpiContainer.appendChild(kpiCard('Quotation Performance','bi-file-earmark-text','#e74c3c',val,id,[{label:'Total Tickets',value:item.TotalTickets},{label:'Within 48H',value:item.Within48Hour},{label:'After 48H',value:item.After48Hour},{label:'Pending',value:item.Pending},{label:'AMC Tickets',value:item.AmcTickets||0},{label:'SLA Breach',value:item.NotApprovedIn48Hour||0}]));drawMeter(id,val,getColor(val,false));});}
function renderClosed(data){data.forEach(item=>{const val=parseFloat(item['Performance%'])||0;const id="closed_"+uniqueID();kpiContainer.appendChild(kpiCard('Ticket Closing Performance','bi-check2-circle','#2ecc71',val,id,[{label:'Available Tickets',value:item.AvailableTickets},{label:'Closing Target',value:item.ClosingTarget},{label:'Closed Tickets',value:item.ClosedTickets},{label:'Closed <24H',value:item.ClosedWithin24Hour},{label:'Closed >24H',value:item.ClosedAfter24Hour},{label:'Days in Range',value:item.DaysInRange}]));drawMeter(id,val,getColor(val,false));});}
function renderAttendance(data){data.forEach(item=>{const val=parseFloat(item['Performance%'])||0;const id="attend_"+uniqueID();kpiContainer.appendChild(kpiCard('Attendance Performance','bi-calendar-check','#8e44ad',val,id,[{label:'Working Days',value:item.WorkingDays},{label:'Present Days',value:item.PresentDays},{label:'Absent Days',value:item.AbsentDays}]));drawMeter(id,val,getColor(val,true));});}
function renderSalary(full,payable,perf){const id="salary_"+uniqueID();kpiContainer.appendChild(kpiCard('Salary Adjustment','bi-cash-stack','#f1c40f',perf,id,[{label:'Payable',value:payable}]));drawMeter(id,perf,'#f1c40f');}
// function renderEmployeeDetails(data){const container=document.getElementById("employeeDetails");container.innerHTML=`<div class="alert alert-info">Employee: ${data.name} | Designation: ${data.designation} | Branch: ${data.branch}</div>`;}
function renderEmployeeDetails(data){
    const container = document.getElementById("employeeDetails");
    const designation = data.Designation || data.designation || '';
    currentEmployeeDesignation = designation; // store globally

    container.innerHTML = `
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <h5 class="mb-1">${data.Name || data.name || '-'}</h5>
                <p class="mb-0"><strong>Role:</strong> ${designation || 'Employee'}</p>
                <p class="mb-0"><strong>Email:</strong> ${data.Email || data.email || '-'}</p>
                <p class="mb-0"><strong>Phone:</strong> ${data.ContactNumber || data.contactNumber || '-'}</p>
            </div>
        </div>
    `;
}



function toggleCanvas() {
    document.getElementById('filterCanvas').classList.toggle('show');
}
document.querySelector('.filter-btn').addEventListener('click', toggleCanvas);


function isExecutiveDesignation(designation) {
    if (!designation) return false;
    const keywords = [
        'executive',
        'sr. executive',
        'senior helpdesk',
        'purchase executive',
        'web developer',
        'app developer',
        'mis',
        'hr',
        'finance',
        'projectmanager',
        'asst.manager'
    ];
    designation = designation.toLowerCase();
    return keywords.some(word => designation.includes(word));
}


const empIdVal = $("#employee").val();
if (empIdVal) {
    fetch("ajax/get_employee_details.php?employee=" + empIdVal)
    .then(r => r.json())
    .then(emp => {
        if (emp.success) {
            renderEmployeeDetails(emp.data);
            loadKPIs(); 
        } else {
            document.getElementById("employeeDetails").innerHTML = "";
        }
    });
} else {
    loadKPIs();
}


</script>
</body>
</html>
