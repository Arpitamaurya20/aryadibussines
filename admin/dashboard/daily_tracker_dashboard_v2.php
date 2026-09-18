<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="description" content="Aryadibusiness Analytics Dashboard - Interactive Charts">
    <?php
    include('../includes/common_head_content.php');
    include('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    $core = new Core();
    $core->setTimeZone();
    $corporate_array = _getTableRecords($conn,'company','where 1');
    $current_date = date("Y-m-d");
    $previous_date =  date('Y-m-d', strtotime('-30 days'));
    $data = array();
    $data['start_date'] = $previous_date;
    $data['end_date'] = $current_date;
    $date_range = $previous_date . " - " . $current_date;
    $CorporateID = -1;
    $BranchID = -1;
    $techx_admin = true;
    if($UserType == "Corporate Branch User")
    {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
        $BranchID = $_SESSION['Roles']['BranchID'];
    }
    if($UserType == "Corporate Admin")
    {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
    }
    $corporate_ticket_obj = new Corporateticket($conn);
    $status_array = $corporate_ticket_obj->getCorporateTicketStatusArray("All");
    $data['CorporateID'] = $CorporateID;
    $daily_tracker_status = $corporate_ticket_obj->GetDailyTicketStatsbyStatus($data);

    $ProductName = "Aryadibusiness";
    if ($CorporateID == 183) 
    {
        $_product = "innov";
        $dbh = new Dbh();
        $conn = $dbh->_connectodb();
        $conf = new Config($conn);
        $product_configuration = $conf->GetConfigParametersfromURL($_product);
        $ProductName = $product_configuration['ProductName'];
    } 
    $logoImg = "tech-logo.jpg";
    if(isset($product_configuration['logo']))
    {
        $logoImg = "innov_logo.png";
    }
    ?>
    <title><?=$ProductName;?> Daily Tracker - Interactive Charts</title>
    
    <!-- Chart.js and additional libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    
    <!-- Date Range Picker -->
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    
    <!-- Custom Styles -->
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: none;
        }
        
        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .btn-modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .form-control-modern {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control-modern:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .legend-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
        }
        
        .trend-indicator {
            font-size: 0.875rem;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            display: inline-block;
        }
        
        .trend-up {
            background: #d4edda;
            color: #155724;
        }
        
        .trend-down {
            background: #f8d7da;
            color: #721c24;
        }
        
        .trend-stable {
            background: #fff3cd;
            color: #856404;
        }
    </style>
    
    <?php 
    if(isset($product_configuration['favicon']))
    {
        ?>
        <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/<?=$product_configuration['favicon'];?>">
        <?php
    }
    // if($ProductName != "Aryadibusiness")
    {
        include("../css/client_generated_css.php");
    }
    ?>
</head>

<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
    <input type="hidden" name="UserType" id="UserType" value="<?php echo $UserType;?>">
    <input type="hidden" name="CorporateID" id="CorporateID" value="<?php echo $CorporateID;?>">
    
    <!-- DOC: script to save and load page settings -->
    <?php include('../js/theme_settings.js'); ?>
    
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
                        <li class="breadcrumb-item"><a href="javascript:void(0);"><?=$ProductName;?></a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Daily Tracker</a></li>
                        <li class="breadcrumb-item active">Interactive Charts</li>
                    </ol>
                    
                    <!-- Filter Section -->
                    <div class="filter-section">
                        <div class="row align-items-center">
                            <?php if($CorporateID == -1): ?>
                            <div class="col-md-4">
                                <label class="form-label">Select Corporate</label>
                                <select class="form-control form-control-modern" id="corporate_name" name="corporate_name">
                                    <option value="-1">All Corporates</option>
                                    <?php foreach($corporate_array as $corporate): ?>
                                        <option value="<?php echo $corporate['ID'];?>">
                                            <?php echo $corporate['CompanyName'];?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-md-4">
                                <label class="form-label">Date Range</label>
                                <input type="text" class="form-control form-control-modern" id="filter_date" placeholder="Select date range" value="<?php echo $date_range; ?>">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-modern w-100" onclick="loadChartData()">
                                    <i class="fas fa-search"></i> Update Charts
                                </button>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-outline-secondary w-100" onclick="exportCharts()">
                                    <i class="fas fa-download"></i> Export
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Summary Statistics -->
                    <div class="row" id="summary-stats">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number" id="total-tickets">0</div>
                                <div class="stats-label">Total Tickets</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number" id="avg-daily">0</div>
                                <div class="stats-label">Avg Daily</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number" id="peak-day">0</div>
                                <div class="stats-label">Peak Day</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number" id="active-status">0</div>
                                <div class="stats-label">Active Status</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Section -->
                    <div class="row">
                        <!-- Line Chart - Daily Trends -->
                        <div class="col-lg-8">
                            <div class="chart-card">
                                <h5 class="chart-title">Daily Ticket Trends</h5>
                                <div class="chart-container">
                                    <canvas id="dailyTrendsChart"></canvas>
                                </div>
                                <div class="legend-container" id="line-chart-legend"></div>
                            </div>
                        </div>
                        
                        <!-- Pie Chart - Status Distribution -->
                        <div class="col-lg-4">
                            <div class="chart-card">
                                <h5 class="chart-title">Status Distribution</h5>
                                <div class="chart-container">
                                    <canvas id="statusDistributionChart"></canvas>
                                </div>
                                <div class="legend-container" id="pie-chart-legend"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Bar Chart - Status Comparison -->
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <h5 class="chart-title">Status Comparison</h5>
                                <div class="chart-container">
                                    <canvas id="statusComparisonChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Area Chart - Cumulative Trends -->
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <h5 class="chart-title">Cumulative Trends</h5>
                                <div class="chart-container">
                                    <canvas id="cumulativeTrendsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detailed Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="chart-card">
                                <h5 class="chart-title">Detailed Data Table</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="detailed-table">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Date</th>
                                                <?php foreach($status_array as $status): ?>
                                                    <th><?php echo $status['Status'];?></th>
                                                <?php endforeach; ?>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="table-body">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                
                <!-- this overlay is activated only when mobile menu is triggered -->
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <!-- END Page Content -->
                
                <!-- BEGIN Page Footer -->
                <?php include('../includes/common_footer.php') ?>
                <!-- END Page Footer -->
            </div>
        </div>
    </div>
    
    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    
    <script>
    // Global variables
    let dailyTrendsChart, statusDistributionChart, statusComparisonChart, cumulativeTrendsChart;
    let chartData = {};
    
    // Color palette for charts
    const chartColors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
        '#4BC0C0', '#FF6384', '#36A2EB', '#FFCE56'
    ];
    
    $(document).ready(function() {
        // Initialize Select2
        if($("#corporate_name").length) {
            $("#corporate_name").select2({
                placeholder: "Select Corporate",
                allowClear: true
            });
        }
        
        // Initialize Date Range Picker
        $('#filter_date').daterangepicker({
            locale: {
                format: 'YYYY-MM-DD',
                separator: ' - ',
                applyLabel: 'Apply',
                cancelLabel: 'Cancel',
                fromLabel: 'From',
                toLabel: 'To',
                customRangeLabel: 'Custom',
                daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
                monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                firstDay: 1
            },
            startDate: moment().subtract(30, 'days'),
            endDate: moment(),
            ranges: {
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });
        
        // Set active navigation
        $("#nav_daily_tracker_dashboard").addClass("active");
        
        // Load initial data
        loadChartData();
    });
    
    function loadChartData() {
        const filter_date = document.getElementById("filter_date").value;
        const UserType = $("#UserType").val();
        let CorporateID = $("#CorporateID").val();
        
        if($("#corporate_name").length) {
            CorporateID = document.getElementById("corporate_name").value;
        }
        
        // Show loading state
        showLoading();
        
        $.post("ajax/get_daily_tracker_chart_data.php", {
            UserType: UserType,
            filter_date: filter_date,
            CorporateID: CorporateID
        }, function(data, status) {
            if(status === 'success') {
                try {
                    chartData = JSON.parse(data);
                    updateCharts();
                    updateSummaryStats();
                    updateDetailedTable();
                } catch(e) {
                    console.error('Error parsing chart data:', e);
                    hideLoading();
                }
            } else {
                console.error('Error loading chart data');
                hideLoading();
            }
        });
    }
    
    function updateCharts() {
        // Destroy existing charts
        if(dailyTrendsChart) dailyTrendsChart.destroy();
        if(statusDistributionChart) statusDistributionChart.destroy();
        if(statusComparisonChart) statusComparisonChart.destroy();
        if(cumulativeTrendsChart) cumulativeTrendsChart.destroy();
        
        // Create new charts
        createDailyTrendsChart();
        createStatusDistributionChart();
        createStatusComparisonChart();
        createCumulativeTrendsChart();
        
        hideLoading();
    }
    
    function createDailyTrendsChart() {
        const ctx = document.getElementById('dailyTrendsChart').getContext('2d');
        
        const datasets = chartData.statuses.map((status, index) => ({
            label: status,
            data: chartData.dates.map(date => chartData.data[date]?.[status] || 0),
            borderColor: chartColors[index % chartColors.length],
            backgroundColor: chartColors[index % chartColors.length] + '20',
            borderWidth: 2,
            fill: false,
            tension: 0.4
        }));
        
        dailyTrendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.dates,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        borderColor: 'rgba(255,255,255,0.2)',
                        borderWidth: 1
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day',
                            displayFormats: {
                                day: 'MMM dd'
                            }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
        
        // Create legend
        createLegend('line-chart-legend', chartData.statuses, chartColors);
    }
    
    function createStatusDistributionChart() {
        const ctx = document.getElementById('statusDistributionChart').getContext('2d');
        
        const totalByStatus = {};
        chartData.statuses.forEach(status => {
            totalByStatus[status] = chartData.dates.reduce((sum, date) => {
                return sum + (chartData.data[date]?.[status] || 0);
            }, 0);
        });
        
        const data = Object.values(totalByStatus);
        const labels = Object.keys(totalByStatus);
        
        statusDistributionChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: chartColors.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        
        // Create legend
        createLegend('pie-chart-legend', labels, chartColors.slice(0, labels.length));
    }
    
    function createStatusComparisonChart() {
        const ctx = document.getElementById('statusComparisonChart').getContext('2d');
        
        const totalByStatus = {};
        chartData.statuses.forEach(status => {
            totalByStatus[status] = chartData.dates.reduce((sum, date) => {
                return sum + (chartData.data[date]?.[status] || 0);
            }, 0);
        });
        
        statusComparisonChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(totalByStatus),
                datasets: [{
                    label: 'Total Tickets',
                    data: Object.values(totalByStatus),
                    backgroundColor: chartColors.slice(0, Object.keys(totalByStatus).length),
                    borderColor: chartColors.slice(0, Object.keys(totalByStatus).length),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: 'white',
                        bodyColor: 'white'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    function createCumulativeTrendsChart() {
        const ctx = document.getElementById('cumulativeTrendsChart').getContext('2d');
        
        const cumulativeData = {};
        chartData.statuses.forEach(status => {
            cumulativeData[status] = [];
            let cumulative = 0;
            chartData.dates.forEach(date => {
                cumulative += (chartData.data[date]?.[status] || 0);
                cumulativeData[status].push(cumulative);
            });
        });
        
        const datasets = chartData.statuses.map((status, index) => ({
            label: status,
            data: cumulativeData[status],
            borderColor: chartColors[index % chartColors.length],
            backgroundColor: chartColors[index % chartColors.length] + '40',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }));
        
        cumulativeTrendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.dates,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: 'white',
                        bodyColor: 'white'
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day',
                            displayFormats: {
                                day: 'MMM dd'
                            }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                }
            }
        });
    }
    
    function createLegend(containerId, labels, colors) {
        const container = document.getElementById(containerId);
        container.innerHTML = '';
        
        labels.forEach((label, index) => {
            const legendItem = document.createElement('div');
            legendItem.className = 'legend-item';
            legendItem.innerHTML = `
                <span class="status-indicator" style="background-color: ${colors[index]}"></span>
                <span>${label}</span>
            `;
            container.appendChild(legendItem);
        });
    }
    
    function updateSummaryStats() {
        const totalTickets = chartData.dates.reduce((sum, date) => {
            return sum + Object.values(chartData.data[date] || {}).reduce((a, b) => a + b, 0);
        }, 0);
        
        const avgDaily = totalTickets / chartData.dates.length;
        const peakDay = Math.max(...chartData.dates.map(date => 
            Object.values(chartData.data[date] || {}).reduce((a, b) => a + b, 0)
        ));
        const activeStatuses = chartData.statuses.length;
        
        document.getElementById('total-tickets').textContent = totalTickets.toLocaleString();
        document.getElementById('avg-daily').textContent = avgDaily.toFixed(1);
        document.getElementById('peak-day').textContent = peakDay;
        document.getElementById('active-status').textContent = activeStatuses;
    }
    
    function updateDetailedTable() {
        const tbody = document.getElementById('table-body');
        tbody.innerHTML = '';
        
        chartData.dates.forEach(date => {
            const row = document.createElement('tr');
            const dateData = chartData.data[date] || {};
            const total = Object.values(dateData).reduce((a, b) => a + b, 0);
            
            row.innerHTML = `
                <td><strong>${date}</strong></td>
                ${chartData.statuses.map(status => `<td>${dateData[status] || 0}</td>`).join('')}
                <td><strong>${total}</strong></td>
            `;
            tbody.appendChild(row);
        });
    }
    
    function showLoading() {
        // Add loading overlay
        if(!document.getElementById('loading-overlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255,255,255,0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
            `;
            overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>';
            document.body.appendChild(overlay);
        }
    }
    
    function hideLoading() {
        const overlay = document.getElementById('loading-overlay');
        if(overlay) {
            overlay.remove();
        }
    }
    
    function exportCharts() {
        // Export charts as images
        const charts = [dailyTrendsChart, statusDistributionChart, statusComparisonChart, cumulativeTrendsChart];
        const chartNames = ['Daily Trends', 'Status Distribution', 'Status Comparison', 'Cumulative Trends'];
        
        charts.forEach((chart, index) => {
            if(chart) {
                const link = document.createElement('a');
                link.download = `${chartNames[index]}_${new Date().toISOString().split('T')[0]}.png`;
                link.href = chart.toBase64Image();
                link.click();
            }
        });
        
        // Export data as CSV
        exportDataAsCSV();
    }
    
    function exportDataAsCSV() {
        let csv = 'Date,' + chartData.statuses.join(',') + ',Total\n';
        
        chartData.dates.forEach(date => {
            const dateData = chartData.data[date] || {};
            const total = Object.values(dateData).reduce((a, b) => a + b, 0);
            const row = [date, ...chartData.statuses.map(status => dateData[status] || 0), total];
            csv += row.join(',') + '\n';
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `daily_tracker_data_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
    }
    </script>
</body>
</html>