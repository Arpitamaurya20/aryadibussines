<?php
/**
 * Rate card + non-ARC modals for adding line items when quotation is "Quote Sent Approval Pending".
 * Included from corporate_ticket_quotation_tab.php
 */
?>
<div class="modal fade" id="pending_approval_rate_card_modal" tabindex="-1" role="dialog" aria-labelledby="pendingApprovalRateCardLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 95%; width: 95%;">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1; color: #fff;">
                <h5 class="modal-title" id="pendingApprovalRateCardLabel">Add Line Item — Rate Card</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1; color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <select class="form-control" name="pending_filter_type" id="pending_filter_type">
                            <option value="">Select Type</option>
                            <option value="Product">Product</option>
                            <option value="Service">Service</option>
                            <option value="Visit Charge">Visit Charge</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" name="pending_filter_category" id="pending_filter_category" onchange="GetPendingQuotationFilterSubCategories(this.value)">
                            <option value="">Select Category</option>
                            <?php
                            foreach ($categories_array as $category) {
                                $CategoryName = $category['CategoriesName'];
                                ?>
                                <option value="<?php echo $CategoryName ?>" data-pending-filter-category-id="<?php echo $category['ID']; ?>"><?php echo $CategoryName ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3" id="pending_subcategory_div">
                        <select class="form-control" name="pending_filter_subcategory" id="pending_filter_subcategory">
                            <option value="">Select Sub Category</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" onclick="loadPendingApprovalLineItems(<?php echo (int) $CorporateID; ?>);" class="btn btn-primary">Search</button>
                    </div>
                </div>
                <table id="view-q-rate-card-pending" class="table table-bordered table-hover table-striped w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Category / Sub Category</th>
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
            </div>
        </div>
    </div>
</div>

<div class="modal fade bd-example-modal-sm" id="pending_qty_modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1; color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Add Line Item</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1; color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="pending_quotation_add_line_item_form">
                    <div class="row">
                        <div class="col-md-12 col-12">
                            <div class="form_div">
                                <label for="pending_quantity">Quantity:</label>
                                <input type="number" class="form-control quantity-box" id="pending_quantity" name="pending_quantity" value="1" min="1" oninput="checkPendingQuantity(this)">
                            </div>
                        </div>
                        <input type="hidden" name="pending_LineItemID" id="pending_LineItemID" value="" />
                    </div>
                    <div class="row justify-content-center mt-3">
                        <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer"
                            id="pending_add_line_item_btn"
                            style="background-color: #2196f3;" onclick="AddLineItemtoQuotationPending(); return false;">Add</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rateCardModalPending" tabindex="-1" role="dialog" aria-labelledby="rateCardModalPendingLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 95%; width: 95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rateCardModalPendingLabel">Add Non ARC Line Items</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="Quotation_Corporate_ID_Pending" value="-1" />
                <table class="table" id="dynamicTablePending">
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
                                <select class="form-control" name="pending_type[]">
                                    <option value="">Please Select</option>
                                    <option value="Product">Product</option>
                                    <option value="Service">Service</option>
                                    <option value="Visit Charge">Visit Charge</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-control pending-category-dropdown" name="pending_category[]">
                                    <option value="">Please Select</option>
                                    <?php
                                    foreach ($categories_array as $category) {
                                        $CategoryName = $category['CategoriesName'];
                                        ?>
                                        <option value="<?php echo $CategoryName; ?>" data-id="<?php echo $category['ID']; ?>"><?php echo $CategoryName; ?></option>
                                        <?php
                                    }
                                    ?>
                                    <option value="Others">Others</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-control pending-subcategory-dropdown" name="pending_subcategory[]">
                                    <option value="">Please Select</option>
                                </select>
                            </td>
                            <td style="width:300px;"><input type="text" class="form-control" name="pending_lineItemName[]"></td>
                            <td><input type="text" class="form-control" name="pending_make[]"></td>
                            <td><input type="text" class="form-control pending-numeric-input" name="pending_hsn[]"></td>
                            <td>
                                <select class="form-control" name="pending_uom[]">
                                    <option value="">Please Select</option>
                                    <?php
                                    foreach ($uom_array as $uom) {
                                        $UOMName = $uom['UOMName'];
                                        ?>
                                        <option value="<?php echo $UOMName; ?>"><?php echo $UOMName; ?></option>
                                        <?php
                                    }
                                    ?>
                                    <option value="Others">Others</option>
                                </select>
                            </td>
                            <td><input type="text" class="form-control pending-numeric-input" name="pending_price[]"></td>
                            <td><input type="text" class="form-control pending-numeric-input" name="pending_tax[]"></td>
                            <td><input type="text" class="form-control pending-numeric-input" name="pending_qty[]"></td>
                            <td><button type="button" class="btn btn-danger pending-remove-row"><i class="fas fa-trash-alt"></i></button></td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="btn btn-success" id="addRowPending">Add Row</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveRowsPending">Save</button>
            </div>
        </div>
    </div>
</div>
