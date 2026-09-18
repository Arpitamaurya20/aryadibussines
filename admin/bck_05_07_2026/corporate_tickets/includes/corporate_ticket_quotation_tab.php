<!-- Page Container -->
<?php
$quotation_detail = $corporateticket_obj->GetQuotationDetail($ID); 
$quotation_button_text = "Create Quotation";
$QuotationStatus = "";
if($quotation_detail != null)
{
    $QuotationID = $quotation_detail['ID'];
    $QuotationStatus = $quotation_detail['QuotationStatus'];
    $quotation_button_text = "Edit Quotation";
}
else
{
    $QuotationID = -1;

}
$core = new Core();
$uom_array = $core->_getTableRecords($conn,'manage_uom',' where 1');

$quote_company_list = $core->_getTableRecords($conn, 'quote_company_details', ' where IsActive = 1 ORDER BY CompanyName ASC');
if (!is_array($quote_company_list)) {
    $quote_company_list = array();
}
$saved_quote_company_id = 0;
$saved_expected_budget = 0;
if ($quotation_detail != null && is_array($quotation_detail)) {
    $saved_quote_company_id = (int)($quotation_detail['QuoteCompanyDetailsID'] ?? 0);
    $saved_expected_budget = (float)($quotation_detail['expectedbudget'] ?? 0);
}
$view_quotation = 0;
$view_approval = 0;
$reject_quotation = 0;
$view_approval_statemanager=0;
$view_approval_financehead=0;
if($UserType == "Corporate Admin" || $UserType == "Corporate Branch User")
{
    if($CorporateID == 183)
    {
        $view_quotation = 1;
        
            
    }
    if($QuotationStatus == "Quote Sent Approval Pending" || $QuotationStatus == "Quote Rejected by Client" || $QuotationStatus == "Quote Approved")
    {
        $view_quotation = 1;
    }
    if($QuotationStatus == "Quote Sent Approval Pending")
    {
        $view_approval = 1;
         if($CorporateID == 183)
            {
                 $view_approval = 0;
                
                 
            }
    }
}
else
{
    $view_quotation = 1;
}
if(($UserType == "Admin" || $TicketManager) && ($QuotationStatus == "Quote Approved" || $QuotationStatus == "Quote Sent Approval Pending"))
{
    $reject_quotation = 1;
}
if($CFO)
{
    $view_approval = 1;
}

// if(isset($_SESSION['Roles']['EmployeeRoles']))
//     {
//         $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
//         foreach($EmployeeRoles as $E_Role)
//         {
//             if($E_Role == "State Corporate Lead")
//             {
//                 $view_approval_statemanager = 1;
               
//             }

//             if($E_Role == "Finance")
//             {
//                 $view_approval_financehead = 1;
               
//             }
//         }
//     }

$view_approval_statemanager = 0;
$view_approval_financehead  = 0;

$LoggedEmployeeID = $_SESSION['Roles']['EmployeeID'] ?? 0;


/* =====================================================
   STEP 1: GET TICKET ID FROM QUOTATION
===================================================== */
$TicketID = $quotation_detail['TicketID'] ?? 0;

$BranchState = '';

if($TicketID)
{
    /* =====================================================
       STEP 2: GET BRANCH ID FROM corporate_tickets
    ===================================================== */
    $ticket_query = "
        SELECT BranchID 
        FROM corporate_tickets 
        WHERE ID = '$TicketID'
        AND IsActive = 1
        LIMIT 1
    ";

    $ticket_result = $conn->query($ticket_query);

    if($ticket_result && $ticket_result->num_rows > 0)
    {
        $ticket_row = $ticket_result->fetch_assoc();
        $BranchID   = $ticket_row['BranchID'];

        /* =====================================================
           STEP 3: GET BRANCH STATE FROM branch TABLE
        ===================================================== */
        $branch_query = "
            SELECT BranchState 
            FROM branch 
            WHERE ID = '$BranchID'
            AND IsActive = 1
            LIMIT 1
        ";

        $branch_result = $conn->query($branch_query);

        if($branch_result && $branch_result->num_rows > 0)
        {
            $branch_row  = $branch_result->fetch_assoc();
            $BranchState = $branch_row['BranchState'];
        }
    }
}

/* =====================================================
   STEP 4: CHECK STATE APPROVAL ACCESS
===================================================== */
if(!empty($BranchState))
{
    $state_check_query = "
        SELECT 1
        FROM user_quation_access
        WHERE EmployeeID = '$LoggedEmployeeID'
        AND IsApprovedState = 1
        AND IsActive = 1
        AND UPPER(StateName) = UPPER('$BranchState')
        LIMIT 1
    ";

    $state_check = $conn->query($state_check_query);

    if($state_check && $state_check->num_rows > 0){
        $view_approval_statemanager = 1;
    }
}

/* =====================================================
   STEP 5: CHECK FINANCE APPROVAL ACCESS
===================================================== */
$finance_check_query = "
    SELECT 1
    FROM user_quation_access
    WHERE EmployeeID = '$LoggedEmployeeID'
    AND IsApprovedFinance = 1
    AND IsActive = 1
    LIMIT 1
";

$finance_check = $conn->query($finance_check_query);

if($finance_check && $finance_check->num_rows > 0){
    $view_approval_financehead = 1;
}

$show_revised_date_option = (
    $QuotationID != -1
    && $QuotationStatus === 'Quote Sent Approval Pending'
    && $corporate_ticket_data['Status'] != 'Closed'
    && $UserType !== 'Corporate Branch User'
    && $UserType !== 'Corporate Admin'
);

?>

<div class="rows">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    View Quotation &nbsp;&nbsp; 
                    <span id="status_text">
                    <?php 
                    if($QuotationID != -1 && $view_quotation)
                    {
                    ?>
                        <span class="badge badge-danger"><?php echo $quotation_detail['QuotationStatus']; ?></span>
                    <?php
                    }
                    ?>
                    </span> 
                </h2>
                <?php 
                if($corporate_ticket_data['Status'] != "Closed")
                {
                    if($QuotationStatus == "Draft" || $QuotationStatus == "" || $QuotationStatus == "Quote Rejected by Client" || $quotation_detail['QuotationStatus'] == "Quote Rejected By State" || $quotation_detail['QuotationStatus'] == "Quote Rejected By Finance" )
                    {
                        if($UserType != 'Corporate Branch User')
                        {
                    ?>
                        <button type="button" onclick="UpdateQuotation(<?=$CorporateID;?>);" id="update_quotation_button_text" class="btn btn-sm btn-primary ml-3 mr-3"><?=$quotation_button_text;?></button>

                         <button type="button" onclick="AddNonARCItem(<?=$CorporateID;?>);" id="add_non_arc_button" class="btn btn-sm btn-primary ml-3 mr-3">Add Non ARC Item</button>
                  
                        <button type="button" onclick="UpdateQuotationStatus('Quote Sent Approval Pending');" id="update_quotation_status_button" style="display:none;" class="btn btn-sm btn-info ml-3 mr-3">Save & Submit</button>

                        <button type="button" onclick="GenerateQuotation(<?=$QuotationID;?>,'Download');" class="btn btn-sm btn-info ml-3 mr-3">Download</button>
                    <?php
                        }
                    }
                    if($QuotationStatus != "Draft" && $QuotationStatus != "")
                    {
                        ?>
                        <button type="button" onclick="QuotationShowRemarks(<?=$QuotationID;?>);" class="btn btn-sm btn-info ml-3 mr-3">View History</button>
                        <button type="button" onclick="GenerateQuotation(<?=$QuotationID;?>,'Download');" class="btn btn-sm btn-info ml-3 mr-3">Download</button>
                        <?php
                        if ($show_revised_date_option) {
                        ?>
                        <button type="button" onclick="openQuotationRevisedDateModal();" class="btn btn-sm btn-warning ml-3 mr-3">Revised Date</button>
                        <?php
                        }
                    }

                    if($view_approval)
                    {
                        ?>
                        <button type="button" onclick="UpdateQuotationStatus('Quote Approved');" class="btn btn-sm btn-success ml-3 mr-3">Approve</button>
                        <button type="button" onclick="UpdateQuotationStatus('Quote Rejected by Client');" class="btn btn-sm btn-danger ml-1 mr-3">Reject</button>
                        <?php
                    }
                     if($view_approval_statemanager)
                    {
                        ?>
                        <button type="button" onclick="UpdateQuotationStatus('StateApproved');" class="btn btn-sm btn-success ml-3 mr-3">Approve by State</button>
                        <button type="button" onclick="UpdateQuotationStatus('StateRejected');" class="btn btn-sm btn-danger ml-1 mr-3">Reject by State</button>

                    <?php
                    }
                    if($view_approval_financehead)
                    {
                        ?>
                         <button type="button" onclick="UpdateQuotationStatus('FinanceApproved');" class="btn btn-sm btn-success ml-3 mr-3">Approve by Finance</button>
                        <button type="button" onclick="UpdateQuotationStatus('FinanceRejected');" class="btn btn-sm btn-danger ml-1 mr-3">Reject by Finance</button>

                        <?php
                    }


                    if($reject_quotation)
                    {
                        ?>
                        <button type="button" onclick="UpdateQuotationStatus('Quote Rejected by Client');" class="btn btn-sm btn-danger ml-1 mr-3">Reject</button>
                        <?php
                    }
                }
                else
                {
                    if($QuotationID != -1)
                    {
                        ?>
                        <button type="button" onclick="QuotationShowRemarks(<?=$QuotationID;?>);" class="btn btn-sm btn-info ml-3 mr-3">View History</button>
                        <button type="button" onclick="GenerateQuotation(<?=$QuotationID;?>,'Download');" class="btn btn-sm btn-info ml-3 mr-3">Download</button>
                        <?php
                    }
                }
                ?>
                <input type="hidden" id="TicketQuotationID" value="<?=$QuotationID;?>" />
                <input type="hidden" id="Q_TicketID" value="<?=$ID;?>" />
            </div>

            
             <div class="panel-container show">
                <div class="panel-content" id="quotation_view">
                    <?php
                          if($QuotationID != -1 && $view_quotation)
                          {
                            if($view_quotation)
                            {
                                include("corporate_ticket_quotation_detail.php");
                            }
                            
                          }
                          else
                          {
                            ?>
                            <h5> Currently there are no Quotation associated with Ticket</h2>
                            <?php
                          }
                          ?>

                </div>
            </div>

        </div>
        <!-- panel-1 -->
    </div><!-- col-xl-12 -->
</div> <!-- row -->


<div class="row" id="rate_card_line_item_panel" style="display:none;">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    View Rate Card
                </h2>
                <button type="button" onclick="loadLineItems(<?=$CorporateID;?>);" class="btn btn-sm btn-primary ml-3 mr-3">Search</button>
                <!--a href="#" onclick="OpenCSVmodal()" class="btn btn-info"
                    style="margin-right:20px;">Upload CSV File</a-->
            </div>
           
            <div class="row">
                <div class="col-xl-12">
                    
                    <div class="panel-hdr">
                        <div class="col-2" style="width:100%;z-index: 1!important;">
                            <select class="form-control" name="filter_type" id="filter_type">
                                <option value="">Select Type</option>
                                <option value="Product">Product</option>
                                <option value="Service">Service</option>
                                <option value="Visit Charge">Visit Charge</option>
                            </select>
                        </div>
                        <div class="col-2" style="width:100%;z-index: 1!important;">
                            <select class="form-control" name="filter_category" id="filter_category" onchange="GetQuotationFilterSubCategories(this.value)">
                                <option value="">Select Category</option>
                                <?php
                                foreach ($categories_array as $category) 
                                {
                                    $CategoryName = $category['CategoriesName'];
                                ?>

                                    <option value="<?php echo $CategoryName ?>" data-filter-category-id="<?php echo $category['ID']; ?>"> <?php echo $CategoryName ?></option>

                                <?php 
                                }  
                                ?>
                            </select>
                        </div>
                        <div class="col-2" id="subcategory_div" style="width:100%;z-index: 1!important;">
                            <select class="form-control" name="filter_subcategory" id="filter_subcategory">
                                <option value="">Select Sub Category</option>
                            </select>
                        </div>
                        <input type="hidden" id="CorporateID" value="<?php echo $CorporateID; ?>" />
                        
                    </div>

                </div> <!-- col-xl-12 -->
            </div> <!-- row -->
            <!-- Filters -->
           
            
             <div class="panel-container show">
                <div class="panel-content">
                    <!-- datatable start -->
                    <table id="view-q-rate-card" class="table table-bordered table-hover table-striped w-100">
                        
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Category / Sub Category </th>
                                <th>Line Item</th>
                                <th>Make</th>
                                <th>HSN</th>
                                <th>ARC Code</th>
                                <th>UoM</th>
                                <th>Price</th>
                                <th>Tax</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                    </table>
                    
                    <!-- datatable end -->
                </div>
            </div>

        </div>
        <!-- panel-1 -->
    </div><!-- col-xl-12 -->
</div> <!-- row -->



<div class="modal fade bd-example-modal-sm" id="qty_modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;
    color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Add Line Item</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;
    color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="quotation_add_line_item_form">
                    <div id="wizard">
                        <section>
                            <div class="row">

                                 <div class="col-md-12 col-12">
                                    <div class="form_div"> 
                                        <label for="quantity">Quantity:</label>
                                        <input type="number" class="form-control quantity-box" id="quantity" name="quantity" value="1" min="1" oninput="checkQuantity(this)">

                                    </div>
                                </div>

                                <input type="hidden" name="LineItemID" id="LineItemID" value="" />


                            </div>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="clientid_change_btn"
                                        style="background-color: #2196f3;" onclick="AddLineItemtoQuotation()"
                                        value="Save">Add</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
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

                                <div class="form-group" id="quotation-quote-company-div" style="display:none;">
                                    <label class="form-label" for="quotation-quote-company-id">Quote company name<span class="text-danger">*</span></label>
                                    <select class="form-control" id="quotation-quote-company-id" name="quotation-quote-company-id">
                                        <option value="" selected>— Select —</option>
                                        <?php
                                        foreach ($quote_company_list as $qc) {
                                            $qid = (int) $qc['ID'];
                                            $qname = htmlspecialchars($qc['CompanyName'] ?? '', ENT_QUOTES, 'UTF-8');
                                            $sel = ($saved_quote_company_id === $qid) ? ' selected' : '';
                                            echo '<option value="' . $qid . '"' . $sel . '>' . $qname . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="form-group" id="quotation-tc-div" style="display:none;">
                                    <label class="form-label" for="quotation-remarks">Terms and Conditions</label>
                                    <!-- <input class="form-control" type="text" id="quotation-tc" name="quotation-tc" value=""></input> -->
                                     <textarea class="form-control" id="quotation-tc" name="quotation-tc" rows="5"></textarea>
                                </div>

                                <div class="form-group" style="display:none;" id="expected-budget-div">
                                    <label class="form-label" for="expected-budget">Expected Budget (₹)</label>
                                    <input 
                                        type="number"
                                        class="form-control"
                                        id="expected-budget"
                                        name="expected-budget"
                                        step="0.01"
                                        min="0"
                                        placeholder="e.g. 100000"
                                        value="<?php echo htmlspecialchars((string) $saved_expected_budget, ENT_QUOTES, 'UTF-8'); ?>"
                                        required
                                    />
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
                                    style="background-color: #2196f3;" onclick="syncQuotationEditorContent(); ModifyQuotationAction()" value="Save">Save</a>
                            </div>
                        </section>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/corporate_ticket_quotation_revised_date.php'; ?>
<div class="modal fade bd-example-modal-sm" id="remarks_quotation_modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Remarks</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="quotation_remarks_modal_body">

            </div>
        </div>
    </div>
</div>

<!-- Non ARC Item Modal -->
 <div class="modal fade" id="rateCardModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog  modal-xl" role="document" style="max-width: 95%; width: 95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">Add Non ARC Line Items</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="Quotation_Corporate_ID" value = "-1" />
                <table class="table" id="dynamicTable">
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>Category</th>
                        <th>SubCategory</th>
                        <th>LineItemName</th>
                        <th>Make</th>
                        <th>HSN</th>
                        <th>UoM</th>
                        <th>Price</th>
                        <th>Tax</th>
                        <th>Qty</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>
                            <select class="form-control" name="type[]">
                                <option value="">Please Select</option>
                                <option value="Product">Product</option>
                                <option value="Service">Service</option>
                                <option value="Visit Charge">Visit Charge</option>
                                <!-- Add more options as needed -->
                            </select>
                        </td>
                        <td>
                            <select class="form-control category-dropdown" name="category[]">
                                <option value="">Please Select</option>
                                <?php 
                                foreach($categories_array as $category)
                                {
                                    $CategoryName = $category['CategoriesName'];
                                    ?>
                                    <option value="<?=$CategoryName;?>" data-id="<?php echo $category['ID']; ?>"><?=$CategoryName;?></option>
                                    <?php
                                }
                                ?>
                                <option value="Others">Others</option>
                                <!-- Add more options as needed -->
                            </select>
                        </td>
                        <td>
                            <select class="form-control subcategory-dropdown" name="subcategory[]">
                                <option value="">Please Select</option>
                                
                                <!-- Add more options as needed -->
                            </select>
                        </td>
                        <td style="width:300px;"><input type="text" class="form-control" name="lineItemName[]"></td>
                        <td><input type="text" class="form-control" name="make[]"></td>
                        <td><input type="text" class="form-control numeric-input" name="hsn[]"></td>
                        <td>
                            <select class="form-control" name="uom[]">
                                <option value="">Please Select</option>
                                <?php 
                                foreach($uom_array as $uom)
                                {
                                    $UOMName = $uom['UOMName'];
                                    ?>
                                    <option value="<?=$UOMName;?>"><?=$UOMName;?></option>
                                    <?php
                                }
                                ?>
                                <option value="Others">Others</option>
                            </select>
                        </td>
                        
                        <td><input type="text" class="form-control numeric-input" name="price[]"></td>
                        <td><input type="text" class="form-control numeric-input" name="tax[]"></td>
                        <td><input type="text" class="form-control numeric-input" name="qty[]"></td>
                        <td><button type="button" class="btn btn-danger remove-row"><i class="fas fa-trash-alt"></i></button></td>
                    </tr>
                    </tbody>
                </table>
                <button type="button" class="btn btn-success" id="addRow">Add Row</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveRows">Save</button>
            </div>
        </div>
    </div>
</div>




<script type="text/javascript">
        // For Quotation Tab
        var QuotationID = document.getElementById("TicketQuotationID").value;
        if(QuotationID != -1)
        {
            document.getElementById("update_quotation_status_button").style.display = "";
        }
        else
        {
            document.getElementById("update_quotation_status_button").style.display = "none";
            document.getElementById("rate_card_line_item_panel").style.display = "none";
        }



     $('#addRow').click(function() {
        var newRow = `<tr>
                        <td>
                            <select class="form-control" name="type[]">
                                <option value="">Please Select</option>
                                <option value="Product">Product</option>
                                <option value="Service">Service</option>
                                <option value="Visit Charge">Visit Charge</option>
                            </select>
                        </td>
                        <td>
                            <select class="form-control category-dropdown" name="category[]">
                                <option value="">Please Select</option>
                                <?php 
                                foreach($categories_array as $category)
                                {
                                    $CategoryName = $category['CategoriesName'];
                                    ?>
                                    <option value="<?=$CategoryName;?>" data-id="<?php echo $category['ID']; ?>"><?=$CategoryName;?></option>
                                    <?php
                                }
                                ?>
                                <option value="Others">Others</option>
                            </select>
                        </td>
                        <td>
                            <select class="form-control subcategory-dropdown" name="subcategory[]">
                                <option value="">Please Select</option>
                                <!-- Subcategories will be dynamically populated -->
                            </select>
                        </td>
                        <td style="width:300px;"><input type="text" class="form-control" name="lineItemName[]"></td>
                        <td><input type="text" class="form-control" name="make[]"></td>
                        <td><input type="text" class="form-control numeric-input" name="hsn[]"></td>
                     
                        <td>
                            <select class="form-control" name="uom[]">
                                <option value="">Please Select</option>
                                <?php 
                                foreach($uom_array as $uom)
                                {
                                    $UOMName = $uom['UOMName'];
                                    ?>
                                    <option value="<?=$UOMName;?>"><?=$UOMName;?></option>
                                    <?php
                                }
                                ?>
                                <option value="Others">Others</option>
                            </select>
                        </td>
                       
                        <td><input type="text" class="form-control numeric-input" name="price[]"></td>
                        <td><input type="text" class="form-control numeric-input" name="tax[]"></td>
                         <td><input type="text" class="form-control numeric-input" name="qty[]"></td>
                        <td><button type="button" class="btn btn-danger remove-row">Remove</button></td>
                      </tr>`;
        $('#dynamicTable tbody').append(newRow);
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
    });

    // Handle category change
    $(document).on('change', '.category-dropdown', function() {
        var category_id = $(this).find('option:selected').data('id');
        var subcategoryDropdown = $(this).closest('tr').find('.subcategory-dropdown');
        subcategoryDropdown.empty();
        subcategoryDropdown.append('<option value="">Please Select</option>');
        
        $.post("../company/action/get_subcategories_rate_card.php", {
            CategoryID: category_id
        },
        function(data, status) {
            subcategoryDropdown.append(data);
            subcategoryDropdown.append('<option value="Others">Others</option>');
        });
    });

    // Save rows
    $('#saveRows').click(function() {
        var data = [];
        var isValid = true;
        var CorporateID = $("#Quotation_Corporate_ID").val();
        var TicketID = $("#Q_TicketID").val();
        var TicketQuotationID = $("#TicketQuotationID").val();
        $('#dynamicTable tbody tr').each(function() {
            var row = {
                type: $(this).find('select[name="type[]"]').val(),
                category: $(this).find('select[name="category[]"]').val(),
                category_id: $(this).find('select[name="category[]"] option:selected').data('id'),
                subcategory: $(this).find('select[name="subcategory[]"]').val(),
                lineItemName: $(this).find('input[name="lineItemName[]"]').val(),
                make: $(this).find('input[name="make[]"]').val(),
                hsn: $(this).find('input[name="hsn[]"]').val(),
                arccode: $(this).find('input[name="arccode[]"]').val(),
                uom: $(this).find('select[name="uom[]"]').val(),
                price: $(this).find('input[name="price[]"]').val(),
                tax: $(this).find('input[name="tax[]"]').val(),
                qty: $(this).find('input[name="qty[]"]').val(),
                CorporateID:CorporateID,
                QuotationID:TicketQuotationID,
                TicketID:TicketID
            };
            if (row.category === "" || row.lineItemName === "" || row.make === "" || row.price === "" || row.qty === "") {
                isValid = false;
                return false; // Exit the .each() loop
            }
            data.push(row);
        });

        if (!isValid) 
        {
            alert("Please fill out all required fields (Category, Line Item Name, Make, Price,Qty) in each row.");
            return;
        }

        $.ajax({
            url: 'action/insert_non-arc_items.php',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json; charset=utf-8',
            success: function(response) {
                data_response = JSON.parse(response);
                if(data_response.error == false)
                {
                      if(TicketQuotationID == -1)
                      {
                        var QuotationID = data_response.QuotationID;
                        $("#TicketQuotationID").val(QuotationID);
                        UpdateQuotationDisplay(QuotationID);
                        $("#update_quotation_button_text").text("Edit Quotation");
                        $("#status_text").html("<span class='badge badge-danger'>Draft</span>");
                        document.getElementById("update_quotation_status_button").style.display = "";
                      }
                      else
                      {
                        UpdateQuotationDisplay(TicketQuotationID);
                      }
                      $("#rateCardModal").modal("hide");
                }
                TechXAlert(data_response.message);
                location.reload();
                // Optionally, you can refresh the page or update the UI to reflect the new data
            },
            error: function(error) {
                console.log('Error:', error);
            }
        });
    });

    document.querySelectorAll('.numeric-input').forEach(function(input) 
    {
        input.addEventListener('input', function(e) {
            if (!/^\d*\.?\d*$/.test(this.value)) {
                this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');
            }
        });
    });

    $(document).on('input', '.numeric-input', function() {
        if (!/^\d*\.?\d*$/.test(this.value)) {
            this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');
        }
    });



    var tinyMceLoaderPromise = null;
    function loadTinyMceScript() {
        if (typeof tinymce !== 'undefined') {
            return Promise.resolve();
        }
        if (tinyMceLoaderPromise) {
            return tinyMceLoaderPromise;
        }

        tinyMceLoaderPromise = new Promise(function(resolve, reject) {
            var script = document.createElement('script');
            script.src = "https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js";
            script.onload = function() { resolve(); };
            script.onerror = function() { reject(new Error("TinyMCE script failed to load.")); };
            document.head.appendChild(script);
        });

        return tinyMceLoaderPromise;
    }

    function initQuotationTextEditors() {
        loadTinyMceScript()
            .then(function() {
                ['quotation-remarks', 'quotation-tc'].forEach(function(editorId) {
                    if (!document.getElementById(editorId) || tinymce.get(editorId)) {
                        return;
                    }

                    tinymce.init({
                        selector: '#' + editorId,
                        menubar: false,
                        branding: false,
                        height: 180,
                        plugins: 'lists link code',
                        toolbar: 'undo redo | bold italic underline | bullist numlist | link | removeformat | code',
                        setup: function(editor) {
                            editor.on('change keyup', function() {
                                editor.save();
                            });
                        }
                    });
                });
            })
            .catch(function() {
                console.log("TinyMCE unavailable, falling back to textarea.");
            });
    }

    function syncQuotationEditorContent() {
        if (typeof tinymce === 'undefined') {
            return;
        }

        ['quotation-remarks', 'quotation-tc'].forEach(function(editorId) {
            var editor = tinymce.get(editorId);
            if (editor) {
                editor.save();
            }
        });
    }

    $('#save_submit_quotation_modal').on('shown.bs.modal', function() {
        setTimeout(initQuotationTextEditors, 100);
    });


        // For Edit Single Line Item Modal
$("#editCategory").on('change', function() {
    var category_id = $(this).find('option:selected').data('id');
    var subcategoryDropdown = $("#editSubCategory");
    subcategoryDropdown.empty();
    subcategoryDropdown.append('<option value="">Please Select</option>');

    $.post("../company/action/get_subcategories_rate_card.php", {
        CategoryID: category_id
    },
    function(data, status) {
        subcategoryDropdown.append(data);
        subcategoryDropdown.append('<option value="Others">Others</option>');
    });
});

</script>