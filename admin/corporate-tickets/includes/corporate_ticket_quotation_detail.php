<?php
@session_start();
if(isset($_POST['ajax']))
{
    require_once('../../includes/autoloader.inc.php');
    $dbh = new Dbh();
    $conn = $dbh->_connectodb();
    $QuotationID = $_POST['QuotationID'];
    
    $core = new Core($conn);
    $uom_array = $core->_getTableRecords($conn,'manage_uom',' where 1');
    
    $categories_obj = new Category($conn);
    $categories_array = $categories_obj->getAllCategories();
}
else
{

}
$corporateticket = new Corporateticket($conn);
// Get Line Items information 
$expectedbudget=0;
$quotation_detail = $corporateticket->GetQuotationDetailbyID($QuotationID); 
$q_line_items = $corporateticket->GetQuotationLineItems($QuotationID);
$expectedbudget=$quotation_detail['expectedbudget'];
$quotationViewerUserType = isset($UserType) ? $UserType : ($_SESSION['UserType'] ?? '');
$showExpectedBudget = !in_array($quotationViewerUserType, array('Corporate Admin', 'Corporate Branch User', 'Corporate User'), true);
if(sizeof($q_line_items) > 0)
{
?>






<div class="container">
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
                            <th class="border-top-0 table-scale-border-bottom fw-700">UOM</th>
                            <th class="border-top-0 table-scale-border-bottom fw-700">ARC Code</th>
                            <th class="text-left border-top-0 table-scale-border-bottom fw-700">Unit Cost</th>
                            <th class="text-left border-top-0 table-scale-border-bottom fw-700">Tax</th>
                            <th class="text-left border-top-0 table-scale-border-bottom fw-700">Qty</th>
                            <th class="text-left border-top-0 table-scale-border-bottom fw-700">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i=1;
                        $SuperTotal = 0;
                        $total_tax = 0;
                        foreach($q_line_items as $line_item)
                        {
                            $SuperTotal = $SuperTotal +  $line_item['TotalPrice'];

                        ?>
                            <tr>
                                <td class="text-center fw-700">
                                    <?php 
                                    if($quotation_detail['QuotationStatus'] == "Draft" || $quotation_detail['QuotationStatus'] == "Quote Rejected by Client" ||  $quotation_detail['QuotationStatus'] == "Quote Sent Approval Pending" || $quotation_detail['QuotationStatus'] == "Quote Rejected By State" || $quotation_detail['QuotationStatus'] == "Quote Rejected By Finance" )
                                    {
                                        ?>
                                         <a onclick="EditLineItemFromQuotation(<?php echo $line_item['ID'];?>,<?php echo $QuotationID; ?>);">
                                            <i class="fas fa-edit text-primary"></i>
                                        </a>&nbsp;
                                        <a onclick="DeleteLineItemFromQuotation(<?php echo $line_item['ID'];?>,<?php echo $QuotationID; ?>);">
                                            <i class="fas fa-trash text-danger"></i>
                                        </a>&nbsp;
                                        <?php
                                    }
                                    $tax = $line_item['Tax'];
                                    $tax_html = "";
                                    if($tax == "" || $tax == "0")
                                    {
                                        $tax_html = "0";
                                    }
                                    if($tax != 0 && $tax != "")
                                    {
                                        $tax_html = $tax."%";
                                        $total_tax = $total_tax+round((($line_item['TotalPrice'] * $tax)/100),2);
                                    }
                                    ?>
                                    
                                    <?php echo $i; ?>
                                </td>
                                <td class="text-left strong"><?php echo $line_item['LineItemName']; ?></td>
                                <td class="text-left"><?php echo $line_item['Category']; ?></td>
                                <td class="text-left"><?php echo $line_item['Make']; ?></td>
                                <td class="text-left"><?php echo $line_item['HSN']; ?></td>
                                 <td class="text-left"><?php echo $line_item['UoM']; ?></td>
                                <td class="text-left"><?php echo $line_item['ARCCode']; ?></td>
                                <td class="text-left"><?php echo $line_item['PerItemPrice']; ?></td>
                                <td class="text-left"><?php echo $tax_html; ?></td>
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
                    <tr class="table-scale border-left-0 border-right-0 border-bottom-0">
                        <td class="text-left keep-print-font">
                            <h4 class="m-0  keep-print-font color-primary-700">Total</h4>
                        </td>
                        <td class="text-right keep-print-font">
                            <h4 class="m-0 keep-print-font">&#8377;<?php echo $SuperTotal; ?></h4>
                        </td>
                    </tr>
                    <tr class="table-scale border-left-0 border-right-0 border-bottom-0">
                        <td class="text-left keep-print-font">
                            <h4 class="m-0 keep-print-font color-primary-700">Tax</h4>
                        </td>
                        <td class="text-right keep-print-font">
                            <h4 class="m-0 keep-print-font">&#8377;<?php echo $total_tax; ?></h4>
                        </td>
                    </tr>
                    
                    <tr class="table-scale-border-top border-left-0 border-right-0 border-bottom-0">
                        <td class="text-left keep-print-font">
                            <h4 class="m-0 fw-700 h2 keep-print-font color-primary-700">Total (Including Tax)</h4>
                        </td>
                        <td class="text-right keep-print-font">
                            <h4 class="m-0 fw-700 h2 keep-print-font">&#8377;<?php echo ($SuperTotal+$total_tax); ?></h4>
                        </td>
                    </tr>




                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
}
?>

<!-- Edit Single Line Item Modal -->
<div class="modal fade" id="editLineItemModal" tabindex="-1" role="dialog" aria-labelledby="editLineItemLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 95%; width: 95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editLineItemLabel">Edit Line Item</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editLineItemID" name="line_item_id" />
                <input type="hidden" id="editQuotationID" name="quotation_id" />
                <input type="hidden" id="editQuotationItems" name="quotation_items" />

                <div class="row">
                    <div class="col-md-2">
                        <label>Type</label>
                        <select class="form-control" id="editType" name="type">
                            <option value="">Please Select</option>
                            <option value="Product">Product</option>
                            <option value="Service">Service</option>
                            <option value="Visit Charge">Visit Charge</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Category</label>
                        <select class="form-control" id="editCategory" name="category">
                            <option value="">Please Select</option>
                            <?php foreach($categories_array as $category) { ?>
                                <option value="<?=$category['CategoriesName'];?>" data-id="<?=$category['ID'];?>"><?=$category['CategoriesName'];?></option>
                            <?php } ?>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>SubCategory</label>
                        <select class="form-control subcategory-dropdown" id="editSubCategory" name="subcategory">
                            <option value="">Please Select</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Line Item Name</label>
                        <input type="text" class="form-control" id="editLineItemName" name="lineItemName">
                    </div>
                    <div class="col-md-2">
                        <label>Make</label>
                        <input type="text" class="form-control" id="editMake" name="make">
                    </div>
                    <div class="col-md-1">
                        <label>HSN</label>
                        <input type="text" class="form-control numeric-input" id="editHSN" name="hsn">
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-2">
                        <label>UoM</label>
                        <select class="form-control" id="editUOM" name="uom">
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
                    </div>
                    <div class="col-md-2">
                        <label>Price</label>
                        <input type="text" class="form-control numeric-input" id="editPrice" name="price">
                    </div>
                    <div class="col-md-2">
                        <label>Tax (%)</label>
                        <input type="text" class="form-control numeric-input" id="editTax" name="tax">
                    </div>
                    <div class="col-md-2">
                        <label>Quantity</label>
                        <input type="text" class="form-control numeric-input" id="editQty" name="qty">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="updateLineItem">Update</button>
            </div>
        </div>
    </div>
</div>


<?php if($showExpectedBudget) { ?>
<h6>expectedbudget:<?php echo $expectedbudget ?></h6>
<?php } ?>