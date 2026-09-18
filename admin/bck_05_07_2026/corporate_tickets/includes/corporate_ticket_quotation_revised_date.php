<?php
/**
 * Revised / expiry date modal — shown when quotation is "Quote Sent Approval Pending".
 * Included from corporate_ticket_quotation_tab.php
 */
$saved_quotation_date = '';
$saved_quotation_expiry_date = '';
if (!empty($quotation_detail) && is_array($quotation_detail)) {
    $saved_quotation_date = trim((string) ($quotation_detail['QuotationDate'] ?? ''));
    $saved_quotation_expiry_date = trim((string) ($quotation_detail['QuotationExpiryDate'] ?? ''));
}
?>
<div class="modal fade bd-example-modal-sm" id="quotation_revised_date_modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Update Revised &amp; Expiry Date</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 col-12">
                        <div class="form-group">
                            <label class="form-label" for="quotation-revised-date">Revised Date <span class="text-danger">*</span></label>
                            <input
                                class="form-control"
                                type="text"
                                id="quotation-revised-date"
                                name="quotation-revised-date"
                                value="<?php echo htmlspecialchars($saved_quotation_date, ENT_QUOTES, 'UTF-8'); ?>"
                                autocomplete="off"
                            />
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="quotation-revised-expiry-date">Expiry Date <span class="text-danger">*</span></label>
                            <input
                                class="form-control"
                                type="text"
                                id="quotation-revised-expiry-date"
                                name="quotation-revised-expiry-date"
                                value="<?php echo htmlspecialchars($saved_quotation_expiry_date, ENT_QUOTES, 'UTF-8'); ?>"
                                autocomplete="off"
                            />
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center mt-3">
                    <a
                        class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer"
                        id="save_quotation_revised_date_button"
                        style="background-color: #2196f3;"
                        onclick="saveQuotationRevisedDates()"
                    >Save</a>
                </div>
            </div>
        </div>
    </div>
</div>
