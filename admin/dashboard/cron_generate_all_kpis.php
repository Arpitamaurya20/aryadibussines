<?php
$role = $_GET['Role'] ?? '';
$employee = $_GET['Employee'] ?? '';
$date_from = $_GET['Datefrom'] ?? '';
$date_to = $_GET['Dateto'] ?? '';
// $EmployeeNumber=$_GET['EmployeeNumber']??'';
$EmployeeNumber="8948975967";
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
// $UserType = SessionCheck();

$conn = _connectodb();
$core = new Core();
$core->setTimeZone();

?>
<style>
body {font-family:'Poppins',sans-serif;background:#f6f7fb;}
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
</style>
</head>
<body>
<input type="hidden" id="UserType" value="<?php echo $UserType;?>">
<input type="hidden" id="LoggedEmployeeID" value="<?php echo $employeeID;?>">

<div class="page-wrapper">
    <div class="page-inner">
       
        <div class="page-content-wrapper">
           
            <main class="page-content">
                <div class="kpi-dashboard">
                    <div class="dashboard-header d-flex justify-content-between align-items-center">
                        <h1 class="dashboard-title"><i class="bi bi-speedometer2 text-primary"></i>Performance KPI Dashboard</h1>
                    </div>

                    <div id="employeeDetails" class="mb-4"></div>

                    <form id="filterForm" class="d-none">
                        <input type="hidden" name="role" value="<?php echo $role; ?>">
                        <input type="hidden" name="employee" value="<?php echo $employee; ?>">
                        <input type="hidden" name="date_from" value="<?php echo $date_from; ?>">
                        <input type="hidden" name="date_to" value="<?php echo $date_to; ?>">
                    </form>

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

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
const filterForm = document.getElementById("filterForm");
const params = new URLSearchParams(new FormData(filterForm)).toString();
const kpiContainer = document.getElementById('kpiContainer');
const statusContainer = document.getElementById('statusContainer');
let charts = {};

document.addEventListener("DOMContentLoaded", () => {
    if (params.includes("employee=") && params.includes("role=")) {
        loadKPIData();
    } else {
        statusContainer.innerHTML = `<div class="no-data-container"><i class="bi bi-funnel"></i><h5>Please provide Role & Employee in URL</h5></div>`;
    }
});

function loadKPIData() {
    showLoading();

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

        if (assign.success && assign.data.length) {
            renderAssignment(assign.data);
            percentages.push(avg(assign.data.map(x => parseFloat(x['Performance%'])||0)));
            anyData = true;
        }
        if (quote.success && quote.data.length) {
            renderQuotation(quote.data);
            percentages.push(avg(quote.data.map(x => parseFloat(x['Performance%'])||0)));
            anyData = true;
        }
        if (closed.success && closed.data) {
            renderClosed([closed.data]);
            percentages.push(parseFloat(closed.data['Performance%'])||0);
            anyData = true;
        }
        if (attendance.success && attendance.data) {
            renderAttendance([attendance.data]);
            percentages.push(parseFloat(attendance.data['Performance%'])||0);
            anyData = true;
        }

        if (percentages.length) {
            const overall = avg(percentages);
            fetch("ajax/get_employee_salary.php?employee=<?php echo $employee; ?>")
                .then(r => r.json())
                .then(sal => {
                    if(sal.success){
                        const fullSalary = parseFloat(sal.data.TotalSalary);
                        let payable = fullSalary;
                        if(overall < 85){
                            payable = (overall/100)*fullSalary;
                        }
                        renderSalary(fullSalary, payable, overall);
                    }

                    // ✅ Capture KPI image after all charts and salary rendered
                    captureKPIImage();
                });
        } else {
            captureKPIImage();
        }

        if (!anyData) showNoData();
        else statusContainer.innerHTML = '';

        fetch("ajax/get_employee_details.php?employee=<?php echo $employee; ?>")
        .then(r => r.json())
        .then(emp => { if (emp.success) renderEmployeeDetails(emp.data); });
    })
    .catch(err => showError(err));
}

// Helpers
function avg(arr){return arr.reduce((a,b)=>a+b,0)/arr.length;}
function uniqueID(){return Date.now()+Math.floor(Math.random()*10000);}
function getStatus(p){if(p>=90)return {txt:'Excellent',cls:'badge-excellent'};if(p>=80)return {txt:'Good',cls:'badge-good'};if(p>=70)return {txt:'Average',cls:'badge-average'};return {txt:'Poor',cls:'badge-poor'};}
function getColor(p){const arr=['#e74c3c','#f39c12','#3498db','#2ecc71'];return p>=90?arr[3]:p>=80?arr[2]:p>=70?arr[1]:arr[0];}
function showLoading(){kpiContainer.innerHTML='';statusContainer.innerHTML=`<div class="loading-container"><i class="bi bi-hourglass-split"></i><h5 class="mt-3">Loading Performance Data...</h5></div>`;}
function showNoData(){statusContainer.innerHTML=`<div class="no-data-container"><i class="bi bi-inbox mb-3"></i><h5>No Data Found</h5><p>Try different filters.</p></div>`;}
function showError(msg){statusContainer.innerHTML=`<div class="no-data-container text-danger"><i class="bi bi-exclamation-triangle mb-3"></i><h5>Error</h5><p>${msg}</p></div>`;}

function drawMeter(id,val,color){const ctx=document.getElementById(id).getContext('2d');if(charts[id])charts[id].destroy();charts[id]=new Chart(ctx,{type:'doughnut',data:{datasets:[{data:[val,100-val],backgroundColor:[color,'#f0f0f0'],borderWidth:0,cutout:'70%'}]},options:{plugins:{legend:{display:false},tooltip:{enabled:false}}}});}
function kpiCard(title,icon,color,val,canvasId,stats){const card=document.createElement('div');const status=getStatus(val);card.className='kpi-card';card.innerHTML=`<div class="kpi-header"><div class="kpi-title"><div class="kpi-icon" style="background:${color}"><i class="${icon}"></i></div>${title}</div><div class="performance-badge ${status.cls}">${status.txt}</div></div><div class="meter-container"><canvas id="${canvasId}" width="120" height="120"></canvas><div class="meter-value">${val.toFixed(1)}%</div></div><div class="kpi-stats">${stats.map(s=>`<div class="stat-item"><span class="stat-number">${s.value}</span><p class="stat-label">${s.label}</p></div>`).join('')}</div>`;return card;}
function renderAssignment(data){data.forEach(item=>{const val=parseFloat(item['Performance%'])||0;const id="assign_"+uniqueID();kpiContainer.appendChild(kpiCard('Assignment Performance','bi-person-check','#3498db',val,id,[{label:'Total Tickets',value:item.TotalTickets},{label:'Within 1 Hour',value:item.Within1Hour},{label:'After 1 Hour',value:item.After1Hour}]));drawMeter(id,val,getColor(val));});}
function renderQuotation(data){data.forEach(item=>{const val=parseFloat(item['Performance%'])||0;const id="quote_"+uniqueID();kpiContainer.appendChild(kpiCard('Quotation Performance','bi-file-earmark-text','#e74c3c',val,id,[{label:'Total Tickets',value:item.TotalTickets},{label:'Within 48H',value:item.Within48Hour},{label:'After 48H',value:item.After48Hour}]));drawMeter(id,val,getColor(val));});}
function renderClosed(data){data.forEach(item=>{const val=parseFloat(item['Performance%'])||0;const id="closed_"+uniqueID();kpiContainer.appendChild(kpiCard('Closing Performance','bi-check2-circle','#2ecc71',val,id,[{label:'Available',value:item.AvailableTickets},{label:'Closed',value:item.ClosedTickets}]));drawMeter(id,val,getColor(val));});}
function renderAttendance(data){data.forEach(item=>{const val=parseFloat(item['Performance%'])||0;const id="attend_"+uniqueID();kpiContainer.appendChild(kpiCard('Attendance','bi-calendar-check','#8e44ad',val,id,[{label:'Present',value:item.PresentDays},{label:'Absent',value:item.AbsentDays}]));drawMeter(id,val,getColor(val));});}
function renderSalary(full,payable,perf){const id="salary_"+uniqueID();kpiContainer.appendChild(kpiCard('Salary Adjustment','bi-cash-stack','#f1c40f',perf,id,[{label:'Payable',value:payable.toFixed(2)}]));drawMeter(id,perf,'#f1c40f');}
function renderEmployeeDetails(data){document.getElementById("employeeDetails").innerHTML = `<div class="card shadow-sm border-0 mb-3"><div class="card-body"><h5 class="mb-1">${data.Name || '-'}</h5><p class="mb-0"><strong>Role:</strong> ${data.Designation || 'Employee'}</p><p class="mb-0"><strong>Email:</strong> ${data.Email || '-'}</p><p class="mb-0"><strong>Phone:</strong> ${data.ContactNumber || '-'}</p></div></div>`;}

// ✅ New function to capture KPI image
function captureKPIImage(){
    if(kpiContainer && kpiContainer.children.length > 0){
     setTimeout(() => {
        html2canvas(kpiContainer).then(canvas => {
            const imageData = canvas.toDataURL('image/png');
            fetch('ajax/save_kpi.php', {
                method: 'POST',
                body: JSON.stringify({ image: imageData,employeeNumber:"<?php echo $EmployeeNumber; ?>", employeeID: "<?php echo $employee; ?>" }),
                headers: { 'Content-Type': 'application/json' }
            })
            .then(res => res.json())
            .then(data => { if(data.success){ console.log("KPI Image Saved: KPI_<?php echo $employee; ?>.png"); } });
        });

         }, 4000);
    }
}
</script>

</body>
</html>
