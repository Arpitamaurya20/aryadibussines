/**
 * State Manager PPM ticket verification UI — isolated from ppm-tickets-all.js core logic.
 */

function psmvShowAlert(message, isError) {
    if (typeof alertify !== 'undefined') {
        if (isError && alertify.error) {
            alertify.error(message);
        } else if (alertify.success) {
            alertify.success(message);
        } else {
            alertify.message(message);
        }
        return;
    }
    if (typeof TechXAlert === 'function') {
        TechXAlert(message);
        return;
    }
    window.alert(message);
}

function psmvEscapeHtml(value) {
    if (typeof $ !== 'undefined') {
        return $('<div>').text(value || '').html();
    }
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function psmvResetPoSection() {
    $('#psmv_CustomerPoAvailable').prop('checked', false).prop('disabled', false);
    $('#psmv_CustomerPoRemarks').val('').prop('disabled', false);
    $('#psmvPoRemarksWrap').hide();
    $('#psmvPoSection').show();
}

function psmvPopulatePoSection(res) {
    var isVerified = !!(res && res.is_verified);
    var poAvailable = !!(res && res.customer_po_available);
    var poRemarks = (res && res.customer_po_remarks) ? res.customer_po_remarks : '';

    $('#psmv_CustomerPoAvailable').prop('checked', poAvailable);
    $('#psmv_CustomerPoRemarks').val(poRemarks);

    if (isVerified) {
        $('#psmv_CustomerPoAvailable').prop('disabled', true);
        $('#psmv_CustomerPoRemarks').prop('disabled', true);
    }

    if (poAvailable) {
        $('#psmvPoRemarksWrap').show();
    } else {
        $('#psmvPoRemarksWrap').hide();
    }
}

function psmvTogglePoRemarksField() {
    var isChecked = $('#psmv_CustomerPoAvailable').is(':checked');
    var isVerified = $('#psmv_is_verified').val() === '1';
    if (isChecked) {
        $('#psmvPoRemarksWrap').show();
        if (!isVerified) {
            $('#psmv_CustomerPoRemarks').prop('disabled', false);
        }
    } else {
        $('#psmvPoRemarksWrap').hide();
        if (!isVerified) {
            $('#psmv_CustomerPoRemarks').val('').prop('disabled', false);
        }
    }
}

function psmvRefreshVerifySubmitState() {
    var allChecked = true;
    $('#psmvChecklistForm .psmv-check-item:visible').each(function () {
        if (!$(this).is(':checked')) {
            allChecked = false;
        }
    });

    var poAvailable = $('#psmv_CustomerPoAvailable').is(':checked');
    var poRemarks = ($('#psmv_CustomerPoRemarks').val() || '').trim();
    var poValid = !poAvailable || poRemarks !== '';

    var isVerified = $('#psmv_is_verified').val() === '1';
    $('#psmvSubmitBtn').prop('disabled', !allChecked || !poValid || isVerified);
}

function psmvOpenVerificationModal(ticketPK, ticketID) {
    $('#psmv_ticket_pk').val(ticketPK);
    $('#psmv_ticket_id_display').text(ticketID || '');
    $('#psmvChecklistForm')[0].reset();
    psmvResetPoSection();
    $('#psmvChecklistContainer').html('<div class="text-muted">Loading checklist...</div>');
    $('#psmvVerifiedInfo').hide().empty();
    $('#psmv_is_verified').val('0');
    $('#psmvSubmitBtn').prop('disabled', true).show();
    $('#psmvVerificationModal').modal('show');

    $.post('action/get_sm_verification.php', { TicketPK: ticketPK }, function (res) {
        if (!res || res.error) {
            $('#psmvChecklistContainer').html('<div class="text-danger">' + (res && res.message ? res.message : 'Unable to load checklist.') + '</div>');
            return;
        }

        $('#psmv_is_verified').val(res.is_verified ? '1' : '0');
        var html = '';

        $.each(res.required_keys, function (idx, key) {
            var label = res.labels[key] || key;
            var checked = res.checks && res.checks[key] ? 'checked' : '';
            var disabled = res.is_verified ? 'disabled' : '';
            html += '<div class="custom-control custom-checkbox mb-3">';
            html += '<input type="checkbox" class="custom-control-input psmv-check-item" id="psmv_' + key + '" name="' + key + '" value="1" ' + checked + ' ' + disabled + '>';
            html += '<label class="custom-control-label" for="psmv_' + key + '">' + label + ' <strong>(Yes)</strong></label>';
            html += '</div>';
        });

        $('#psmvChecklistContainer').html(html);
        psmvPopulatePoSection(res);

        if (res.is_verified) {
            var info = 'Verified by ' + (res.verified_by || 'State Manager');
            if (res.verified_date) {
                info += ' on ' + res.verified_date;
                if (res.verified_time) {
                    info += ' at ' + res.verified_time;
                }
            }
            if (res.customer_po_available && res.customer_po_remarks) {
                info += '<br><strong>PO Number:</strong> ' + psmvEscapeHtml(res.customer_po_remarks);
            }
            $('#psmvVerifiedInfo').html(info).show();
            $('#psmvSubmitBtn').hide();
        } else if (!res.can_verify) {
            $('#psmvChecklistContainer').append('<div class="alert alert-warning mb-0">You are not authorized to verify this ticket, or the ticket status is not Closed.</div>');
            $('#psmvSubmitBtn').hide();
        }

        psmvTogglePoRemarksField();
        psmvRefreshVerifySubmitState();
    }, 'json').fail(function () {
        $('#psmvChecklistContainer').html('<div class="text-danger">Unable to load checklist.</div>');
    });
}

function psmvSubmitVerification() {
    var ticketPK = $('#psmv_ticket_pk').val();
    if (!ticketPK) {
        return false;
    }

    if ($('#psmv_CustomerPoAvailable').is(':checked') && !($('#psmv_CustomerPoRemarks').val() || '').trim()) {
        psmvShowAlert('Please enter the PO number / remarks.', true);
        return false;
    }

    var formData = $('#psmvChecklistForm').serializeArray();
    formData.push({ name: 'TicketPK', value: ticketPK });

    $('#psmvSubmitBtn').prop('disabled', true);

    $.post('action/save_sm_verification.php', formData, function (res) {
        if (res && res.error === false) {
            $('#psmvVerificationModal').modal('hide');
            if ($.fn.DataTable && $('#view-all-ppm-tickets').length && $.fn.DataTable.isDataTable('#view-all-ppm-tickets')) {
                $('#view-all-ppm-tickets').DataTable().ajax.reload(null, false);
            }
            psmvShowAlert(res.message || 'PPM ticket verified successfully.', false);
        } else {
            psmvShowAlert((res && res.message) ? res.message : 'Verification failed.', true);
            psmvRefreshVerifySubmitState();
        }
    }, 'json').fail(function () {
        psmvShowAlert('Verification request failed.', true);
        psmvRefreshVerifySubmitState();
    });

    return false;
}

$(document).ready(function () {
    $(document).on('click', '.psmv-open-verify-btn', function () {
        var ticketPK = $(this).data('ticket-pk');
        var ticketID = $(this).data('ticket-id');
        psmvOpenVerificationModal(ticketPK, ticketID);
    });

    $(document).on('change', '.psmv-check-item', function () {
        psmvRefreshVerifySubmitState();
    });

    $(document).on('change', '.psmv-po-available', function () {
        psmvTogglePoRemarksField();
        psmvRefreshVerifySubmitState();
    });

    $(document).on('input', '#psmv_CustomerPoRemarks', function () {
        psmvRefreshVerifySubmitState();
    });
});
