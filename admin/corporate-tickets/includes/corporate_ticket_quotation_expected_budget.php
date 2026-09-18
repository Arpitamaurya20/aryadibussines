<?php
/**
 * Expected budget modal — shown when quotation is "Quote Sent Approval Pending".
 * Included from corporate_ticket_quotation_tab.php
 */
$saved_expected_budget_modal = '';
if (!empty($quotation_detail) && is_array($quotation_detail)) {
    $saved_expected_budget_modal = trim((string) ($quotation_detail['expectedbudget'] ?? ''));
}
?>
<div class="modal fade bd-example-modal-sm" id="quotation_expected_budget_modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Update Expected Budget</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 col-12">
                        <div class="form-group">
                            <label class="form-label" for="quotation-expected-budget-input">Expected Budget <span class="text-danger">*</span></label>
                            <input
                                class="form-control numeric-input"
                                type="text"
                                id="quotation-expected-budget-input"
                                name="quotation-expected-budget-input"
                                value="<?php echo htmlspecialchars($saved_expected_budget_modal, ENT_QUOTES, 'UTF-8'); ?>"
                                autocomplete="off"
                                placeholder="Enter expected budget"
                            />
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center mt-3">
                    <a
                        class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer"
                        id="save_quotation_expected_budget_button"
                        style="background-color: #2196f3;"
                        onclick="saveQuotationExpectedBudget()"
                    >Save</a>
                </div>
            </div>
        </div>
    </div>
</div>
