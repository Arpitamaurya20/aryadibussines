/**
 * State Manager ticket verification UI — isolated from corporate-tickets.js core logic.
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

function smvRefreshVerifySubmitState() {
    var allChecked = true;
    $('#smvChecklistForm .smv-check-item:visible').each(function () {
        if (!$(this).is(':checked')) {
            allChecked = false;
        }
    });

    var poAvailable = $('#smv_CustomerPoAvailable').is(':checked');
    var poRemarks = ($('#smv_CustomerPoRemarks').val() || '').trim();
    var poValid = !poAvailable || poRemarks !== '';

    var isVerified = $('#smv_is_verified').val() === '1';
    $('#smvSubmitBtn').prop('disabled', !allChecked || !poValid || isVerified);
}

function smvOpenVerificationModal(ticketPK, ticketID, ticketType) {
    $('#smv_ticket_pk').val(ticketPK);
    $('#smv_ticket_id_display').text(ticketID || '');
    $('#smv_ticket_type_display').text(ticketType || '');
    $('#smvChecklistForm')[0].reset();
    smvResetPoSection();
    $('#smvChecklistContainer').html('<div class="text-muted">Loading checklist...</div>');
    $('#smvVerifiedInfo').hide().empty();
    $('#smv_is_verified').val('0');
    $('#smvSubmitBtn').prop('disabled', true).show();
    $('#smVerificationModal').modal('show');

    $.post('action/get_sm_verification.php', { TicketPK: ticketPK }, function (res) {
        if (!res || res.error) {
            $('#smvChecklistContainer').html('<div class="text-danger">' + (res && res.message ? res.message : 'Unable to load checklist.') + '</div>');
            return;
        }

        $('#smv_is_verified').val(res.is_verified ? '1' : '0');
        var html = '';

        $.each(res.required_keys, function (idx, key) {
            var label = res.labels[key] || key;
            var checked = res.checks && res.checks[key] ? 'checked' : '';
            var disabled = res.is_verified ? 'disabled' : '';
            html += '<div class="custom-control custom-checkbox mb-3">';
            html += '<input type="checkbox" class="custom-control-input smv-check-item" id="smv_' + key + '" name="' + key + '" value="1" ' + checked + ' ' + disabled + '>';
            html += '<label class="custom-control-label" for="smv_' + key + '">' + label + ' <strong>(Yes)</strong></label>';
            html += '</div>';
        });

        $('#smvChecklistContainer').html(html);
        smvPopulatePoSection(res);

        if (res.is_verified) {
            var info = 'Verified by ' + (res.verified_by || 'State Manager');
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
            $('#smvChecklistContainer').append('<div class="alert alert-warning mb-0">You are not authorized to verify this ticket, or the ticket status is not Closed.</div>');
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
        return false;
    }

    if ($('#smv_CustomerPoAvailable').is(':checked') && !($('#smv_CustomerPoRemarks').val() || '').trim()) {
        if (typeof toastr !== 'undefined') {
            toastr.error('Please enter the customer PO number / remarks.');
        } else {
            alert('Please enter the customer PO number / remarks.');
        }
        return false;
    }

    var formData = $('#smvChecklistForm').serializeArray();
    formData.push({ name: 'TicketPK', value: ticketPK });

    $('#smvSubmitBtn').prop('disabled', true);

    $.post('action/save_sm_verification.php', formData, function (res) {
        if (res && res.error === false) {
            $('#smVerificationModal').modal('hide');
            if ($.fn.DataTable && $('#view-corporate-tickets').length && $.fn.DataTable.isDataTable('#view-corporate-tickets')) {
                $('#view-corporate-tickets').DataTable().ajax.reload(null, false);
            }
            if (typeof toastr !== 'undefined') {
                toastr.success(res.message || 'Ticket verified successfully.');
            } else {
                alert(res.message || 'Ticket verified successfully.');
            }
        } else {
            if (typeof toastr !== 'undefined') {
                toastr.error((res && res.message) ? res.message : 'Verification failed.');
            } else {
                alert((res && res.message) ? res.message : 'Verification failed.');
            }
            smvRefreshVerifySubmitState();
        }
    }, 'json').fail(function () {
        if (typeof toastr !== 'undefined') {
            toastr.error('Verification request failed.');
        } else {
            alert('Verification request failed.');
        }
        smvRefreshVerifySubmitState();
    });

    return false;
}

$(document).ready(function () {
    $(document).on('click', '.smv-open-verify-btn', function () {
        var ticketPK = $(this).data('ticket-pk');
        var ticketID = $(this).data('ticket-id');
        var ticketType = $(this).data('ticket-type');
        smvOpenVerificationModal(ticketPK, ticketID, ticketType);
    });

    $(document).on('change', '.smv-check-item', function () {
        smvRefreshVerifySubmitState();
    });

    $(document).on('change', '.smv-po-available', function () {
        smvTogglePoRemarksField();
        smvRefreshVerifySubmitState();
    });

    $(document).on('input', '#smv_CustomerPoRemarks', function () {
        smvRefreshVerifySubmitState();
    });
});
