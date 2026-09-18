<?php
/*
|--------------------------------------------------------------------------
| Ticket Financial Dashboard - FULL VERSION (MySQLi)
|--------------------------------------------------------------------------
| REQUIREMENTS:
|   $conn
|   $quotation_detail
|--------------------------------------------------------------------------
*/

if(!isset($quotation_detail['TicketID'])) {
    return;
}

$TicketID = (int)$quotation_detail['TicketID'];

/* =========================================================
   1️⃣ EXPECTED BUDGET
========================================================= */
$expected_budget = isset($quotation_detail['expectedbudget']) 
    ? $quotation_detail['expectedbudget'] 
    : 0;


/* =========================================================
   2️⃣ SUPER TOTAL FROM QUOTATION ITEMS (JOIN METHOD)
========================================================= */
$SuperTotal = 0;
$total_tax  = 0;

$query_items = "
    SELECT i.TotalPrice
    FROM corporate_ticket_quotation_items i
    INNER JOIN corporate_ticket_quotation q 
        ON i.QuotationID = q.ID
    WHERE q.TicketID = '$TicketID'
    AND i.IsActive = 1
    AND q.IsActive = 1
";

$result_items = mysqli_query($conn, $query_items);

if($result_items && mysqli_num_rows($result_items) > 0)
{
    while($row = mysqli_fetch_assoc($result_items))
    {
        $price = $row['TotalPrice'];
        $tax   = isset($row['Tax']) ? $row['Tax'] : 0;

        $SuperTotal += $price;

        if($tax > 0)
        {
            $total_tax += round((($price * $tax) / 100), 2);
        }
    }
}

$quotation_total = $SuperTotal + $total_tax;


/* =========================================================
   3️⃣ PAYMENT TOTAL
========================================================= */
$ticket_cost = 0;

$query_payment = "
    SELECT SUM(Amount) as total_payment
    FROM corporate_tickets_payment_details
    WHERE TicketID = '$TicketID'
    AND IsActive = 1
";

$result_payment = mysqli_query($conn, $query_payment);

if($result_payment && mysqli_num_rows($result_payment) > 0)
{
    $row = mysqli_fetch_assoc($result_payment);
    $ticket_cost = $row['total_payment'] ?? 0;
}


/* =========================================================
   4️⃣ CONVENIENCE TOTAL
========================================================= */
$convenience_total = 0;

$query_con = "
    SELECT SUM(ConvenienceAmount) as total_convenience
    FROM employee_convenience
    WHERE TicketID = '$TicketID'
    AND Status = 'Approved'
";

$result_con = mysqli_query($conn, $query_con);

if($result_con && mysqli_num_rows($result_con) > 0)
{
    $row = mysqli_fetch_assoc($result_con);
    $convenience_total = $row['total_convenience'] ?? 0;
}


/* =========================================================
   5️⃣ FINAL COST
========================================================= */
$final_actual_cost = $ticket_cost + $convenience_total;


/* =========================================================
   6️⃣ VARIANCE
========================================================= */
$variance = $expected_budget - $final_actual_cost;
$variance_percent = ($expected_budget > 0) 
    ? (($final_actual_cost / $expected_budget) * 100)
    : 0;
?>


<!-- =========================================================
     PROFESSIONAL DASHBOARD UI
========================================================= -->

<style>
.dashboard-wrapper {
    margin-top:40px;
}

.dashboard-title {
    font-weight:700;
    font-size:22px;
    margin-bottom:25px;
}

.kpi-card {
    border-radius:14px;
    padding:20px;
    color:#fff;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
    transition:0.3s ease-in-out;
}

.kpi-card:hover {
    transform:translateY(-5px);
}

.kpi-budget { background:linear-gradient(135deg,#667eea,#764ba2); }
.kpi-quotation { background:linear-gradient(135deg,#43cea2,#185a9d); }
.kpi-payment { background:linear-gradient(135deg,#f7971e,#ffd200); }
.kpi-convenience { background:linear-gradient(135deg,#ff416c,#ff4b2b); }

.kpi-card h6 {
    font-size:14px;
    opacity:0.9;
}

.kpi-card h4 {
    font-size:22px;
    font-weight:700;
    margin-top:8px;
}

.chart-card {
    background:#ffffff;
    border-radius:16px;
    padding:25px;
    box-shadow:0 10px 30px rgba(0,0,0,0.05);
    margin-top:30px;
}

.chart-container {
    position:relative;
    height:350px;
}

.variance-box {
    margin-top:30px;
    padding:25px;
    border-radius:16px;
    font-size:18px;
    font-weight:600;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
}
</style>



<div class="container dashboard-wrapper">

    <div class="dashboard-title">Financial Overview Dashboard</div>

    <!-- KPI CARDS -->
    <div class="row">
         <div class="col-md-3 mb-4">
            <div class="kpi-card kpi-quotation">
                <h6>Sale Cost</h6>
                <h4>₹ <?php echo number_format($quotation_total); ?></h4>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="kpi-card kpi-budget">
                <h6>Expected Budget</h6>
                <h4>₹ <?php echo number_format($expected_budget); ?></h4>
            </div>
        </div>

       

        <div class="col-md-3 mb-4">
            <div class="kpi-card kpi-payment">
                <h6>Payment Total</h6>
                <h4>₹ <?php echo number_format($ticket_cost); ?></h4>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="kpi-card kpi-convenience">
                <h6>Convenience Charges</h6>
                <h4>₹ <?php echo number_format($convenience_total); ?></h4>
            </div>
        </div>

    </div>


    <!-- BAR CHART -->
    <div class="chart-card">
        <h5 style="font-weight:600;margin-bottom:20px;">Budget vs Quotation vs Actual</h5>
        <div class="chart-container">
            <canvas id="budgetComparisonChart"></canvas>
        </div>
    </div>


    <!-- DOUGHNUT CHART -->
    <div class="chart-card">
        <h5 style="font-weight:600;margin-bottom:20px;">Cost Breakdown Analysis</h5>
        <div class="chart-container">
            <canvas id="costBreakdownChart"></canvas>
        </div>
    </div>


    <!-- VARIANCE -->
    <div class="variance-box 
        <?php echo ($variance >= 0) ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
        
        Budget Variance: ₹ <?php echo number_format($variance); ?>
        <br>
        <small><?php echo number_format($variance_percent,2); ?>% of budget utilized</small>
    </div>

</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

/* ================= PREMIUM BAR CHART ================= */
new Chart(document.getElementById('budgetComparisonChart'), {
    type: 'bar',
    data: {
        labels: ['Expected Budget','Quotation','Actual Cost'],
        datasets: [{
            label: 'Amount (₹)',
            data: [
                <?php echo $expected_budget; ?>,
                <?php echo $quotation_total; ?>,
                <?php echo $final_actual_cost; ?>
            ],
            backgroundColor: [
                '#6C63FF',
                '#00C9A7',
                '#FF6B6B'
            ],
            borderRadius: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio:false,
        plugins: {
            legend: { display:false }
        },
        scales: {
            y: {
                beginAtZero:true,
                grid: { color:'#f0f0f0' }
            },
            x: {
                grid: { display:false }
            }
        }
    }
});


/* ================= PREMIUM DOUGHNUT ================= */
new Chart(document.getElementById('costBreakdownChart'), {
    type: 'doughnut',
    data: {
        labels: ['Quotation Base','Tax','Payment','Convenience'],
        datasets: [{
            data: [
                <?php echo $SuperTotal; ?>,
                <?php echo $total_tax; ?>,
                <?php echo $ticket_cost; ?>,
                <?php echo $convenience_total; ?>
            ],
            backgroundColor: [
                '#6C63FF',
                '#F9C80E',
                '#00C9A7',
                '#FF6B6B'
            ],
            borderWidth:0
        }]
    },
    options: {
        responsive:true,
        maintainAspectRatio:false,
        cutout:'65%',
        plugins: {
            legend: {
                position:'bottom'
            }
        }
    }
});

</script>

