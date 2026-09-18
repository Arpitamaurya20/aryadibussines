<?php session_start();?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="description" content="Aryadibusiness Analytics Dashboard">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no, minimal-ui">
    
    <?php
    include('../includes/common_head_content.php');
    include('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    $core = new Core();
    $core->setTimeZone();
    
    // Load employees for the dropdown
    $employees = $conn->query("SELECT ID, Name FROM employees WHERE IsActive=1 ORDER BY Name ASC")->fetch_all(MYSQLI_ASSOC);
    ?>

    <style>
    /* Custom CSS for the KPI Dashboard */
    .kpi-dashboard {
        padding: 20px;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .kpi-card {
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .kpi-card h3 {
        color: #5a5c69;
        font-size: 1.1rem;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .kpi-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1cc88a;
        text-align: center;
        margin: 20px 0;
    }

    .kpi-description {
        color: #858796;
        text-align: center;
        font-size: 0.9rem;
    }

    .chart-container {
        height: 200px;
        margin: 15px 0;
    }

    .filters-section {
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .filter-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #5a5c69;
    }

    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d3e2;
        border-radius: 4px;
        font-size: 14px;
    }

    .filter-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-primary {
        background: #4e73df;
        color: white;
    }

    .btn-primary:hover {
        background: #2e59d9;
    }

    .btn-secondary {
        background: #858796;
        color: white;
    }

    .btn-secondary:hover {
        background: #6c757d;
    }

    .btn-success {
        background: #1cc88a;
        color: white;
    }

    .btn-success:hover {
        background: #17a673;
    }

    .charts-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .raw-data {
        background: #f8f9fc;
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
    }

    .raw-data summary {
        cursor: pointer;
        font-weight: 600;
        color: #5a5c69;
        margin-bottom: 15px;
    }

    .raw-data ul {
        list-style: none;
        padding: 0;
    }

    .raw-data li {
        padding: 8px 0;
        border-bottom: 1px solid #e3e6f0;
        color: #858796;
    }

    .raw-data li:last-child {
        border-bottom: none;
    }

    .raw-data strong {
        color: #5a5c69;
    }

    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
        .kpi-grid {
            grid-template-columns: 1fr;
        }
        
        .charts-row {
            grid-template-columns: 1fr;
        }
        
        .filters-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>

</head>

<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
    <input type="hidden" id="UserType" value="<?php echo $UserType;?>" />
    
    <!-- BEGIN Page Wrapper -->
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <!-- BEGIN Page Header -->
                <?php include('../includes/common_header.php'); ?>
                <!-- END Page Header -->
                
                <!-- BEGIN Page Content -->
                <main id="js-page-content" role="main" class="page-content">
                    
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
                        <li class="breadcrumb-item active">Analytics Dashboard</li>
                    </ol>
                    
                    <div class="kpi-dashboard">
                        <div class="d-sm-flex align-items-center justify-content-between mb-4">
                            <h1 class="h3 mb-0 text-gray-800">Technician KPI Dashboard</h1>
                            <small class="text-muted">Performance metrics: Assignment, Resolution, Quotation, Productivity, Attendance, Salary</small>
                        </div>

                        <!-- Filters -->
                        <div class="filters-section">
                            <form id="filterForm" onsubmit="return false;">
                                <div class="filters-grid">
                                    <div class="filter-group">
                                        <label for="employee">Employee</label>
                                        <select id="employee" class="form-control">
                                            <option value="0">Select employee</option>
                                            <?php foreach($employees as $e): ?>
                                                <option value="<?=htmlspecialchars($e['ID'])?>"><?=htmlspecialchars($e['Name'])?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="filter-group">
                                        <label for="period">Period</label>
                                        <select id="period" class="form-control" onchange="updateDateFields()">
                                            <option value="day">Today</option>
                                            <option value="week">This Week</option>
                                            <option value="month" selected>This Month</option>
                                            <option value="year">This Year</option>
                                            <option value="custom">Custom</option>
                                        </select>
                                    </div>
                                    <div class="filter-group">
                                        <label for="start">Start Date (Custom)</label>
                                        <input type="date" id="start" class="form-control">
                                    </div>
                                    <div class="filter-group">
                                        <label for="end">End Date (Custom)</label>
                                        <input type="date" id="end" class="form-control">
                                    </div>
                                </div>
                                <div class="filter-buttons">
                                    <button type="button" onclick="loadKPI()" class="btn btn-primary" id="generateBtn">
                                        <i class="fa fa-chart-line"></i> Generate Report
                                    </button>
                                    <button type="button" onclick="quick('day')" class="btn btn-secondary">Today</button>
                                    <button type="button" onclick="quick('week')" class="btn btn-secondary">Week</button>
                                    <button type="button" onclick="quick('month')" class="btn btn-secondary">Month</button>
                                    <button type="button" onclick="quick('year')" class="btn btn-secondary">Year</button>
                                </div>
                            </form>
                        </div>

                        <!-- KPI tiles -->
                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <h3>Ticket Assignment Compliance</h3>
                                <div id="gaugeAssign" class="chart-container"></div>
                                <div class="kpi-value" id="assignVal">0%</div>
                                <div class="kpi-description">Tickets accepted within 2 hours</div>
                            </div>
                            <div class="kpi-card">
                                <h3>Ticket Resolution SLA</h3>
                                <div id="gaugeSLA" class="chart-container"></div>
                                <div class="kpi-value" id="slaVal">0%</div>
                                <div class="kpi-description">Tickets resolved within 2 days</div>
                            </div>
                            <div class="kpi-card">
                                <h3>Quotation Approval Rate</h3>
                                <div id="gaugeQuote" class="chart-container"></div>
                                <div class="kpi-value" id="quoteVal">0%</div>
                                <div class="kpi-description">Approved within 2 days</div>
                            </div>
                            <div class="kpi-card">
                                <h3>Attendance Compliance</h3>
                                <div id="gaugeAtt" class="chart-container"></div>
                                <div class="kpi-value" id="attVal">0%</div>
                                <div class="kpi-description">Presence during period</div>
                            </div>
                        </div>

                        <div class="charts-row">
                            <div class="kpi-card">
                                <h3>Productivity</h3>
                                <div id="prodBar" class="chart-container"></div>
                                <div class="kpi-description" id="prodText">Actual vs Target</div>
                            </div>
                            <div class="kpi-card">
                                <h3>Salary (Performance-linked)</h3>
                                <div id="salaryRadial" class="chart-container"></div>
                                <div class="kpi-value" id="salaryFinal">₹0</div>
                                <div class="kpi-description">
                                    Base: <span id="salaryBase">₹0</span> • KPI: <span id="kpiFinal">0%</span>
                                </div>
                                <div id="salaryNote" class="alert alert-warning" style="display:none; margin-top: 15px;"></div>
                            </div>
                        </div>

                        <div class="raw-data">
                            <details>
                                <summary>Raw Performance Data</summary>
                                <div id="rawBox" style="margin-top:15px; font-size:14px"></div>
                            </details>
                        </div>

                        <div class="alert alert-info" style="margin-top: 20px;">
                            <strong>Tip:</strong> Tune weights & targets inside the PHP code to customize KPI calculations.
                        </div>
                    </div>
                </main>
                <!-- END Page Content -->
            </div>
        </div>
    </div>

    <?php include(__DIR__ . '/kpi_pop_up.php'); ?>

    <!-- Charts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Chart instances
        let cAssign, cSLA, cQuote, cAtt, cProd, cSalary;

        // Simple chart factories
        function radial(el, val, label){
            return new ApexCharts(document.querySelector(el), {
                chart:{ 
                    type:'radialBar', 
                    height:180, 
                    sparkline:{enabled:true}
                },
                series:[Math.max(0, Math.min(100, val||0))],
                labels:[label],
                plotOptions:{ 
                    radialBar:{ 
                        hollow:{size:'62%'}, 
                        dataLabels:{ 
                            value:{formatter: v=>v.toFixed(0)+'%'} 
                        } 
                    } 
                }
            });
        }
        
        function bar(el, actual, target){
            return new ApexCharts(document.querySelector(el), {
                chart:{ type:'bar', height:200 },
                series:[
                    {name:'Actual', data:[actual]},
                    {name:'Target', data:[target]}
                ],
                xaxis:{categories:['Tickets per period']},
                plotOptions:{bar:{borderRadius:8, columnWidth:'45%'}},
                dataLabels:{enabled:true}
            });
        }

        function showLoading() {
            document.querySelector('.kpi-dashboard').classList.add('loading');
            const btn = document.getElementById('generateBtn');
            btn.innerHTML = '<span class="spinner"></span> Loading...';
            btn.disabled = true;
        }

        function hideLoading() {
            document.querySelector('.kpi-dashboard').classList.remove('loading');
            const btn = document.getElementById('generateBtn');
            btn.innerHTML = '<i class="fa fa-chart-line"></i> Generate Report';
            btn.disabled = false;
        }

        // Function to update date fields based on period selection
        function updateDateFields() {
            const period = document.getElementById('period').value;
            const startField = document.getElementById('start');
            const endField = document.getElementById('end');
            
            const today = new Date();
            let startDate, endDate;
            
            switch(period) {
                case 'day':
                    startDate = today;
                    endDate = today;
                    break;
                case 'week':
                    // Monday to Sunday
                    const day = today.getDay();
                    const diff = today.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
                    startDate = new Date(today.setDate(diff));
                    endDate = new Date(startDate);
                    endDate.setDate(startDate.getDate() + 6);
                    break;
                case 'month':
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    break;
                case 'year':
                    startDate = new Date(today.getFullYear(), 0, 1);
                    endDate = new Date(today.getFullYear(), 11, 31);
                    break;
                case 'custom':
                    // Keep current custom dates
                    return;
            }
            
            startField.value = startDate.toISOString().split('T')[0];
            endField.value = endDate.toISOString().split('T')[0];
        }

        function loadKPI(){
            const employee = document.getElementById('employee').value;
            const period   = document.getElementById('period').value;
            const start    = document.getElementById('start').value;
            const end      = document.getElementById('end').value;

            if (employee == 0) {
                alert('Please select an employee first');
                return;
            }

            showLoading();

            // Use the separate AJAX handler
            const params = new URLSearchParams({ajax:1, employee, period, start, end});
            
            fetch('kpi_ajax_handler.php?' + params.toString())
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if(!data.ok){ 
                        alert(data.msg||'Failed to load data'); 
                        return; 
                    }

                    console.log('KPI Data received:', data); // Debug log

                    // Update KPI values
                    document.getElementById('assignVal').innerText = (data.kpi.assignment_compliance||0).toFixed(1) + '%';
                    document.getElementById('slaVal').innerText     = (data.kpi.resolution_sla||0).toFixed(1) + '%';
                    document.getElementById('quoteVal').innerText   = (data.kpi.quotation_approval||0).toFixed(1) + '%';
                    document.getElementById('attVal').innerText     = (data.kpi.attendance||0).toFixed(1) + '%';

                    // Destroy existing charts
                    if(cAssign) cAssign.destroy();
                    if(cSLA)    cSLA.destroy();
                    if(cQuote)  cQuote.destroy();
                    if(cAtt)    cAtt.destroy();

                    // Create new charts
                    cAssign = radial('#gaugeAssign', data.kpi.assignment_compliance, 'Assign ≤2h'); 
                    cAssign.render();
                    cSLA    = radial('#gaugeSLA', data.kpi.resolution_sla, 'Resolve ≤2d'); 
                    cSLA.render();
                    cQuote  = radial('#gaugeQuote', data.kpi.quotation_approval, 'Quote ≤2d'); 
                    cQuote.render();
                    cAtt    = radial('#gaugeAtt', data.kpi.attendance, 'Attendance'); 
                    cAtt.render();

                    // Productivity chart
                    const actual = data.kpi.productivity_detail.closed;
                    const target = data.kpi.productivity_detail.target_scaled;
                    document.getElementById('prodText').innerText =
                        `Closed: ${actual} • Target: ${target} • Daily-rule met: ${data.kpi.productivity_detail.daily_rule_rate}%`;
                    
                    if(cProd) cProd.destroy();
                    cProd = bar('#prodBar', actual, target); 
                    cProd.render();

                    // Salary & final KPI
                    document.getElementById('salaryBase').innerText  = '₹'+Number(data.salary.base).toLocaleString();
                    document.getElementById('kpiFinal').innerText    = (data.kpi.final_kpi||0).toFixed(1)+'%';
                    document.getElementById('salaryFinal').innerText = '₹'+Number(data.salary.final).toLocaleString();
                    
                    const salaryNote = document.getElementById('salaryNote');
                    if (data.salary.note) {
                        salaryNote.style.display = 'block';
                        salaryNote.innerText = data.salary.note;
                    } else {
                        salaryNote.style.display = 'none';
                    }

                    if(cSalary) cSalary.destroy();
                    cSalary = new ApexCharts(document.querySelector('#salaryRadial'), {
                        chart:{type:'radialBar', height:200},
                        series:[Math.min(120, data.salary.factor*100)],
                        labels:['Salary Factor %'],
                        plotOptions:{ 
                            radialBar:{ 
                                hollow:{size:'60%'}, 
                                dataLabels:{ 
                                    value:{formatter:v=>v.toFixed(0)+'%'} 
                                } 
                            } 
                        }
                    });
                    cSalary.render();

                    // Raw numbers
                    document.getElementById('rawBox').innerHTML =
                        `<ul>
                           <li><strong>Period:</strong> ${data.period.start} → ${data.period.end} (${data.period.days} days)</li>
                           <li><strong>Total tickets:</strong> ${data.raw.total_tickets} • <strong>Closed:</strong> ${data.raw.closed_total}</li>
                           <li><strong>Assignment≤2h:</strong> ${data.kpi.assignment_compliance}% • <strong>Quote≤2d:</strong> ${data.kpi.quotation_approval}% • <strong>Resolve≤2d:</strong> ${data.kpi.resolution_sla}%</li>
                           <li><strong>Attendance:</strong> ${data.kpi.attendance}% (Present days: ${data.raw.present_days})</li>
                           <li><strong>Productivity:</strong> ${data.kpi.productivity}% (Daily-rule OK ${data.raw.days_rule_ok}/${data.raw.days_rule_total})</li>
                           <li><strong>Final KPI:</strong> ${data.kpi.final_kpi}%</li>
                           ${data.debug ? `<li><strong>Debug:</strong> Start: ${data.debug.start_date}, End: ${data.debug.end_date}, Corp: ${data.debug.corp_total}, PPM: ${data.debug.ppm_total}</li>` : ''}
                         </ul>`;
                })
                .catch(e=>{
                    console.error('Error:', e);
                    alert('Error loading data: ' + e.message);
                })
                .finally(() => {
                    hideLoading();
                });
        }

        function quick(p){
            document.getElementById('period').value = p;
            updateDateFields(); // Update dates when quick button is clicked
            loadKPI();
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial dates based on current month
            updateDateFields();
            
            // Auto-load data for first employee if available
            const employeeSelect = document.getElementById('employee');
            if (employeeSelect.options.length > 1) {
                employeeSelect.selectedIndex = 1; // Select first employee (skip "Select employee")
                loadKPI(); // Auto-load data
            }
        });
    </script>

    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <?php include(__DIR__ . '/kpi_pop_up.php'); ?>
    <script>
    $(document).ready(function() {
        $("#js-nav-menu").addClass("active");
        $("#js-nav-menu").addClass("open");
        $("#nav_analytics_dashboard").addClass("active");
        
        if($("#corporate_name").length) {
            $("#corporate_name").select2();
        }
        
        $('#filter_date').daterangepicker({
            locale: {
                format: 'YYYY-MM-DD'
            }
        });

        $('#ad_branch_name').on('select2:select', function (e) {
            $(this).select2('close');
        });
    }); 
    </script>
</body>
</html>