<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>
        Aryadibusiness - View Quotation
    </title>
    <meta name="description" content="Aryadibusiness - View Quotation">
    <?php
    include('../includes/common_head_content.php');
    include('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    ?>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>

<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
    <!-- DOC: script to save and load page settings -->
    <?php include('../js/theme_settings.js'); ?>
    <!-- BEGIN Page Wrapper -->
    <div class="page-wrapper">
        <div class="page-inner">
            
            <div class="page-content-wrapper" style="padding-left:1em !important;">
                <!-- BEGIN Page Header -->
            
                   
                <!-- END Page Header -->
                <!-- BEGIN Page Content -->
                <!-- the #js-page-content id is needed for some plugins to initialize -->
                <main id="js-page-content" role="main" class="page-content" style="margin-top:0">
                     
              

                    <?php
                    if(isset($_GET['QuotationID']) && isset($_GET['By']))
                    {
                        $QuotationID = base64_decode($_GET['QuotationID']);
                        $CreatedBy = base64_decode($_GET['By']);
                        $dbh = new Dbh();
                        $conn = $dbh->_connectodb();
                        $corporateticket = new Corporateticket($conn);
                        // Get Line Items information 
                        $quotation_detail = $corporateticket->GetQuotationDetailbyID($QuotationID); 
                        
                        $q_line_items = $corporateticket->GetQuotationLineItems($QuotationID);
                        if(sizeof($q_line_items) > 0)
                        {
                        ?>

                        <div class="container">
                            <input type="hidden" id="CreatedBy" value="<?php echo $CreatedBy; ?>" />
                            <input type="hidden" id="TicketQuotationID" value="<?php echo $QuotationID; ?>" />
                            <div class="row">
                                <div class="col-4">
                                    <a href="#"  data-toggle="modal" data-target="#modal-shortcut">
                                        <img width="112px" style="height:27px;" src="../img/tech-logo.jpg" >
                                       
                                    </a>
                                </div>
                                <div class="col-4">
                                </div>
                                <?php 
                                if($quotation_detail['QuotationStatus'] == "Quote Sent Approval Pending")
                                {
                                    ?>
                                    <div class="col-4 text-right">
                                         <button type="button" onclick="UpdateQuotationStatus('Quote Approved');" class="btn btn-sm btn-success ml-3 mr-3">Approve</button>
                                        <button type="button" onclick="UpdateQuotationStatus('Quote Rejected by Client');" class="btn btn-sm btn-danger ml-1 mr-3">Reject</button>
                                    </div>
                                    <?php
                                }

                                ?>
                                
                            </div>
                           <div class="row">
                                <div class="col-sm-12">
                                    <div class="table-responsive">
                                        <table class="table mt-2">
                                            <thead>
                                                <tr>
                                                    <th class="text-center border-top-0 table-scale-border-bottom fw-700"></th>
                                                    <th class="border-top-0 table-scale-border-bottom fw-700">Item</th>
                                                    <th class="border-top-0 table-scale-border-bottom fw-700">Category</th>
                                                    <th class="border-top-0 table-scale-border-bottom fw-700">Make</th>
                                                    <th class="border-top-0 table-scale-border-bottom fw-700">HSN</th>
                                                    <th class="border-top-0 table-scale-border-bottom fw-700">ARC Code</th>
                                                    <th class="text-left border-top-0 table-scale-border-bottom fw-700">Unit Cost</th>
                                                    <th class="text-left border-top-0 table-scale-border-bottom fw-700">Qty</th>
                                                    <th class="text-left border-top-0 table-scale-border-bottom fw-700">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $i=1;
                                                $SuperTotal = 0;
                                                foreach($q_line_items as $line_item)
                                                {
                                                    $SuperTotal = $SuperTotal +  $line_item['TotalPrice'];
                                                ?>
                                                    <tr>
                                                        <td class="text-center fw-700">                                                            
                                                            <?php echo $i; ?>
                                                        </td>
                                                        <td class="text-left strong"><?php echo $line_item['LineItemName']; ?></td>
                                                        <td class="text-left"><?php echo $line_item['Category']; ?></td>
                                                        <td class="text-left"><?php echo $line_item['Make']; ?></td>
                                                        <td class="text-left"><?php echo $line_item['HSN']; ?></td>
                                                        <td class="text-left"><?php echo $line_item['ARCCode']; ?></td>
                                                        <td class="text-left"><?php echo $line_item['PerItemPrice']; ?></td>
                                                        <td class="text-left"><?php echo $line_item['Qty']; ?></td>
                                                        <td class="text-left">&#8377;<?php echo $line_item['TotalPrice']; ?></td>
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
                                            
                                            <tr class="table-scale-border-top border-left-0 border-right-0 border-bottom-0">
                                                <td class="text-left keep-print-font">
                                                    <h4 class="m-0 fw-700 h2 keep-print-font color-primary-700">Total</h4>
                                                </td>
                                                <td class="text-right keep-print-font">
                                                    <h4 class="m-0 fw-700 h2 keep-print-font">&#8377;<?php echo $SuperTotal; ?></h4>
                                                </td>
                                            </tr>
                                            
                                           
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php
                        }
                        

                    }
                    else
                    {
                        echo "<h2>Sorry Invalid Page</h2>";
                    }
                    ?>
                </main>
                <!-- this overlay is activated only when mobile menu is triggered -->
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <!-- END Page Content -->
                <!-- BEGIN Page Footer -->
                <?php
                include('../includes/common_footer.php')
                ?>
                <!-- END Page Footer -->
            </div>
        </div>
    </div>
    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <script src="../js/modules/corporate-booking.js?v=20260619a"></script>

</body>

<div class="modal fade bd-example-modal-sm" id="save_submit_quotation_modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Update Quotation</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
               <input type="hidden" id="new_quotation_status" value="" />
                <div id="wizard">
                    <section>
                        <div class="row">

                             <div class="col-md-12 col-12">
                                <div class="form-group">
                                    <label class="form-label" for="quotation-remarks">Remarks</label>
                                    <textarea class="form-control" id="quotation-remarks" rows="5"></textarea>
                                </div>
                                <div class="form-group" id="quotation-tc-div" style="display:none;">
                                    <label class="form-label" for="quotation-remarks">Terms and Conditions</label>
                                    <input class="form-control" type="text" id="quotation-tc" name="quotation-tc" value=""></input>
                                </div>
                                <div class="form-group" id="quotation-expiry-date-div" style="display:none;">
                                    <label class="form-label" for="quotation-remarks">Expiry Date<span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" id="quotation-expiry-date" name="quotation-expiry-date" value=""></input>
                                </div>
                            </div>
                        </div>
                        <section>
                            <div class="row justify-content-center mt-3">
                                <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="saving_quotation_modal_button"
                                    style="background-color: #2196f3;" onclick="ModifyQuotationAction()" value="Save">Save</a>
                            </div>
                        </section>
                </div>
            </div>
        </div>
    </div>
</div>
</html>