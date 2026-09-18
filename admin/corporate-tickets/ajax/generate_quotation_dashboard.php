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
                        <div class="col-12 col-md-4 mb-4">
                          <div class="panel h-100" style="border: 1px solid rgba(0,0,0,0.05); box-shadow: var(--ab-shadow); border-radius: var(--ab-radius); overflow: hidden;">
                            <div class="panel-hdr" style="border-bottom: 3px solid <?=$color_qa;?>; background: #fff;">
                                <h2 style="color: <?=$color_qa;?>; font-weight: 600; font-size: 1rem;"><i class="fas fa-check-circle mr-2"></i> Quotation Approved</h2>
                            </div>
                            <div class="panel-content p-4">
                              <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                                  <span class="text-muted font-weight-bold text-uppercase" style="font-size: 0.8rem;">Total Cost</span>
                                  <span class="h3 m-0 font-weight-bold text-dark">Rs. <?=$core->formatIndianNumber($quotation_price_approved);?></span>
                              </div>
                              <ul class="list-unstyled m-0" style="font-size: 0.9rem;">
                                <li class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Service Cost</span>
                                    <span class="font-weight-bold text-dark">Rs. <?=$core->formatIndianNumber($quotation_price_approved_sc);?></span>
                                </li>
                                <li class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Material Cost</span>
                                    <span class="font-weight-bold text-dark">Rs. <?=$core->formatIndianNumber($quotation_price_approved_pc);?></span>
                                </li>
                                <li class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Visit Charge Cost</span>
                                    <span class="font-weight-bold text-dark">Rs. <?=$core->formatIndianNumber($quotation_price_approved_vc);?></span>
                                </li>
                                <li class="d-flex justify-content-between">
                                    <span class="text-muted">Not Set Cost</span>
                                    <span class="font-weight-bold text-dark">Rs. <?=$core->formatIndianNumber($quotation_price_approved_ns);?></span>
                                </li>
                              </ul>
                            </div>
                          </div>
                        </div>

                        <div class="col-12 col-md-4 mb-4">
                          <div class="panel h-100" style="border: 1px solid rgba(0,0,0,0.05); box-shadow: var(--ab-shadow); border-radius: var(--ab-radius); overflow: hidden;">
                            <div class="panel-hdr" style="border-bottom: 3px solid <?=$color_qsap;?>; background: #fff;">
                                <h2 style="color: <?=$color_qsap;?>; font-weight: 600; font-size: 1rem;"><i class="fas fa-hourglass-half mr-2"></i> Quotation Pending</h2>
                            </div>
                            <div class="panel-content p-4">
                              <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                                  <span class="text-muted font-weight-bold text-uppercase" style="font-size: 0.8rem;">Total Cost</span>
                                  <span class="h3 m-0 font-weight-bold text-dark">Rs. <?=$core->formatIndianNumber($quotation_price_pending);?></span>
                              </div>
                              <ul class="list-unstyled m-0" style="font-size: 0.9rem;">
                                <li class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Service Cost</span>
                                    <span class="font-weight-bold text-dark">Rs. <?=$core->formatIndianNumber($quotation_price_pending_sc);?></span>
                                </li>
                                <li class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Material Cost</span>
                                    <span class="font-weight-bold text-dark">Rs. <?=$core->formatIndianNumber($quotation_price_pending_pc);?></span>
                                </li>
                                <li class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Visit Charge Cost</span>
                                    <span class="font-weight-bold text-dark">Rs. <?=$core->formatIndianNumber($quotation_price_pending_vc);?></span>
                                </li>
                                <li class="d-flex justify-content-between">
                                    <span class="text-muted">Not Set Cost</span>
                                    <span class="font-weight-bold text-dark">Rs. <?=$core->formatIndianNumber($quotation_price_pending_ns);?></span>
                                </li>
                              </ul>
                            </div>
                          </div>
                        </div>

                        <div class="col-12 col-md-4 mb-4">
                          <div class="panel h-100" style="border: 1px solid rgba(0,0,0,0.05); box-shadow: var(--ab-shadow); border-radius: var(--ab-radius); overflow: hidden;">
                            <div class="panel-hdr" style="border-bottom: 3px solid <?=$color_qr;?>; background: #fff;">
                                <h2 style="color: <?=$color_qr;?>; font-weight: 600; font-size: 1rem;"><i class="fas fa-times-circle mr-2"></i> Quotation Rejected</h2>
                            </div>
                            <div class="panel-content p-4">
                              <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                                  <span class="text-muted font-weight-bold text-uppercase" style="font-size: 0.8rem;">Total Cost</span>
                                  <span class="h3 m-0 font-weight-bold text-dark">Rs. <?=$core->formatIndianNumber($quotation_price_rejected);?></span>
                              </div>
                              <ul class="list-unstyled m-0" style="font-size: 0.9rem;">
                                <li class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Service Cost</span>
                                    <span class="font-weight-bold text-dark">Rs. <?=$core->formatIndianNumber($quotation_price_rejected_sc);?></span>
                                </li>
                                <li class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Material Cost</span>
                                    <span class="font-weight-bold text-dark">Rs. <?=$core->formatIndianNumber($quotation_price_rejected_pc);?></span>
                                </li>
                                <li class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Visit Charge Cost</span>
                                    <span class="font-weight-bold text-dark">Rs. <?=$core->formatIndianNumber($quotation_price_rejected_vc);?></span>
                                </li>
                                <li class="d-flex justify-content-between">
                                    <span class="text-muted">Not Set Cost</span>
                                    <span class="font-weight-bold text-dark">Rs. <?=$core->formatIndianNumber($quotation_price_rejected_ns);?></span>
                                </li>
                              </ul>
                            </div>
                          </div>
                        </div>
                      </div>
                  
                </div>
              </div>
</div>