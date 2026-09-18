<?php 
@session_start(); 
$employeeID = $_SESSION['Roles']['EmployeeID'] ?? '';
?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

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
</style>

<body>
<input type="hidden" id="UserType" value="<?php echo $UserType;?>">
<input type="hidden" id="LoggedEmployeeID" value="<?php echo $employeeID;?>">

<div id="statusContainer"></div>
<div class="kpi-container" id="kpiContainer"></div>

<!-- Yesterday KPI Popup -->
<div class="modal fade" id="yesterdayKpiModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content rounded-4 shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="bi bi-bar-chart-fill me-1"></i> Your Yesterday’s KPI Performance
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="yesterdayKpiContainer" class="kpi-container"></div>
        <div id="yesterdayStatus" class="mt-3"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
const kpiContainer = document.getElementById('kpiContainer');
const statusContainer = document.getElementById('statusContainer');
let charts = {}; // Global chart storage

// Helpers
function showLoading(){kpiContainer.innerHTML='';statusContainer.innerHTML=`<div class="loading-container"><i class="bi bi-hourglass-split"></i><h5 class="mt-3">Loading Performance Data...</h5></div>`;}
function showNoData(){statusContainer.innerHTML=`<div class="no-data-container"><i class="bi bi-inbox mb-3"></i><h5>No Data Found</h5><p>Try different filters.</p></div>`;}
function showError(msg){statusContainer.innerHTML=`<div class="no-data-container text-danger"><i class="bi bi-exclamation-triangle mb-3"></i><h5>Error</h5><p>${msg}</p></div>`;}
function avg(arr){return arr.reduce((a,b)=>a+b,0)/arr.length;}
function uniqueID(){return Date.now()+Math.floor(Math.random()*10000);}
function getStatus(p){if(p>=90)return {txt:'Excellent',cls:'badge-excellent'};if(p>=80)return {txt:'Good',cls:'badge-good'};if(p>=70)return {txt:'Average',cls:'badge-average'};return {txt:'Poor',cls:'badge-poor'};}
function getColor(p,isAssign){const cAssign=['#e74c3c','#f39c12','#3498db','#2ecc71'];const cQuote=['#c0392b','#e67e22','#2980b9','#27ae60'];const arr=isAssign?cAssign:cQuote;return p>=90?arr[3]:p>=80?arr[2]:p>=70?arr[1]:arr[0];}

// Draw chart
function drawMeter(id,val,color){
    const ctx=document.getElementById(id).getContext('2d');
    if(charts[id]) charts[id].destroy();
    charts[id]=new Chart(ctx,{
        type:'doughnut',
        data:{datasets:[{data:[val,100-val],backgroundColor:[color,'#f0f0f0'],borderWidth:0,cutout:'70%'}]},
        options:{plugins:{legend:{display:false},tooltip:{enabled:false}},animation:{duration:1200}}
    });
}

// KPI Card
function kpiCard(title,icon,color,val,canvasId,stats){
    const card=document.createElement('div');
    const status=getStatus(val);
    card.className='kpi-card';
    card.innerHTML=`<div class="kpi-header"><div class="kpi-title"><div class="kpi-icon" style="background:${color}"><i class="${icon}"></i></div>${title}</div><div class="performance-badge ${status.cls}">${status.txt}</div></div><div class="meter-container"><canvas id="${canvasId}" width="120" height="120"></canvas><div class="meter-value">${val.toFixed(1)}%</div></div><div class="kpi-stats">${stats.map(s=>`<div class="stat-item"><span class="stat-number">${s.value}</span><p class="stat-label">${s.label}</p></div>`).join('')}</div>`;
    return card;
}

// Employee Details
function renderEmployeeDetails(data){
    const container = document.getElementById("employeeDetails");
    container.innerHTML = `
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <h5 class="mb-1">${data.Name || data.name || '-'}</h5>
                <p class="mb-0"><strong>Role:</strong> ${data.Designation || data.designation || 'Employee'}</p>
                <p class="mb-0"><strong>Email:</strong> ${data.Email || data.email || '-'}</p>
                <p class="mb-0"><strong>Phone:</strong> ${data.ContactNumber || data.contactNumber || '-'}</p>
            </div>
        </div>
    `;
}

// Yesterday KPI loader
document.addEventListener("DOMContentLoaded", function(){
    const userType = document.getElementById("UserType").value;
    const empID   = document.getElementById("LoggedEmployeeID").value;
    if(userType !== "Admin" && empID){ loadYesterdayKpi(empID); }
});

function loadYesterdayKpi(empID){
    const container = document.getElementById("yesterdayKpiContainer");
    const statusBox = document.getElementById("yesterdayStatus");
    container.innerHTML = `<div class="loading-container w-100"><i class="bi bi-hourglass-split"></i><h5 class="mt-2">Loading yesterday's performance...</h5></div>`;

    const d = new Date();
    d.setDate(d.getDate()-1);
    const yDate = d.toISOString().slice(0,10);
    const params = `employee=${empID}&date_from=${yDate}&date_to=${yDate}`;

    Promise.all([
        fetch("ajax/get_detailed_assignment_kpi.php?" + params).then(r=>r.json()),
        fetch("ajax/get_detailed_quatation_kpi.php?" + params).then(r=>r.json()),
        fetch("ajax/get_detailed_closed.php?" + params).then(r=>r.json()),
        fetch("ajax/get_detailed_attendance.php?" + params).then(r=>r.json()),
        fetch("ajax/get_employee_salary.php?employee=" + empID).then(r=>r.json())
    ])
    .then(([assign,quote,closed,attendance,salary])=>{
        container.innerHTML = "";
        statusBox.innerHTML = "";
        let percents = [];

        function addAssignment(d){
            const v = parseFloat(d['Performance%'])||0;
            const id = "assign_"+uniqueID();
            container.appendChild(kpiCard('Assignment Performance','bi-person-check','#3498db',v,id,[
                {label:'Total Tickets', value:d.TotalTickets},
                {label:'Within 1 Hour', value:d.Within1Hour},
                {label:'After 1 Hour', value:d.After1Hour},
                {label:'Compliance', value:Math.round((d.Within1Hour/d.TotalTickets)*100)+'%'}
            ]));
            drawMeter(id,v,getColor(v,true));
            percents.push(v);
        }

        function addQuotation(d){
            const v = parseFloat(d['Performance%'])||0;
            const id = "quote_"+uniqueID();
            container.appendChild(kpiCard('Quotation Performance','bi-file-earmark-text','#e74c3c',v,id,[
                {label:'Total Tickets', value:d.TotalTickets},
                {label:'Within 48H', value:d.Within48Hour},
                {label:'Pending', value:d.Pending},
                {label:'AMC Tickets', value:d.AmcTickets||0}
            ]));
            drawMeter(id,v,getColor(v,false));
            percents.push(v);
        }

        function addClosed(d){
            const v = parseFloat(d['Performance%'])||0;
            const id = "closed_"+uniqueID();
            container.appendChild(kpiCard('Ticket Closing Performance','bi-check2-circle','#2ecc71',v,id,[
                {label:'Closed Tickets', value:d.ClosedTickets},
                {label:'Closed <24H', value:d.ClosedWithin24Hour},
                {label:'Closed >24H', value:d.ClosedAfter24Hour}
            ]));
            drawMeter(id,v,getColor(v,false));
            percents.push(v);
        }

        function addAttendance(d){
            const v = parseFloat(d['Performance%'])||0;
            const id = "attend_"+uniqueID();
            container.appendChild(kpiCard('Attendance Performance','bi-calendar-check','#8e44ad',v,id,[
                {label:'Working Days', value:d.WorkingDays},
                {label:'Present Days', value:d.PresentDays},
                {label:'Absent Days', value:d.AbsentDays}
            ]));
            drawMeter(id,v,getColor(v,true));
            percents.push(v);
        }

        if(assign.success && assign.data.length) addAssignment(assign.data[0]);
        if(quote.success && quote.data.length) addQuotation(quote.data[0]);
        if(closed.success && closed.data) addClosed(closed.data);
        if(attendance.success && attendance.data) addAttendance(attendance.data);

        if(percents.length && salary.success){
            const overall = avg(percents);
            const full = parseFloat(salary.data.TotalSalary)||0;
            const payable = (overall < 85) ? (overall/100)*full : full;
            const id = "salary_"+uniqueID();
            container.appendChild(kpiCard('Salary on Yesterday’s Performance','bi-cash-stack','#f1c40f',overall,id,[
                {label:'Payable', value:`₹${payable.toLocaleString()}`}
            ]));
            drawMeter(id,overall,'#f1c40f');
        }

        if(container.children.length===0){
            statusBox.innerHTML = `<div class="no-data-container"><i class="bi bi-inbox"></i><h5>No data found for yesterday</h5></div>`;
        }

        new bootstrap.Modal(document.getElementById('yesterdayKpiModal')).show();
    })
    .catch(err=>{
        statusBox.innerHTML = `<div class="text-danger p-3"><i class="bi bi-exclamation-triangle"></i> Error: ${err}</div>`;
        new bootstrap.Modal(document.getElementById('yesterdayKpiModal')).show();
    });
}

function captureKpiPopup() {
    const popup = document.getElementById('yesterdayKpiModal'); // or any container
    html2canvas(popup, { scale: 2 }).then(canvas => {
        // Convert to image
        const imgData = canvas.toDataURL("image/png");

        // Option 1: Open in new tab
        const win = window.open();
        win.document.write('<img src="' + imgData + '">');

        // Option 2: Download automatically
        const a = document.createElement('a');
        a.href = imgData;
        a.download = 'KPI_Performance.png';
        a.click();
    });
}
new bootstrap.Modal(document.getElementById('yesterdayKpiModal')).show();
setTimeout(captureKpiPopup, 1000); // give 1 sec for charts to render
</script>


</body>
</html>
