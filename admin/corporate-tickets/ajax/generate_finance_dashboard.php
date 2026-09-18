<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
include('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$corporate_tickets_obj = new Corporateticket($conn);
$filters = array();
$filters['CorporateID'] = -1;
if(isset($_POST['company_account_export']))
{
    if($_POST['company_account_export'] != "" && $_POST['company_account_export'] != -1)
        $filters['CorporateID'] = $_POST['company_account_export'];
}
$filters['State'] = "";
if(isset($_POST['state_export']))
{
    if($_POST['state_export'] != "" && $_POST['state_export'] != -1)
        $filters['State'] = $_POST['state_export'];
}
$filters['City'] = "";
if(isset($_POST['city_export']))
{
    if($_POST['city_export'] != "" && $_POST['city_export'] != -1)
        $filters['City'] = $_POST['city_export'];
}
$filters['Branch'] = "";
if(isset($_POST['branch_export']))
{
     if($_POST['branch_export'] != "" && $_POST['branch_export'] != -1)
        $filters['Branch'] = $_POST['branch_export'];
}
$filters['FinanceStatus'] = "";
if(isset($_POST['status_export']))
{
     if($_POST['status_export'] != "" && $_POST['status_export'] != -1)
        $filters['FinanceStatus'] = $_POST['status_export'];
}
$filter_date = $_POST['filter_date_export'];
$filters['StartDate'] = explode(" - ",$filter_date)[0];
$filters['EndDate'] = explode(" - ",$filter_date)[1];

$finance_placed_cost = $corporate_tickets_obj->getTicketsFinanceTotalCost($filters);
$core = new Core();
?>
    <div id="panel-1" class="panel" style="margin-bottom:1%;width:50%;">
        <div class="panel-hdr">
            <h2>Finance Placed Cost</h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content bg-subtlelight-fade">
                <div class="row">
                    <!-- Customer Cost -->
                    <div class="col-md-6 mb-3">
                        <div class="card border-success w-100">
                            <div class="card-header bg-success text-white">
                                Customer Cost
                            </div>
                            <div class="card-body">
                                <p>Total Price: <span class="badge bg-success text-white"><?php echo $core->convertToIndianCurrency($finance_placed_cost['C_TotalPrice']);?></span></p>
                                <p>Visit Charge: <span class="badge bg-success text-white"><?php echo $core->convertToIndianCurrency($finance_placed_cost['C_VisitCharge']);?></span></p>
                                <p>Service Cost: <span class="badge bg-success text-white"><?php echo $core->convertToIndianCurrency($finance_placed_cost['C_LabourCost']);?></span></p>
                                <p>Material Cost: <span class="badge bg-success text-white"><?php echo $core->convertToIndianCurrency($finance_placed_cost['C_MaterialCost']);?></span></p>
                            </div>
                        </div>
                    </div>
                    <!-- TechXpert Cost -->
                    <div class="col-md-6 mb-3">
                        <div class="card border-info w-100">
                            <div class="card-header bg-info text-white">
                                TechXpert Cost
                            </div>
                            <div class="card-body">
                                <p>Total Price: <span class="badge bg-info text-white"><?php echo $core->convertToIndianCurrency($finance_placed_cost['T_TotalPrice']);?></span></p>
                                <p>Visit Charge: <span class="badge bg-info text-white"><?php echo $core->convertToIndianCurrency($finance_placed_cost['T_VisitCharge']);?></span></p>
                                <p>Service Cost: <span class="badge bg-info text-white"><?php echo $core->convertToIndianCurrency($finance_placed_cost['T_LabourCost']);?></span></p>
                                <p>Material Cost: <span class="badge bg-info text-white"><?php echo $core->convertToIndianCurrency($finance_placed_cost['T_MaterialCost']);?></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="panel-8" class="panel" style="margin-bottom:1%;width:50%;">
        <div class="panel-hdr">
            <h2>
                Top 10 States<span class="fw-300"><i>Revenue</i></span>
            </h2>
            <!-- <div class="panel-toolbar">
                <button class="btn btn-panel waves-effect waves-themed" data-action="panel-collapse" data-toggle="tooltip" data-offset="0,10" data-original-title="Collapse"></button>
                <button class="btn btn-panel waves-effect waves-themed" data-action="panel-fullscreen" data-toggle="tooltip" data-offset="0,10" data-original-title="Fullscreen"></button>
                <button class="btn btn-panel waves-effect waves-themed" data-action="panel-close" data-toggle="tooltip" data-offset="0,10" data-original-title="Close"></button>
            </div> -->
        </div>
        <div class="panel-container show">
            <div class="panel-content">
               
                <div id="barChart"><div class="chartjs-size-monitor"><div class="chartjs-size-monitor-expand"><div class=""></div></div><div class="chartjs-size-monitor-shrink"><div class=""></div></div></div>
                    <canvas style="width: 100%; height: 300px; display: block;" width="743" height="300" class="chartjs-render-monitor"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!--div id="panel-1" class="panel" style="margin-bottom:1% ;">
        <div class="panel-hdr">
            <h2>
                Quotation Cost
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content bg-subtlelight-fade">
                <button type="button" class="btn btn-xs btn-primary waves-effect waves-themed mb-2 ml-2 text-white analytics-button" >Quotation Approved Cost
                        <span class="badge bg-primary-500 ml-2">Rs.100</span>
                </button>
                <button type="button" class="btn btn-xs btn-primary waves-effect waves-themed mb-2 ml-2 text-white analytics-button" >Quotation Pending Cost
                        <span class="badge bg-primary-500 ml-2">Rs.100</span>
                </button>
                <button type="button" class="btn btn-xs btn-primary waves-effect waves-themed mb-2 ml-2 text-white analytics-button" >Visit Charge Cost
                        <span class="badge bg-primary-500 ml-2">Rs.100</span>
                </button>
                <button type="button" class="btn btn-xs btn-primary waves-effect waves-themed mb-2 ml-2 text-white analytics-button" >Product Cost
                        <span class="badge bg-primary-500 ml-2">Rs.100</span>
                </button>
                <button type="button" class="btn btn-xs btn-primary waves-effect waves-themed mb-2 ml-2 text-white analytics-button" >Service Cost
                        <span class="badge bg-primary-500 ml-2">Rs.100</span>
                </button>
            </div>
        </div>
    </div-->

