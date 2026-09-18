<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
include('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
$corporate_tickets_obj = new Corporateticket($conn);
$filters = array();
$CorporateID = -1;
if(isset($_POST['CorporateID']))
{
  $filters['CorporateID'] = $_POST['CorporateID'];
  $CorporateID = $_POST['CorporateID'];
}
$Category_selected = "";
if(isset($_POST['Category']))
{
  $Category_selected = $_POST['Category'];
  $filters['Category'] = $_POST['Category'];
}
$Status_selected = "";
if(isset($_POST['TicketStatus']))
{
  $Status_selected = $_POST['TicketStatus'];
  $filters['TicketStatus'] = $_POST['TicketStatus'];
}
if(isset($_POST['sql_in_state_string']))
{
  $filters['sql_in_state_string'] = $_POST['sql_in_state_string'];
}
if(isset($_POST['sql_in_branch_account_string']))
{
  $filters['sql_in_branch_account_string'] = $_POST['sql_in_branch_account_string'];
}
if(isset($_POST['filter_date']))
{
  $filters['filter_date'] = $_POST['filter_date'];
}
if(isset($_POST['state_filter']))
{
  $filters['state_filter'] = $_POST['state_filter'];
}
if(isset($_POST['region_filter']))
{
  $filters['region_filter'] = $_POST['region_filter'];
}
$categories_obj = new Categories($conn);
$categories_array = $categories_obj->getAllCategories();
$quotation_price_status_array = $corporate_tickets_obj->GetQuotationPricesGroupByStatus($filters);
$status_array = $corporate_tickets_obj->getCorporateTicketStatusArray($conn);
$quotation_price_approved = 0;
$quotation_price_rejected = 0;
$quotation_price_pending = 0;
$quotation_price_approved_sc = 0;
$quotation_price_approved_pc = 0;
$quotation_price_approved_vc = 0;
$quotation_price_approved_ns = 0;

$quotation_price_rejected_sc = 0;
$quotation_price_rejected_pc = 0;
$quotation_price_rejected_vc = 0;
$quotation_price_rejected_ns = 0;

$quotation_price_pending_sc = 0;
$quotation_price_pending_pc = 0;
$quotation_price_pending_vc = 0;
$quotation_price_pending_ns = 0;

$color_qsap = "#FFA500";
$color_qr = "#DC3545";
$color_qa = "#28A745";

foreach($quotation_price_status_array as $quotation_price)
{
  if($quotation_price['QuotationStatus'] == "Quote Approved")
  {
    $quotation_price_approved = $quotation_price_approved + $quotation_price['TotalPriceSum'];
    if($quotation_price['Type'] == "Product")
    {
      $quotation_price_approved_pc = $quotation_price_approved_pc + $quotation_price['TotalPriceSum'];
    }
    if($quotation_price['Type'] == "Service" || $quotation_price['Type'] == "Services")
    {
      $quotation_price_approved_sc = $quotation_price_approved_sc + $quotation_price['TotalPriceSum'];
    }
    if($quotation_price['Type'] == "Visit Charge")
    {
      $quotation_price_approved_vc = $quotation_price_approved_vc + $quotation_price['TotalPriceSum'];
    }
    if($quotation_price['Type'] == "")
    {
      $quotation_price_approved_ns = $quotation_price_approved_ns + $quotation_price['TotalPriceSum'];
    }
  }
  if($quotation_price['QuotationStatus'] == "Quote Rejected by Client")
  {
    $quotation_price_rejected = $quotation_price_rejected + $quotation_price['TotalPriceSum'];
    if($quotation_price['Type'] == "Product")
    {
      $quotation_price_rejected_pc = $quotation_price_rejected_pc + $quotation_price['TotalPriceSum'];
    }
    if($quotation_price['Type'] == "Service" || $quotation_price['Type'] == "Services")
    {
      $quotation_price_rejected_sc = $quotation_price_rejected_sc + $quotation_price['TotalPriceSum'];
    }
    if($quotation_price['Type'] == "Visit Charge")
    {
      $quotation_price_rejected_vc = $quotation_price_rejected_vc + $quotation_price['TotalPriceSum'];
    }
    if($quotation_price['Type'] == "")
    {
      $quotation_price_rejected_ns = $quotation_price_rejected_ns + $quotation_price['TotalPriceSum'];
    }
  }
  if($quotation_price['QuotationStatus'] == "Quote Sent Approval Pending")
  {
    $quotation_price_pending = $quotation_price_pending + $quotation_price['TotalPriceSum'];
    if($quotation_price['Type'] == "Product")
    {
      $quotation_price_pending_pc = $quotation_price_pending_pc + $quotation_price['TotalPriceSum'];
    }
    if($quotation_price['Type'] == "Service" || $quotation_price['Type'] == "Services")
    {
      $quotation_price_pending_sc = $quotation_price_pending_sc + $quotation_price['TotalPriceSum'];
    }
    if($quotation_price['Type'] == "Visit Charge")
    {
      $quotation_price_pending_vc = $quotation_price_pending_vc + $quotation_price['TotalPriceSum'];
    }
    if($quotation_price['Type'] == "")
    {
      $quotation_price_pending_ns = $quotation_price_pending_ns + $quotation_price['TotalPriceSum'];
    }
  }
}

?>

<div id="panel-1" class="panel" style="margin-bottom:1%;width: 100%;">
            <div class="panel-hdr">
                <div class="col-2">
                  <h2>
                      Quotation Summary
                  </h2>
                </div>
                <div class="col-3">
                    <div class="form-group pt-3">
                     
                          <select class="form-control w-100" name="service_name" class="select2"
                          id="service_name_qd" onchange="GenerateQuotationsDashboard_Analytics(<?php echo $CorporateID;?>)">
                          <option value="">Please Select Service</option>
                          <?php
                                foreach($categories_array as $category)
                                {
                                  $selected = "";
                                  if($category['CategoriesName'] == $Category_selected)
                                  {
                                    $selected = "selected";
                                  }
                          ?>
                                  <option value="<?php echo $category['CategoriesName']; ?>" <?php echo $selected;?>><?php echo $category['CategoriesName']; ?></option>
                                  <?php
                                  }
                              ?>
                          </select>
                   </div>
                </div>
                <div class="col-3">
                    <select class="form-control" name="ticket_status" id="ticket_status" onchange="GenerateQuotationsDashboard_Analytics(<?php echo $CorporateID;?>)">
                        <option value="">Select Ticket Status</option>
                        <?php
                        foreach ($status_array as $e_status) 
                        {


                            $Status_name = $e_status['Status'];
                            $selected = "";
                            if($Status_name == $Status_selected)
                            {
                              $selected = "selected";
                            }
                        ?>

                            <option value="<?php echo $Status_name ?>" <?php echo $selected;?>> <?php echo $Status_name; ?></option>

                        <?php 
                        }  
                        ?>
                    </select>
                </div>
                
            </div>
            <div class="panel-container show">
                <div class="panel-content bg-subtlelight-fade">
                  

                      <div class="row">
                        <div class="col-12   
                   col-md-4">
                          <div class="card">
                            <div class="card-header">Quotation Approved</div>
                            <div class="card-body">
                              <p>Total Cost: Rs. <span id="approvedTotal" class="badge badge-info" style="background:<?=$color_qa;?>"><?=$core->formatIndianNumber($quotation_price_approved);?></span></p>
                              <ul>
                                <li>Service Cost: Rs. <span id="approvedLabour"><?=$core->formatIndianNumber($quotation_price_approved_sc);?></span></li>
                                <li>Material Cost: Rs. <span id="approvedMaterial"><?=$core->formatIndianNumber($quotation_price_approved_pc);?></span></li>
                                <li>Visit Charge Cost: Rs. <span id="approvedVisit"><?=$core->formatIndianNumber($quotation_price_approved_vc);?></span></li>
                                <li>Not Set Cost: Rs. <span id="approvedVisit"><?=$core->formatIndianNumber($quotation_price_approved_ns);?></span></li>
                              </ul>
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-md-4">
                          <div class="card">
                            <div class="card-header">Quotation Still to be Approved</div>
                            <div class="card-body">
                              <p>Total Cost: Rs. <span id="pendingTotal" class="badge badge-info" style="background:<?=$color_qsap;?>"><?=$core->formatIndianNumber($quotation_price_pending);?></span></p>
                              <ul>
                                <li>Service Cost: Rs. <span id="pendingLabour"><?=$core->formatIndianNumber($quotation_price_pending_sc);?></span></li>
                                <li>Material Cost: Rs. <span id="pendingMaterial"><?=$core->formatIndianNumber($quotation_price_pending_pc);?></span></li>
                                <li>Visit Charge Cost: Rs. <span id="pendingVisit"><?=$core->formatIndianNumber($quotation_price_pending_vc);?></span></li>
                                <li>Not Set Cost: Rs. <span id="approvedVisit"><?=$core->formatIndianNumber($quotation_price_pending_ns);?></span></li>
                              </ul>
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-md-4">
                          <div class="card">
                            <div class="card-header">Quotation Rejected</div>
                            <div class="card-body">
                              <p>Total Cost: Rs. <span id="rejectedTotal" class="badge badge-info" style="background:<?=$color_qr;?>"><?=$core->formatIndianNumber($quotation_price_rejected);?></span></p>
                              <ul>
                                <li>Service Cost: Rs. <span id="rejectedLabour"><?=$core->formatIndianNumber($quotation_price_rejected_sc);?></span></li>
                                <li>Material Cost: Rs. <span id="rejectedMaterial"><?=$core->formatIndianNumber($quotation_price_rejected_pc);?></span></li>
                                <li>Visit Charge Cost: Rs. <span id="rejectedVisit"><?=$core->formatIndianNumber($quotation_price_rejected_vc);?></span></li>
                                <li>Not Set Cost: Rs. <span id="approvedVisit"><?=$core->formatIndianNumber($quotation_price_rejected_ns);?></span></li>
                              </ul>
                            </div>
                          </div>
                        </div>
                      </div>
                  
                </div>
              </div>
</div>