<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        include('../controllers/common_controllers.php');
        include('../branch/controller/branch_controller.php');
        include('../company/controller/company_controller.php');
        require_once('../includes/autoloader.inc.php');

        $UserType = SessionCheck();
        setTimeZone();
        $conn = _connectodb();
        setNavigation($_SESSION['Roles']);

        $company_object = new Company($conn);
        $branch_object  = new Branch($conn);

        $ProductName = "Aryadibusiness";
        $CorporateID = -1;
        $BranchID    = -1;

        if($UserType == "Corporate Admin")
        {
            $CorporateID = $_SESSION['Roles']['CorporateID'];
        }
        if($UserType == "Corporate Branch User")
        {
            $CorporateID = $_SESSION['Roles']['CorporateID'];
            $BranchID    = $_SESSION['Roles']['BranchID'];
        }

        $company_array = $company_object->setCompanyArray('All');
        if($CorporateID != -1)
        {
            $branch_array = $branch_object->setBranchArrayByCorporateID($CorporateID,'All');
        }
        else
        {
            $branch_array = $branch_object->setBranchArray('All');
        }

        $current_date   = date('Y-m-d');
        $month_start    = date('Y-m-01');
        $month_end      = date('Y-m-t');

        $filter_start   = isset($_GET['start_date']) ? $_GET['start_date'] : $month_start;
        $filter_end     = isset($_GET['end_date']) ? $_GET['end_date'] : $month_end;
        $filter_corp    = isset($_GET['CorporateID']) ? intval($_GET['CorporateID']) : $CorporateID;
        $filter_branch  = isset($_GET['BranchID']) ? intval($_GET['BranchID']) : $BranchID;
        $filter_status  = isset($_GET['BillingStatus']) ? $_GET['BillingStatus'] : "";

        // Reuse existing billing logic
        $billing_obj = new Ppmbilling($conn);
        $billing_filters = array(
            'start_date'    => $filter_start,
            'end_date'      => $filter_end,
            'CorporateID'   => $filter_corp,
            'BranchID'      => $filter_branch,
            'BillingStatus' => $filter_status
        );
        $billing_data = $billing_obj->getBillingData($billing_filters);

        // Build monthly and quarterly summary on top of ticket-level data
        $summary_by_account = array();   // key: CorporateID|BranchID|BranchAssetID|Year
        $monthly_totals     = array();   // key: YYYY-MM
        $overall_totals     = array('billed' => 0, 'pending' => 0);

        foreach($billing_data as $row){
            $ppmDate = $row['PPMDate'];
            $year    = (int)substr($ppmDate, 0, 4);
            $month   = (int)substr($ppmDate, 5, 2);
            $month_key = substr($ppmDate, 0, 7); // YYYY-MM
            $quarter = 'Q'.ceil($month / 3);

            $key = $row['CorporateID'].'|'.$row['BranchID'].'|'.$row['BranchAssetID'].'|'.$year;
            if(!isset($summary_by_account[$key])){
                $summary_by_account[$key] = array(
                    'CorporateID'   => $row['CorporateID'],
                    'CompanyName'   => $row['CompanyName'],
                    'BranchID'      => $row['BranchID'],
                    'BranchSite'    => $row['BranchSite'],
                    'BranchCity'    => $row['BranchCity'],
                    'BranchAssetID' => $row['BranchAssetID'],
                    'EquipmentName' => $row['EquipmentName'],
                    'EquipmentLocation' => $row['EquipmentLocation'],
                    'IntervalType'  => $row['IntervalType'],
                    'Year'          => $year,
                    'Q1'            => 0,
                    'Q2'            => 0,
                    'Q3'            => 0,
                    'Q4'            => 0,
                    'TotalContractAmount' => (float)$row['AssetContractAmount'],
                    'TotalBilledAmount'   => 0
                );
            }

            $amount = (float)$row['DisplayBilledAmount'];
            $summary_by_account[$key][$quarter] += $amount;
            $summary_by_account[$key]['TotalBilledAmount'] += $amount;

            if(!isset($monthly_totals[$month_key])){
                $monthly_totals[$month_key] = array('billed' => 0, 'pending' => 0);
            }
            if($row['BillingStatus'] == 'Billed'){
                $monthly_totals[$month_key]['billed'] += $amount;
                $overall_totals['billed'] += $amount;
            } else {
                $monthly_totals[$month_key]['pending'] += $amount;
                $overall_totals['pending'] += $amount;
            }
        }
    ?>

    <meta charset="utf-8">
    <meta name="description" content="PPM Billing">
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    <title>PPM Billing</title>

</head>
<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/analytics_dashboard"><?php echo $ProductName; ?></a></li>
                        <li class="breadcrumb-item active">PPM Billing</li>
                    </ol>

                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2><span>PPM Billing Summary</span></h2>
                                    <form class="form-inline ml-auto" method="get">
                                        <div class="form-group mr-2">
                                            <label class="mr-1">Period</label>
                                            <input type="text" class="form-control" id="filter_date" name="filter_date"
                                                   value="<?php echo $filter_start.' - '.$filter_end; ?>">
                                            <input type="hidden" name="start_date" id="start_date" value="<?php echo $filter_start; ?>">
                                            <input type="hidden" name="end_date" id="end_date" value="<?php echo $filter_end; ?>">
                                        </div>
                                        <?php if($UserType != "Corporate Admin" && $UserType != "Corporate Branch User"){ ?>
                                        <div class="form-group mr-2">
                                            <label class="mr-1">Corporate</label>
                                            <select name="CorporateID" class="form-control">
                                                <option value="-1">All</option>
                                                <?php foreach($company_array as $CID=>$company){ ?>
                                                    <option value="<?php echo $CID; ?>" <?php if($filter_corp == $CID) echo "selected"; ?>>
                                                        <?php echo $company['CompanyName']; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <?php } ?>
                                        <div class="form-group mr-2">
                                            <label class="mr-1">Branch</label>
                                            <select name="BranchID" class="form-control">
                                                <option value="-1">All</option>
                                                <?php foreach($branch_array as $branch){ ?>
                                                    <option value="<?php echo $branch['ID']; ?>" <?php if($filter_branch == $branch['ID']) echo "selected"; ?>>
                                                        <?php echo $branch['BranchSite']; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="form-group mr-2">
                                            <label class="mr-1">Billing</label>
                                            <select name="BillingStatus" class="form-control">
                                                <option value="">All</option>
                                                <option value="Pending" <?php if($filter_status == "Pending") echo "selected"; ?>>Pending</option>
                                                <option value="Billed" <?php if($filter_status == "Billed") echo "selected"; ?>>Billed</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary mr-2">Search</button>
                                        <button type="button" class="btn btn-info" onclick="DownloadPPMBillingPDF();">Download PDF</button>
                                    </form>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- High level summary -->
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <div class="p-3 bg-info-100 rounded">
                                                    <div class="fw-500">Total Billed (closed PPM in period)</div>
                                                    <div class="fs-xxl">
                                                        <?php echo number_format($overall_totals['billed'],2); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="p-3 bg-warning-100 rounded">
                                                    <div class="fw-500">Total Pending Billing</div>
                                                    <div class="fs-xxl">
                                                        <?php echo number_format($overall_totals['pending'],2); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="p-3 bg-success-100 rounded">
                                                    <div class="fw-500">Total (Billed + Pending)</div>
                                                    <div class="fs-xxl">
                                                        <?php echo number_format($overall_totals['billed'] + $overall_totals['pending'],2); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Quarterly / Invoice style account summary -->
                                        <h4>Account-wise Quarterly Summary</h4>
                                        <div class="table-responsive mb-4">
                                            <table class="table table-bordered table-hover table-striped w-100">
                                                <thead>
                                                    <tr>
                                                        <th>Corporate</th>
                                                        <th>Branch</th>
                                                        <th>Asset</th>
                                                        <th>Year</th>
                                                        <th>PPM Type</th>
                                                        <th>Q1</th>
                                                        <th>Q2</th>
                                                        <th>Q3</th>
                                                        <th>Q4</th>
                                                        <th>Total Billed</th>
                                                        <th>Contract Amount</th>
                                                        <th>Due Amount</th>
                                                        <th>PPM Billing Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if(count($summary_by_account) == 0){
                                                        ?>
                                                        <tr>
                                                            <td colspan="13" class="text-center">No PPM tickets found for the selected filters.</td>
                                                        </tr>
                                                        <?php
                                                    } else {
                                                        foreach($summary_by_account as $row){
                                                            $due_amount = max($row['TotalContractAmount'] - $row['TotalBilledAmount'], 0);
                                                            if($row['TotalBilledAmount'] >= $row['TotalContractAmount'] && $row['TotalContractAmount'] > 0){
                                                                $billing_status = "Fully Billed";
                                                                $badge_class = "success";
                                                            } elseif($row['TotalBilledAmount'] > 0){
                                                                $billing_status = "Partially Billed";
                                                                $badge_class = "warning";
                                                            } else {
                                                                $billing_status = "Not Billed";
                                                                $badge_class = "danger";
                                                            }
                                                            ?>
                                                            <tr>
                                                                <td><?php echo $row['CompanyName']; ?></td>
                                                                <td><?php echo $row['BranchSite']." (".$row['BranchCity'].")"; ?></td>
                                                                <td>
                                                                    <?php echo $row['EquipmentName']; ?><br>
                                                                    <small><?php echo $row['EquipmentLocation']; ?></small>
                                                                </td>
                                                                <td><?php echo $row['Year']; ?></td>
                                                                <td><?php echo $row['IntervalType']; ?></td>
                                                                <td><?php echo number_format($row['Q1'],2); ?></td>
                                                                <td><?php echo number_format($row['Q2'],2); ?></td>
                                                                <td><?php echo number_format($row['Q3'],2); ?></td>
                                                                <td><?php echo number_format($row['Q4'],2); ?></td>
                                                                <td><?php echo number_format($row['TotalBilledAmount'],2); ?></td>
                                                                <td><?php echo number_format($row['TotalContractAmount'],2); ?></td>
                                                                <td><?php echo number_format($due_amount,2); ?></td>
                                                                <td>
                                                                    <span class="badge badge-<?php echo $badge_class; ?>">
                                                                        <?php echo $billing_status; ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <?php
                                                        }
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Detailed ticket-wise list (per visit / invoice line items) -->
                                        <h4>Ticket-wise PPM Billing Details</h4>
                                        <table id="ppm-billing-table" class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Ticket</th>
                                                    <th>Corporate / Branch</th>
                                                    <th>Asset</th>
                                                    <th>PPM Date</th>
                                                    <th>Interval</th>
                                                    <th>Contract Amount</th>
                                                    <th>Per Visit Amount</th>
                                                    <th>Billing Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                $total_amount = 0;
                                                foreach($billing_data as $row){
                                                    $total_amount += $row['DisplayBilledAmount'];
                                                ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $row['TicketNumber']; ?></td>
                                                    <td>
                                                        <b><?php echo $row['CompanyName']; ?></b><br>
                                                        <?php echo $row['BranchSite']; ?> (<?php echo $row['BranchCity']; ?>)
                                                    </td>
                                                    <td>
                                                        <?php echo $row['EquipmentName']; ?><br>
                                                        <small><?php echo $row['EquipmentLocation']; ?></small>
                                                    </td>
                                                    <td><?php echo $row['PPMDate']; ?></td>
                                                    <td><?php echo $row['IntervalType']; ?></td>
                                                    <td><?php echo number_format($row['AssetContractAmount'],2); ?></td>
                                                    <td><?php echo number_format($row['DisplayBilledAmount'],2); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo ($row['BillingStatus'] == 'Billed') ? 'success' : 'warning'; ?>">
                                                            <?php echo $row['BillingStatus']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php
                                                    $i++;
                                                }
                                                ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="7" class="text-right">Total Billed Amount (tickets)</th>
                                                    <th colspan="2"><?php echo number_format($total_amount,2); ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form id="ppm_billing_pdf_form" method="post" action="action/export_ppm_billing_pdf.php" target="_blank">
                        <input type="hidden" name="start_date" value="<?php echo $filter_start; ?>">
                        <input type="hidden" name="end_date" value="<?php echo $filter_end; ?>">
                        <input type="hidden" name="CorporateID" value="<?php echo $filter_corp; ?>">
                        <input type="hidden" name="BranchID" value="<?php echo $filter_branch; ?>">
                        <input type="hidden" name="BillingStatus" value="<?php echo $filter_status; ?>">
                    </form>

                </main>
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <?php include('../includes/common_footer.php'); ?>
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
        function DownloadPPMBillingPDF() {
            document.getElementById('ppm_billing_pdf_form').submit();
        }

        $(document).ready(function() {
            $('#filter_date').daterangepicker({
                locale: { format: 'YYYY-MM-DD' },
                startDate: '<?php echo $filter_start; ?>',
                endDate: '<?php echo $filter_end; ?>'
            }, function(start, end) {
                $('#start_date').val(start.format('YYYY-MM-DD'));
                $('#end_date').val(end.format('YYYY-MM-DD'));
            });
        });
    </script>
</body>
</html>


