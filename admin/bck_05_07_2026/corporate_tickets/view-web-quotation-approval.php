<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>TechXpert - View Quotation</title>
    <meta name="description" content="TechXpert - View Quotation">
    <?php
    include('../includes/common_head_content.php');
    include('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    ?>

    <style>
        

/* =========================
   PROFESSIONAL PAGE FIX
========================= */

/* Container spacing */
.container {
    padding: 15px;
}

/* Logo responsive */
.container img {
    max-width: 100%;
    height: auto !important;
}

/* Top action buttons alignment */
@media (min-width: 768px) {
    .text-right {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
    }
}

/* Mobile Fix */
@media (max-width: 767px) {

    /* Stack header properly */
    .row {
        margin-left: 0;
        margin-right: 0;
    }

    .col-4 {
        flex: 0 0 100%;
        max-width: 100%;
        text-align: center !important;
        margin-bottom: 10px;
    }

    /* Buttons full width on mobile */
    .text-right {
        display: flex;
        flex-direction: column;
        align-items: stretch !important;
        gap: 8px;
        margin-top: 10px;
    }

    .text-right .btn {
        width: 100%;
        margin: 0 !important;
    }

    /* Table scroll fix */
    .table-responsive {
        border: none;
    }

    table th, table td {
        font-size: 13px;
        padding: 8px;
        white-space: nowrap;
    }

    /* Total section alignment */
    .table-clean {
        width: 100%;
    }

    .ml-sm-auto {
        margin-left: 0 !important;
    }

    .table-clean td h4 {
        font-size: 16px;
    }
}

/* Improve button look */
.btn-sm {
    padding: 6px 14px;
    font-weight: 500;
}

/* Make table more clean */
.table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.table tbody tr:hover {
    background-color: #f2f7ff;
}

/* Modal responsiveness */
@media (max-width: 576px) {
    .modal-dialog {
        margin: 10px;
    }
}

/* ==============================
   RESPONSIVE TABLE SCROLL FIX
============================== */

/* Force horizontal scroll */
.table-responsive {
    width: 100%;
    overflow-x: auto !important;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    border-radius: 6px;
}

/* Prevent column breaking */
.table-responsive table {
    min-width: 900px; /* adjust if needed */
    border-collapse: collapse;
}

/* Keep content in single line */
.table-responsive th,
.table-responsive td {
    white-space: nowrap;
    vertical-align: middle;
}

/* Smooth scroll bar style (optional professional look) */
.table-responsive::-webkit-scrollbar {
    height: 6px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #999;
}

/* Mobile improvements */
@media (max-width: 767px) {

    .table-responsive table {
        min-width: 750px;
    }

    .table-responsive {
        box-shadow: inset 0 -1px 0 #ddd;
    }

}

html, body {
    height: auto !important;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch !important;
}

.page-wrapper,
.page-inner,
.page-content-wrapper,
.page-content {
    height: auto !important;
    overflow: visible !important;
}
</style>
</head>

<body>

<?php include('../js/theme_settings.js'); ?>

<div class="page-wrapper">
<div class="page-inner">
<div class="page-content-wrapper" style="padding-left:1em !important;">

<main id="js-page-content" role="main" class="page-content" style="margin-top:0">

<?php

/* ===========================
   VALIDATE PARAMETERS
=========================== */

if (
    isset($_GET['QuotationID']) &&
    isset($_GET['EmployeeID']) &&
    isset($_GET['BranchState']) &&
    isset($_GET['IsStateApproval']) &&
    isset($_GET['IsFinanceApproval'])
) {

    $QuotationID = base64_decode (($_GET['QuotationID']));
    $EmployeeID = base64_decode (($_GET['EmployeeID']));
    $BranchState = base64_decode(($_GET['BranchState']));
    $CreatedBy ='';
    $IsStateApproval   = (base64_decode($_GET['IsStateApproval']) === "Yes") ? "Yes" : "No";
    $IsFinanceApproval = (base64_decode($_GET['IsFinanceApproval']) === "Yes") ? "Yes" : "No";
    $IsCfo             = (base64_decode($_GET['IsCfo']) === "Yes") ? "Yes" : "No";

    $dbh = new Dbh();
    $conn = $dbh->_connectodb();
    $corporateticket = new Corporateticket($conn);

    $employee_obj=new Employee($conn);
    $employee_details=$employee_obj->getemployeeDetailsByID($EmployeeID);
    $EmployeeName=$employee_details['Name'];
    $CreatedBy =$EmployeeName;

    $quotation_detail = $corporateticket->GetQuotationDetailbyID($QuotationID);
    if (!$quotation_detail) {
        echo "<h3>Invalid Quotation ID</h3>";
        exit;
    }


            $InternalTotal = 0;
            $CustomerTotal = 0;
            $PaymentTotal  = 0;
            $ExpectedBudget = 0;

            /* ---- Get TicketID from quotation ---- */
            $TicketID = intval($quotation_detail['TicketID']);

            /* ---- Expected Budget ---- */
            $ExpectedBudget = floatval($quotation_detail['expectedbudget']);

            /* ---- Finance Totals ---- */
            $q2 = mysqli_query($conn,"
                SELECT T_TotalPrice, C_TotalPrice 
                FROM corporate_tickets_finance 
                WHERE TicketID = '$TicketID'
            ");

            if($q2 && mysqli_num_rows($q2) > 0){
                while($row = mysqli_fetch_assoc($q2)){
                    $InternalTotal += floatval($row['T_TotalPrice']);
                    $CustomerTotal += floatval($row['C_TotalPrice']);
                }
            }

            /* ---- Payment / Conveyance ---- */
            $q3 = mysqli_query($conn,"
                SELECT Amount 
                FROM corporate_tickets_payment_details 
                WHERE TicketID = '$TicketID' AND IsActive = 1
            ");

            if($q3 && mysqli_num_rows($q3) > 0){
                while($row = mysqli_fetch_assoc($q3)){
                    $PaymentTotal += floatval($row['Amount']);
                }
            }

            /* ---- Final Calculations ---- */
            $QuotationTotal = $CustomerTotal;;
            // $Profit = $CustomerTotal - $InternalTotal;
            $Profit = $CustomerTotal -  $ExpectedBudget;
            

    $q_line_items = $corporateticket->GetQuotationLineItems($QuotationID);

    if (!empty($q_line_items)) {

?>

<div class="container">
     <input type="hidden" id="CreatedBy" value="<?php echo $CreatedBy; ?>" />
<input type="hidden" id="TicketQuotationID" value="<?php echo $QuotationID; ?>" />
 <div class="form-group" style="display:none;" id="expected-budget-div">
                                   
                                    <input 
                                        type="number"
                                        class="form-control"
                                        id="expected-budget"
                                        name="expected-budget"
                                        step="0.01"
                                        min="0"
                                        placeholder="e.g. 100000"
                                        value="<?php echo  $quotation_detail['expectedbudget']?>"
                                        required
                                    />
                                </div>
<input type="hidden" id="new_quotation_status" value="" />

<div class="row">
<div class="col-4">
<img width="112px" style="height:27px;" src="../img/tech-logo.jpg">
</div>

<div class="col-4"></div>

<?php if ($quotation_detail['QuotationStatus'] !== "Quote Approved") { ?>
<div class="col-4 text-right">

    <?php 
    $status = $quotation_detail['QuotationStatus'];
    ?>

    <!-- ================= STATE APPROVAL ================= -->
    <?php if ($IsStateApproval === "Yes") { ?>

        <?php if ($status !== "Quote Approved By State") { ?>
            <button type="button"
                    onclick="UpdateQuotationStatus('StateApproved');"
                    class="btn btn-sm btn-success ml-3 mr-3">
                Approve by State
            </button>
       
            <button type="button"
                    onclick="UpdateQuotationStatus('StateRejected');"
                    class="btn btn-sm btn-danger ml-1 mr-3">
                Reject by State
            </button>
        <?php } ?>

    <?php } ?>


<!-- ================= CFo APPROVAL ================= -->
    <?php if ($IsCfo === "Yes") { ?>

        <?php if ($status !== "Quote Approved") { ?>
            <button type="button"
                    onclick="UpdateQuotationStatus('Quote Approved');"
                    class="btn btn-sm btn-success ml-3 mr-3">
                Approve
            </button>
       
            <button type="button"
                    onclick="UpdateQuotationStatus('Quote Rejected by Client');"
                    class="btn btn-sm btn-danger ml-1 mr-3">
                Reject
            </button>
        <?php } ?>

    <?php } ?>






    <!-- ================= FINANCE APPROVAL ================= -->
    <?php if ($IsFinanceApproval === "Yes") { ?>

        <?php if ($status !== "FinanceApproved") { ?>
            <button type="button"
                    onclick="UpdateQuotationStatus('FinanceApproved');"
                    class="btn btn-sm btn-success ml-3 mr-3">
                Approve by Finance
            </button>
       

      
            <button type="button"
                    onclick="UpdateQuotationStatus('FinanceRejected');"
                    class="btn btn-sm btn-danger ml-1 mr-3">
                Reject by Finance
            </button>
        <?php } ?>

    <?php } ?>

</div>
<?php } ?>
</div>

<div class="row">
<div class="col-sm-12">
<div class="table-responsive">
<table class="table mt-2">
<thead>
<tr>
<th>#</th>
<th>Item</th>
<th>Category</th>
<th>Make</th>
<th>HSN</th>
<th>ARC Code</th>
<th>Unit Cost</th>
<th>Qty</th>
<th>Total</th>
</tr>
</thead>
<tbody>

<?php
$i = 1;
$SuperTotal = 0;

foreach ($q_line_items as $line_item) {

    $SuperTotal += $line_item['TotalPrice'];
?>

<tr>
<td><?php echo $i; ?></td>
<td><?php echo htmlspecialchars($line_item['LineItemName']); ?></td>
<td><?php echo htmlspecialchars($line_item['Category']); ?></td>
<td><?php echo htmlspecialchars($line_item['Make']); ?></td>
<td><?php echo htmlspecialchars($line_item['HSN']); ?></td>
<td><?php echo htmlspecialchars($line_item['ARCCode']); ?></td>
<td><?php echo $line_item['PerItemPrice']; ?></td>
<td><?php echo $line_item['Qty']; ?></td>
<td>₹<?php echo $line_item['TotalPrice']; ?></td>
</tr>

<?php
$i++;
}
?>

</tbody>
</table>
</div>
</div>
</div>

<div class="row">
<div class="col-sm-4 ml-sm-auto">
<table class="table table-clean">
<tbody>
<tr>
<td><h4>Total</h4></td>
<td class="text-right">
<h4>₹<?php echo $SuperTotal; ?></h4>
</td>
</tr>
</tbody>
</table>


</div>
</div>

</div>

<?php
    } else {
        echo "<h3>No Line Items Found</h3>";
    }

} else {
    echo "<h2>Sorry Invalid Page</h2>";
}
?>

</main>

<hr class="mt-4">

<div class="row mt-4">
    <div class="col-12">
        <h4 class="text-center mb-4">Financial Comparison Overview</h4>
        <div style="height:420px;">
            <canvas id="quotationComparisonChart"></canvas>
        </div>
    </div>
</div>


<div class="row mt-5">
    <div class="col-md-6">
        <h5 class="text-center mb-3">Cost Distribution</h5>
        <div style="height:350px;">
            <canvas id="costDistributionChart"></canvas>
        </div>
    </div>

    <div class="col-md-6">
        <h5 class="text-center mb-3">Budget vs Quotation Trend</h5>
        <div style="height:350px;">
            <canvas id="budgetTrendChart"></canvas>
        </div>
    </div>
</div>


<div class="row mt-5">
    <div class="col-12">
        <h5 class="text-center mb-3">Profit Calculation Breakdown</h5>
        <div style="height:400px;">
            <canvas id="profitBreakdownChart"></canvas>
        </div>
    </div>
</div>

<?php include('../includes/common_footer.php'); ?>

</div>
</div>
</div>

<?php
include('../includes/common_modules.php');
include('../includes/common_scripts.php');
?>

<script src="../js/modules/corporate-booking.js"></script>

</body>


<!-- ===========================
     RESTORED MODAL (IMPORTANT)
=========================== -->

<div class="modal fade bd-example-modal-sm" id="save_submit_quotation_modal" role="dialog">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header" style="background-color:#027dc1;color:#fff;">
<h4 class="modal-title">Update Quotation</h4>
<button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">

<div class="form-group">
<label>Remarks</label>
<textarea class="form-control" id="quotation-remarks" rows="4"></textarea>
</div>

<div class="form-group" id="quotation-tc-div" style="display:none;">
<label>Terms & Conditions</label>
<input class="form-control" type="text" id="quotation-tc">
</div>

<div class="form-group" id="quotation-expiry-date-div" style="display:none;">
<label>Expiry Date</label>
<input class="form-control" type="text" id="quotation-expiry-date">
</div>

<div class="text-center mt-3">
<button class="btn btn-primary"
        id="saving_quotation_modal_button"
        onclick="ModifyQuotationAction();">
        Save
</button>
</div>

</div>
</div>
</div>
</div>




<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const ctx = document.getElementById('quotationComparisonChart').getContext('2d');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [
            'Quotation Total',
            'Expected Budget',
            'Internal Cost (T)',
            'Customer Cost (C)',
            'Payment Done',
            'Profit / Loss'
        ],
        datasets: [{
            label: 'Amount (₹)',
            data: [
                <?php echo $QuotationTotal; ?>,
                <?php echo $ExpectedBudget; ?>,
                <?php echo $InternalTotal; ?>,
                <?php echo $CustomerTotal; ?>,
                <?php echo $PaymentTotal; ?>,
                <?php echo $Profit; ?>
            ],
            backgroundColor: [
                '#007bff',
                '#ffc107',
                '#17a2b8',
                '#28a745',
                '#6f42c1',
                <?php echo ($Profit >= 0) ? "'#20c997'" : "'#dc3545'"; ?>
            ],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '₹ ' + context.parsed.y.toLocaleString('en-IN');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₹ ' + value.toLocaleString('en-IN');
                    }
                }
            }
        }
    }
});
</script>


<script>
/* =========================
   COST DISTRIBUTION CHART
========================= */

new Chart(document.getElementById('costDistributionChart'), {
    type: 'doughnut',
    data: {
        labels: [
            'Internal Cost (T)',
            'Customer Cost (C)',
            'Payment Done'
        ],
        datasets: [{
            data: [
                <?php echo $InternalTotal; ?>,
                <?php echo $CustomerTotal; ?>,
                <?php echo $PaymentTotal; ?>
            ],
            backgroundColor: [
                '#17a2b8',
                '#28a745',
                '#6f42c1'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ₹ ' + 
                        context.parsed.toLocaleString('en-IN');
                    }
                }
            }
        }
    }
});
</script>



<script>
/* =========================
   BUDGET VS QUOTATION TREND
========================= */

new Chart(document.getElementById('budgetTrendChart'), {
    type: 'line',
    data: {
        labels: ['Expected Budget', 'Quotation Total'],
        datasets: [{
            label: 'Amount (₹)',
            data: [
                <?php echo $ExpectedBudget; ?>,
                <?php echo $QuotationTotal; ?>
            ],
            borderColor: '#007bff',
            backgroundColor: 'rgba(0,123,255,0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 6,
            pointBackgroundColor: '#007bff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '₹ ' + context.parsed.y.toLocaleString('en-IN');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₹ ' + value.toLocaleString('en-IN');
                    }
                }
            }
        }
    }
});
</script>


<script>
/* =========================
   PROFIT BREAKDOWN CHART
========================= */

new Chart(document.getElementById('profitBreakdownChart'), {
    type: 'bar',
    data: {
        labels: [
            'Customer Cost',
            'Payment Done',
            'Internal Cost',
            'Final Profit'
        ],
        datasets: [{
            label: 'Amount (₹)',
            data: [
                <?php echo $CustomerTotal; ?>,
                <?php echo $PaymentTotal; ?>,
                <?php echo $InternalTotal; ?>,
                <?php echo $Profit; ?>
            ],
            backgroundColor: [
                '#28a745',
                '#6f42c1',
                '#17a2b8',
                <?php echo ($Profit >= 0) ? "'#20c997'" : "'#dc3545'"; ?>
            ],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '₹ ' + context.parsed.y.toLocaleString('en-IN');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₹ ' + value.toLocaleString('en-IN');
                    }
                }
            }
        }
    }
});
</script>

</html>