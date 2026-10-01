/**
 * BAM / State Manager ticket verification UI.
 * BAM: WCC, Vendor Payment, Digital Report, Line Items (+ partial on qty change)
 * State: Quality + GRN (Quoted vs Actual)
 */

function smvEscapeHtml(value) {
    if (typeof $ !== 'undefined') {
        return $('<div>').text(value || '').html();
    }
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function smvReloadTicketTables() {
    if ($.fn.DataTable && $('#view-corporate-tickets').length && $.fn.DataTable.isDataTable('#view-corporate-tickets')) {
        $('#view-corporate-tickets').DataTable().ajax.reload(null, false);
    }
}

function smvResetPoSection() {
    $('#smv_CustomerPoAvailable').prop('checked', false).prop('disabled', false);
    $('#smv_CustomerPoRemarks').val('').prop('disabled', false);
    $('#smvPoRemarksWrap').hide();
    $('#smvPoSection').show();
}

function smvPopulatePoSection(res) {
    var isVerified = !!(res && res.is_verified);
    var poAvailable = !!(res && res.customer_po_available);
    var poRemarks = (res && res.customer_po_remarks) ? res.customer_po_remarks : '';

    $('#smv_CustomerPoAvailable').prop('checked', poAvailable);
    $('#smv_CustomerPoRemarks').val(poRemarks);

    if (isVerified) {
        $('#smv_CustomerPoAvailable').prop('disabled', true);
        $('#smv_CustomerPoRemarks').prop('disabled', true);
    }

    if (poAvailable) {
        $('#smvPoRemarksWrap').show();
    } else {
        $('#smvPoRemarksWrap').hide();
    }
}

function smvTogglePoRemarksField() {
    var isChecked = $('#smv_CustomerPoAvailable').is(':checked');
    var isVerified = $('#smv_is_verified').val() === '1';
    if (isChecked) {
        $('#smvPoRemarksWrap').show();
        if (!isVerified) {
            $('#smv_CustomerPoRemarks').prop('disabled', false);
        }
    } else {
        $('#smvPoRemarksWrap').hide();
        if (!isVerified) {
            $('#smv_CustomerPoRemarks').val('').prop('disabled', false);
        }
    }
}

function smvShowSubmitError(message) {
    if (!message) {
        $('#smvSubmitError').hide().empty();
        return;
    }
    $('#smvSubmitError').text(message).show();
    if (typeof toastr !== 'undefined') {
        toastr.error(message);
    }
}

function smvGetIncompleteChecklistLabels() {
    var missing = [];
    $('#smvChecklistForm .smv-check-item').each(function () {
        if (!$(this).is(':checked')) {
            var label = $('label[for="' + $(this).attr('id') + '"]').text() || $(this).attr('name') || 'checklist item';
            missing.push($.trim(label.replace(/\s+/g, ' ')));
        }
    });
    return missing;
}

function smvRefreshVerifySubmitState() {
    var isVerified = $('#smv_is_verified').val() === '1';
    $('#smvSubmitBtn').prop('disabled', isVerified);
    if (!isVerified) {
        smvShowSubmitError('');
    }
}

function smvFormatMoney(value) {
    var num = parseFloat(value);
    if (isNaN(num)) {
        return '0.00';
    }
    return num.toFixed(2);
}

function smvRenderGrnSection(grn) {
    if (!grn || grn.error) {
        return '<div class="text-danger">' + smvEscapeHtml((grn && grn.message) ? grn.message : 'Unable to load GRN amounts.') + '</div>';
    }
    var html = '<div class="row">';
    html += '<div class="col-md-4 mb-2"><div class="border rounded p-2 bg-white"><div class="text-muted">Quoted Amount (Sell)</div><strong>₹ ' + smvFormatMoney(grn.quoted_amount) + '</strong></div></div>';
    html += '<div class="col-md-4 mb-2"><div class="border rounded p-2 bg-white"><div class="text-muted">Actual Amount (Paid)</div><strong>₹ ' + smvFormatMoney(grn.actual_amount) + '</strong></div></div>';
    html += '<div class="col-md-4 mb-2"><div class="border rounded p-2 bg-white"><div class="text-muted">Difference</div><strong>₹ ' + smvFormatMoney(grn.difference) + '</strong> <span class="badge badge-secondary">' + smvEscapeHtml(grn.status || '') + '</span></div></div>';
    html += '</div>';
    html += '<div class="text-muted mt-1">Actual = Vendor payments paid + Convenience paid. Expected budget: ₹ ' + smvFormatMoney(grn.expected_budget) + '</div>';
    return html;
}

function smvRenderChecklist(res) {
    var html = '';
    var isAmc = String((res && res.ticket_type) || '').toLowerCase() === 'amc';
    var layer = (res && res.layer) || 'bam';
    $.each(res.required_keys, function (idx, key) {
        var label = res.labels[key] || key;
        var checked = res.checks && res.checks[key] ? 'checked' : '';
        var disabled = res.is_verified ? 'disabled' : '';
        html += '<div class="custom-control custom-checkbox mb-3">';
        if (key === 'LineItemVerified') {
            var reviewedAttr = checked ? 'data-reviewed="1"' : 'data-reviewed="0"';
            var reviewBtnLabel = isAmc ? 'Review / Edit Spare Parts' : 'Review / Edit Line Items';
            html += '<input type="checkbox" class="custom-control-input smv-check-item smv-line-item-check" id="smv_' + key + '" name="' + key + '" value="1" ' + checked + ' ' + disabled + ' ' + reviewedAttr + '>';
            html += '<label class="custom-control-label" for="smv_' + key + '">' + label + ' <strong>(Yes)</strong></label>';
            html += ' <button type="button" class="btn btn-sm btn-outline-primary ml-2 smv-open-line-items-btn">' + reviewBtnLabel + '</button>';
        } else if (key === 'GrnChecked') {
            html += '<input type="checkbox" class="custom-control-input smv-check-item" id="smv_' + key + '" name="' + key + '" value="1" ' + checked + ' ' + disabled + '>';
            html += '<label class="custom-control-label" for="smv_' + key + '">' + label + ' <strong>(Yes)</strong></label>';
            html += ' <button type="button" class="btn btn-sm btn-outline-primary ml-2 smv-open-line-items-btn">Review Quoted vs Actual Qty</button>';
        } else {
            html += '<input type="checkbox" class="custom-control-input smv-check-item" id="smv_' + key + '" name="' + key + '" value="1" ' + checked + ' ' + disabled + '>';
            html += '<label class="custom-control-label" for="smv_' + key + '">' + label + ' <strong>(Yes)</strong></label>';
        }
        html += '</div>';
    });
    return html;
}

function smvRenderLineItemRows(items, canEdit, mode) {
    mode = mode || 'quotation';
    if (!items || items.length === 0) {
        if (mode === 'spare_part') {
            return '<div class="alert alert-warning mb-0">No spare part request found for this AMC ticket.</div>';
        }
        return '<div class="alert alert-warning mb-0">No quotation line items found for this ticket.</div>';
    }

    var html = '<div class="table-responsive"><table class="table table-bordered table-sm mb-0">';
    html += '<thead><tr>';
    if (mode === 'spare_part') {
        html += '<th>#</th><th>Code</th><th>Spare Part Name</th><th>UoM</th><th>Qty</th><th>Price</th><th>Total</th>';
    } else {
        html += '<th>#</th><th>Type</th><th>Line Item Name</th><th>HSN</th><th>UoM</th><th>Quoted Qty</th><th>Actual Qty</th><th>Price</th><th>Total</th>';
    }
    if (canEdit) {
        html += '<th>Action</th>';
    }
    html += '</tr></thead><tbody>';

    $.each(items, function (idx, item) {
        if (mode === 'spare_part') {
            var spareItemId = item.SpareItemID;
            html += '<tr data-spare-item-id="' + spareItemId + '" data-mode="spare_part">';
            html += '<td>' + (idx + 1) + '</td>';
            html += '<td>' + smvEscapeHtml(item.SparePartCode || '') + '</td>';
            if (canEdit) {
                html += '<td><input type="text" class="form-control form-control-sm smv-li-name" value="' + smvEscapeHtml(item.SparePart || '') + '"></td>';
                html += '<td>' + smvEscapeHtml(item.UoM || '') + '</td>';
                html += '<td style="max-width:110px;"><input type="number" min="0.01" step="0.01" class="form-control form-control-sm smv-li-qty" value="' + smvEscapeHtml(String(item.Qty)) + '"></td>';
                html += '<td class="smv-li-price">' + smvFormatMoney(item.Price) + '</td>';
                html += '<td class="smv-li-total">' + smvFormatMoney(item.TotalAmount) + '</td>';
                html += '<td><button type="button" class="btn btn-sm btn-primary smv-save-line-item-btn" data-mode="spare_part" data-item-id="' + spareItemId + '">Save</button></td>';
            } else {
                html += '<td>' + smvEscapeHtml(item.SparePart || '') + '</td>';
                html += '<td>' + smvEscapeHtml(item.UoM || '') + '</td>';
                html += '<td>' + smvEscapeHtml(String(item.Qty)) + '</td>';
                html += '<td>' + smvFormatMoney(item.Price) + '</td>';
                html += '<td>' + smvFormatMoney(item.TotalAmount) + '</td>';
            }
            html += '</tr>';
            return;
        }

        var itemId = item.QuotationItemID;
        var quotedQty = item.Qty != null ? item.Qty : 0;
        var actQty = (item.ActQty != null && item.ActQty !== '') ? item.ActQty : quotedQty;
        html += '<tr data-quotation-item-id="' + itemId + '" data-mode="quotation" data-quoted-qty="' + smvEscapeHtml(String(quotedQty)) + '">';
        html += '<td>' + (idx + 1) + '</td>';
        html += '<td>' + smvEscapeHtml(item.Type || '') + '</td>';
        if (canEdit) {
            html += '<td><input type="text" class="form-control form-control-sm smv-li-name" value="' + smvEscapeHtml(item.LineItemName || '') + '"></td>';
            html += '<td>' + smvEscapeHtml(item.HSN || '') + '</td>';
            html += '<td>' + smvEscapeHtml(item.UoM || '') + '</td>';
            html += '<td class="smv-li-quoted-qty">' + smvEscapeHtml(String(quotedQty)) + '</td>';
            html += '<td style="max-width:110px;"><input type="number" min="0.01" step="0.01" class="form-control form-control-sm smv-li-actqty" value="' + smvEscapeHtml(String(actQty)) + '"></td>';
            html += '<td class="smv-li-price">' + smvFormatMoney(item.PerItemPrice || item.Price) + '</td>';
            html += '<td class="smv-li-total">' + smvFormatMoney(item.TotalPrice) + '</td>';
            html += '<td><button type="button" class="btn btn-sm btn-primary smv-save-line-item-btn" data-mode="quotation" data-item-id="' + itemId + '">Save</button></td>';
        } else {
            html += '<td>' + smvEscapeHtml(item.LineItemName || '') + '</td>';
            html += '<td>' + smvEscapeHtml(item.HSN || '') + '</td>';
            html += '<td>' + smvEscapeHtml(item.UoM || '') + '</td>';
            html += '<td>' + smvEscapeHtml(String(quotedQty)) + '</td>';
            html += '<td>' + smvEscapeHtml(String(actQty)) + '</td>';
            html += '<td>' + smvFormatMoney(item.PerItemPrice || item.Price) + '</td>';
            html += '<td>' + smvFormatMoney(item.TotalPrice) + '</td>';
        }
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    return html;
}

function smvLoadLineItemAudit(quotationId, ticketPK) {
    var payload = {};
    if (quotationId && quotationId !== '0') {
        payload.QuotationID = quotationId;
    } else if (ticketPK) {
        payload.TicketPK = ticketPK;
    } else {
        $('#smvLineItemAuditWrap').html('<div class="text-muted small">No history source selected.</div>');
        return;
    }
    $('#smvLineItemAuditWrap').html('<div class="text-muted small">Loading history...</div>');
    $.post('action/get_sm_quotation_line_item_audit.php', payload, function (html) {
        $('#smvLineItemAuditWrap').html(html || '<div class="text-muted small">No history.</div>');
    }).fail(function () {
        $('#smvLineItemAuditWrap').html('<div class="text-danger small">Unable to load edit history.</div>');
    });
}

function smvOpenLineItemModal(ticketPK) {
    ticketPK = parseInt(ticketPK, 10) || 0;
    if (ticketPK <= 0) {
        if (typeof toastr !== 'undefined') {
            toastr.warning('Ticket not found for verification items.');
        } else {
            alert('Ticket not found for verification items.');
        }
        return;
    }

    $('#smv_li_ticket_pk').val(ticketPK);
    $('#smv_li_quotation_id').val('');
    $('#smv_li_mode').val('quotation');
    $('#smv_li_ticket_id_display').text('');
    $('#smv_li_quotation_status_display').text('');
    $('#smvLineItemModalLabel').text('Line Item Verification');
    $('#smvLineItemHelpText').html('Quoted <strong>Qty</strong> stays unchanged. Enter <strong>Actual Qty</strong> only if used quantity is different. Actual Qty cannot exceed Quoted Qty — raise a new ticket for extra quantity.');
    $('#smvLineItemTableWrap').html('<div class="text-muted">Loading items...</div>');
    $('#smvLineItemAuditWrap').html('<div class="text-muted small">Loading history...</div>');
    $('#smvConfirmLineItemsBtn').prop('disabled', false).show().text('Confirm Line Items Verified');
    $('#smvLineItemModal').modal('show');

    $.post('action/get_sm_quotation_line_items.php', { TicketPK: ticketPK }, function (res) {
        if (!res || res.error) {
            $('#smvLineItemTableWrap').html('<div class="alert alert-danger mb-0">' + smvEscapeHtml((res && res.message) ? res.message : 'Unable to load items.') + '</div>');
            $('#smvConfirmLineItemsBtn').hide();
            return;
        }

        var mode = res.mode || 'quotation';
        $('#smv_li_mode').val(mode);
        $('#smv_li_ticket_id_display').text(res.ticket_id || '');
        $('#smv_li_quotation_status_display').text(res.quotation_status || '');
        $('#smv_li_quotation_id').val(res.quotation_id || '');

        if (mode === 'spare_part') {
            $('#smvLineItemModalLabel').text('Spare Part Request Verification');
            $('#smvLineItemHelpText').html('AMC ticket — review <strong>spare part requests</strong>. Changing quantity marks the ticket as <strong>partially verified</strong>.');
            $('#smvConfirmLineItemsBtn').text('Confirm Spare Parts Verified');
        }

        $('#smvLineItemTableWrap').html(smvRenderLineItemRows(res.items || [], !!res.can_edit, mode));

        if (!res.can_edit) {
            $('#smvConfirmLineItemsBtn').hide();
        }

        smvLoadLineItemAudit(res.quotation_id, ticketPK);
    }, 'json').fail(function () {
        $('#smvLineItemTableWrap').html('<div class="alert alert-danger mb-0">Unable to load items.</div>');
        $('#smvConfirmLineItemsBtn').hide();
    });
}

function smvSaveLineItemRow($btn) {
    var $row = $btn.closest('tr');
    var mode = $btn.data('mode') || $row.data('mode') || $('#smv_li_mode').val() || 'quotation';
    var itemId = parseInt($btn.data('item-id'), 10) || 0;
    var name = ($row.find('.smv-li-name').val() || '').trim();
    var qty = ($row.find('.smv-li-qty').val() || $row.find('.smv-li-actqty').val() || '').trim();
    var quotedQty = parseFloat($row.data('quoted-qty') || $row.find('.smv-li-quoted-qty').text()) || 0;
    var price = parseFloat($row.find('.smv-li-price').text()) || 0;

    if (!itemId) {
        return;
    }
    if (!name) {
        if (typeof toastr !== 'undefined') {
            toastr.error(mode === 'spare_part' ? 'Spare part name is required.' : 'Line item name is required.');
        } else {
            alert(mode === 'spare_part' ? 'Spare part name is required.' : 'Line item name is required.');
        }
        return;
    }
    if (!qty || isNaN(parseFloat(qty)) || parseFloat(qty) <= 0) {
        if (typeof toastr !== 'undefined') {
            toastr.error('Quantity must be greater than zero.');
        } else {
            alert('Quantity must be greater than zero.');
        }
        return;
    }
    if (mode === 'quotation' && quotedQty > 0 && parseFloat(qty) > quotedQty) {
        var extra = (parseFloat(qty) - quotedQty).toFixed(2);
        var exceedMsg = 'Actual qty cannot exceed quoted qty (' + quotedQty + '). Raise a new ticket for extra qty of ' + extra + '.';
        if (typeof toastr !== 'undefined') {
            toastr.error(exceedMsg);
        } else {
            alert(exceedMsg);
        }
        return;
    }

    var payload = {
        Mode: mode,
        Qty: qty,
        ActQty: qty
    };
    if (mode === 'spare_part') {
        payload.SpareItemID = itemId;
        payload.SparePart = name;
    } else {
        payload.QuotationItemID = itemId;
        payload.LineItemName = name;
    }

    $btn.prop('disabled', true).text('Saving...');
    $.post('action/save_sm_quotation_line_item.php', payload, function (res) {
        if (res && res.error === false) {
            var total = res.TotalPrice != null ? res.TotalPrice : (res.TotalAmount != null ? res.TotalAmount : (price * parseFloat(qty)));
            $row.find('.smv-li-total').text(smvFormatMoney(total));
            if (res.qty_changed) {
                $('#smv_has_qty_variance').val('1');
                $('#smvPartialInfo').html('Actual quantity is less than quoted quantity. This ticket is now under <strong>partial verification</strong>.').show();
            }
            if (res.needs_new_ticket) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning(res.message);
                }
            }
            if (typeof toastr !== 'undefined') {
                toastr.success(res.message || 'Item updated.');
            }
            smvLoadLineItemAudit($('#smv_li_quotation_id').val(), $('#smv_li_ticket_pk').val());
            smvReloadTicketTables();
        } else {
            if (typeof toastr !== 'undefined') {
                toastr.error((res && res.message) ? res.message : 'Update failed.');
            } else {
                alert((res && res.message) ? res.message : 'Update failed.');
            }
        }
    }, 'json').fail(function () {
        if (typeof toastr !== 'undefined') {
            toastr.error('Update request failed.');
        } else {
            alert('Update request failed.');
        }
    }).always(function () {
        $btn.prop('disabled', false).text('Save');
    });
}

function smvOpenVerificationModal(ticketPK, ticketID, ticketType, layer) {
    layer = layer || 'bam';
    $('#smv_ticket_pk').val(ticketPK);
    $('#smv_verification_layer').val(layer);
    $('#smv_has_qty_variance').val('0');
    $('#smv_ticket_id_display').text(ticketID || '');
    $('#smv_ticket_type_display').text(ticketType || '');
    $('#smvChecklistForm')[0].reset();
    smvResetPoSection();
    $('#smvChecklistContainer').html('<div class="text-muted">Loading checklist...</div>');
    $('#smvVerifiedInfo').hide().empty();
    $('#smvPartialInfo').hide().empty();
    $('#smvGrnSection').hide();
    $('#smv_is_verified').val('0');
    $('#smvSubmitBtn').prop('disabled', true).show();
    smvShowSubmitError('');

    if (layer === 'state') {
        $('#smVerificationModalLabel').text('State Manager Verification — Quality & GRN');
        $('#smvChecklistHelp').text('Confirm Quality and GRN (Quoted vs Actual). Branch Account Manager verification must be complete first.');
        $('#smvPoSection').hide();
        $('#smvSubmitBtn').text('Verify State');
    } else {
        $('#smVerificationModalLabel').text('Branch Account Manager Verification');
        $('#smvChecklistHelp').text('Confirm WCC, Vendor Payment, Digital Service Report and Line Items. Quantity changes create partial verification.');
        $('#smvPoSection').show();
        $('#smvSubmitBtn').text('Verify BAM');
    }

    $('#smVerificationModal').modal('show');

    $.post('action/get_sm_verification.php', { TicketPK: ticketPK, VerificationLayer: layer }, function (res) {
        if (!res || res.error) {
            $('#smvChecklistContainer').html('<div class="text-danger">' + (res && res.message ? res.message : 'Unable to load checklist.') + '</div>');
            return;
        }

        $('#smv_verification_layer').val(res.layer || layer);
        $('#smv_is_verified').val(res.is_verified ? '1' : '0');
        $('#smvChecklistContainer').html(smvRenderChecklist(res));

        if ((res.layer || layer) === 'bam') {
            smvPopulatePoSection(res);
            $('#smvPoSection').show();
            $('#smvGrnSection').hide();
        } else {
            $('#smvPoSection').hide();
            $('#smvGrnSection').show();
            $('#smvGrnBody').html(smvRenderGrnSection(res.grn));
        }

        if (res.is_partial) {
            $('#smvPartialInfo').html('This ticket is under <strong>partial verification</strong> (Actual Qty is less than Quoted Qty). BAM and State can both verify in this state and re-verify later.').show();
            $('#smv_is_verified').val('0');
        }

        if (res.is_verified && !res.is_partial) {
            var who = (res.layer === 'state') ? 'State Manager' : 'Branch Account Manager';
            var info = 'Verified by ' + (res.verified_by || who);
            if (res.verified_date) {
                info += ' on ' + res.verified_date;
                if (res.verified_time) {
                    info += ' at ' + res.verified_time;
                }
            }
            if (res.customer_po_available && res.customer_po_remarks) {
                info += '<br><strong>Customer PO:</strong> ' + smvEscapeHtml(res.customer_po_remarks);
            }
            $('#smvVerifiedInfo').html(info).show();
            $('#smvSubmitBtn').hide();
        } else if (!res.can_verify) {
            $('#smvChecklistContainer').append('<div class="alert alert-warning mb-0">You are not authorized to verify this ticket, prerequisites are missing, or the ticket status is not Closed.</div>');
            $('#smvSubmitBtn').hide();
        }

        smvTogglePoRemarksField();
        smvRefreshVerifySubmitState();
    }, 'json').fail(function () {
        $('#smvChecklistContainer').html('<div class="text-danger">Unable to load checklist.</div>');
    });
}

function smvSubmitVerification() {
    var ticketPK = $('#smv_ticket_pk').val();
    if (!ticketPK) {
        smvShowSubmitError('Ticket not found. Close and open verification again.');
        return false;
    }

    var layer = $('#smv_verification_layer').val() || 'bam';
    var $lineCheck = $('#smv_LineItemVerified');
    if ($lineCheck.length && !$lineCheck.is(':checked')) {
        smvShowSubmitError('Please review spare parts / line items first, then confirm the checklist.');
        smvOpenLineItemModal(ticketPK);
        return false;
    }

    var missing = smvGetIncompleteChecklistLabels();
    if (missing.length) {
        smvShowSubmitError('Please confirm all checklist items: ' + missing.join(', '));
        return false;
    }

    if (layer === 'bam' && $('#smv_CustomerPoAvailable').is(':checked') && !($('#smv_CustomerPoRemarks').val() || '').trim()) {
        smvShowSubmitError('Please enter the customer PO number / remarks.');
        return false;
    }

    var formData = $('#smvChecklistForm').serializeArray();
    formData.push({ name: 'TicketPK', value: ticketPK });
    formData.push({ name: 'VerificationLayer', value: layer });
    formData.push({ name: 'HasQtyVariance', value: $('#smv_has_qty_variance').val() || '0' });
    formData.push({ name: 'KeepPartial', value: $('#smvPartialInfo').is(':visible') ? '1' : '0' });
    if ($lineCheck.length && $lineCheck.is(':checked')) {
        formData.push({ name: 'LineItemVerified', value: '1' });
    }

    $('#smvSubmitBtn').prop('disabled', true).text('Verifying...');
    smvShowSubmitError('');

    $.post('action/save_sm_verification.php', formData, function (res) {
        if (res && res.error === false) {
            $('#smVerificationModal').modal('hide');
            smvReloadTicketTables();
            if (typeof toastr !== 'undefined') {
                toastr.success(res.message || 'Ticket verified successfully.');
            } else {
                alert(res.message || 'Ticket verified successfully.');
            }
        } else {
            smvShowSubmitError((res && res.message) ? res.message : 'Verification failed.');
            $('#smvSubmitBtn').prop('disabled', false).text(layer === 'state' ? 'Verify State' : 'Verify BAM');
        }
    }, 'json').fail(function () {
        smvShowSubmitError('Verification request failed. Please try again.');
        $('#smvSubmitBtn').prop('disabled', false).text(layer === 'state' ? 'Verify State' : 'Verify BAM');
    });

    return false;
}

$(document).ready(function () {
    $(document).on('click', '.smv-open-verify-btn', function () {
        var ticketPK = $(this).data('ticket-pk');
        var ticketID = $(this).data('ticket-id');
        var ticketType = $(this).data('ticket-type');
        var layer = $(this).data('layer') || 'bam';
        smvOpenVerificationModal(ticketPK, ticketID, ticketType, layer);
    });

    $(document).on('click', '#smvSubmitBtn', function (e) {
        e.preventDefault();
        smvSubmitVerification();
    });

    $(document).on('change', '.smv-check-item', function () {
        var $check = $(this);
        if ($check.hasClass('smv-line-item-check') && $check.is(':checked') && $check.attr('data-reviewed') !== '1') {
            $check.prop('checked', false);
            smvOpenLineItemModal($('#smv_ticket_pk').val());
            if (typeof toastr !== 'undefined') {
                toastr.info('Please review line items first, then confirm.');
            }
            smvRefreshVerifySubmitState();
            return;
        }
        smvRefreshVerifySubmitState();
    });

    $(document).on('click', '.smv-open-line-items-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        smvOpenLineItemModal($('#smv_ticket_pk').val());
    });

    $(document).on('click', '.smv-save-line-item-btn', function () {
        smvSaveLineItemRow($(this));
    });

    $(document).on('click', '#smvRefreshLineItemAuditBtn', function () {
        smvLoadLineItemAudit($('#smv_li_quotation_id').val(), $('#smv_li_ticket_pk').val());
    });

    $(document).on('click', '#smvConfirmLineItemsBtn', function () {
        $('#smv_LineItemVerified').prop('checked', true).attr('data-reviewed', '1');
        $('#smvLineItemModal').modal('hide');
        smvRefreshVerifySubmitState();
        var mode = $('#smv_li_mode').val();
        if (typeof toastr !== 'undefined') {
            toastr.success(mode === 'spare_part' ? 'Spare part verification confirmed.' : 'Line item verification confirmed.');
        }
    });

    $('#smvLineItemModal').on('hidden.bs.modal', function () {
        if ($('#smVerificationModal').hasClass('show')) {
            $('body').addClass('modal-open');
        }
    });

    $(document).on('change', '.smv-po-available', function () {
        smvTogglePoRemarksField();
        smvRefreshVerifySubmitState();
    });

    $(document).on('input', '#smv_CustomerPoRemarks', function () {
        smvRefreshVerifySubmitState();
    });
});
