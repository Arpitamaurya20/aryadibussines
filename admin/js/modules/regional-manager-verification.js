/**
 * Regional Manager verification — P&L only.
 */

function rmvEscapeHtml(value) {
    if (typeof $ !== 'undefined') {
        return $('<div>').text(value || '').html();
    }
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function rmvRefreshVerifySubmitState() {
    var allChecked = true;
    $('#rmvChecklistForm .rmv-check-item:visible').each(function () {
        if (!$(this).is(':checked')) {
            allChecked = false;
        }
    });
    var isVerified = $('#rmv_is_verified').val() === '1';
    $('#rmvSubmitBtn').prop('disabled', !allChecked || isVerified);
}

function rmvOpenBulkVerificationModal() {
    var selected = [];
    var ticketIds = [];
    $('.rmv-bulk-select:checked').each(function () {
        selected.push($(this).val());
        ticketIds.push($(this).data('ticket-id'));
    });

    if (selected.length === 0) {
        if (typeof toastr !== 'undefined') {
            toastr.warning('Please select at least one closed ticket that is verified by State Manager and not yet verified by Regional Manager.');
        } else {
            alert('Please select at least one closed ticket that is verified by State Manager and not yet verified by Regional Manager.');
        }
        return;
    }

    $('#rmv_ticket_pks').val(selected.join(','));
    $('#rmv_is_bulk').val('1');
    $('#rmv_ticket_id_display').text(selected.length + ' ticket(s) selected');
    $('#rmv_ticket_type_display').text('P&L confirmation');
    $('#rmvBulkTicketList').show().html('<strong>Selected tickets:</strong> ' + rmvEscapeHtml(ticketIds.join(', ')));
    $('#rmvChecklistForm')[0].reset();
    $('#rmvChecklistContainer').empty();
    $('#rmvVerifiedInfo').hide().empty();
    $('#rmv_is_verified').val('0');
    $('#rmv_PnlReviewed').prop('checked', false);
    $('#rmvSubmitBtn').prop('disabled', true).show().text('Verify Selected Tickets');
    $('#rmVerificationModalLabel').text('Regional Manager — Bulk P&L Verification');
    $('#rmVerificationModal').modal('show');
    rmvRefreshVerifySubmitState();
}

function rmvSubmitVerification() {
    var isBulk = $('#rmv_is_bulk').val() === '1';
    var ticketPKsRaw = ($('#rmv_ticket_pks').val() || '').trim();
    if (!ticketPKsRaw) {
        return false;
    }

    if (!$('#rmv_PnlReviewed').is(':checked')) {
        if (typeof toastr !== 'undefined') {
            toastr.error('Please confirm P&L review.');
        } else {
            alert('Please confirm P&L review.');
        }
        return false;
    }

    var formData = [{ name: 'ConfirmPnl', value: '1' }];
    var url = 'action/save_rm_verification.php';
    if (isBulk) {
        url = 'action/save_rm_verification_bulk.php';
        ticketPKsRaw.split(',').forEach(function (ticketPK) {
            if ((ticketPK || '').trim() !== '') {
                formData.push({ name: 'TicketPKs[]', value: ticketPK.trim() });
            }
        });
    } else {
        formData.push({ name: 'TicketPK', value: ticketPKsRaw });
    }

    $('#rmvSubmitBtn').prop('disabled', true);

    $.post(url, formData, function (res) {
        if (res && res.error === false) {
            $('#rmVerificationModal').modal('hide');
            if ($.fn.DataTable && $('#view-corporate-tickets').length && $.fn.DataTable.isDataTable('#view-corporate-tickets')) {
                $('#view-corporate-tickets').DataTable().ajax.reload(null, false);
            }
            rmvUpdateBulkToolbar();
            if (typeof toastr !== 'undefined') {
                toastr.success(res.message || 'Ticket(s) verified successfully.');
            } else {
                alert(res.message || 'Ticket(s) verified successfully.');
            }
        } else {
            if (typeof toastr !== 'undefined') {
                toastr.error((res && res.message) ? res.message : 'Verification failed.');
            } else {
                alert((res && res.message) ? res.message : 'Verification failed.');
            }
            rmvRefreshVerifySubmitState();
        }
    }, 'json').fail(function () {
        if (typeof toastr !== 'undefined') {
            toastr.error('Verification request failed.');
        } else {
            alert('Verification request failed.');
        }
        rmvRefreshVerifySubmitState();
    });

    return false;
}

function rmvUpdateBulkToolbar() {
    var count = $('.rmv-bulk-select:checked').length;
    $('#rmvSelectedCount').text(count);
    $('#rmvBulkVerifyBtn').prop('disabled', count === 0);
}

function rmvFormatPnlMoney(value) {
    var num = parseFloat(value);
    if (isNaN(num)) {
        num = 0;
    }
    return num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function rmvPnlStatusClass(status) {
    var normalized = String(status || '').toLowerCase();
    if (normalized === 'profit') {
        return 'rmv-pnl-status-pill--profit';
    }
    if (normalized === 'loss') {
        return 'rmv-pnl-status-pill--loss';
    }
    return 'rmv-pnl-status-pill--neutral';
}

function rmvRenderPnlBody(res) {
    var summary = res.summary || {};
    var bars = res.bars || [];
    var html = '';

    html += '<div class="rmv-pnl-kpi-grid">';
    html += '<div class="rmv-pnl-kpi rmv-pnl-kpi--sell"><h6>Sell (C_Total)</h6><h3>₹ ' + rmvFormatPnlMoney(summary.c_total) + '</h3></div>';
    html += '<div class="rmv-pnl-kpi rmv-pnl-kpi--budget"><h6>Expected Budget</h6><h3>₹ ' + rmvFormatPnlMoney(summary.expected_budget) + '</h3></div>';
    html += '<div class="rmv-pnl-kpi rmv-pnl-kpi--payment"><h6>Payments Done</h6><h3>₹ ' + rmvFormatPnlMoney(summary.payment_paid) + '</h3></div>';
    html += '<div class="rmv-pnl-kpi rmv-pnl-kpi--convenience"><h6>Convenience Paid</h6><h3>₹ ' + rmvFormatPnlMoney(summary.convenience_paid) + '</h3></div>';
    html += '<div class="rmv-pnl-kpi rmv-pnl-kpi--projected"><h6>Projected P&amp;L</h6><h3>₹ ' + rmvFormatPnlMoney(summary.projected_pnl) + '</h3></div>';
    html += '<div class="rmv-pnl-kpi rmv-pnl-kpi--actual"><h6>Actual P&amp;L</h6><h3>₹ ' + rmvFormatPnlMoney(summary.actual_pnl) + '</h3></div>';
    html += '</div>';

    html += '<div class="mb-3">';
    html += '<span class="rmv-pnl-status-pill ' + rmvPnlStatusClass(summary.projected_status) + '">Projected: ' + rmvEscapeHtml(summary.projected_status || '-') + '</span> ';
    html += '<span class="rmv-pnl-status-pill ' + rmvPnlStatusClass(summary.actual_status) + '">Actual: ' + rmvEscapeHtml(summary.actual_status || '-') + '</span>';
    html += '</div>';

    html += '<div class="table-responsive mb-3"><table class="table table-sm table-bordered mb-0">';
    html += '<tbody>';
    html += '<tr><th>Sell Value (C_TotalPrice)</th><td>₹ ' + rmvFormatPnlMoney(summary.c_total) + '</td></tr>';
    html += '<tr><th>Internal Base (T_TotalPrice)</th><td>₹ ' + rmvFormatPnlMoney(summary.t_total) + '</td></tr>';
    html += '<tr><th>Expected Budget</th><td>₹ ' + rmvFormatPnlMoney(summary.expected_budget) + '</td></tr>';
    html += '<tr><th>Sell vs Expected Budget</th><td>₹ ' + rmvFormatPnlMoney(summary.sell_vs_budget) + '</td></tr>';
    html += '<tr><th>Payment Requests (Gross)</th><td>₹ ' + rmvFormatPnlMoney(summary.payment_requested_gross) + '</td></tr>';
    html += '<tr><th>Payment Requests (P&amp;L Final)</th><td>₹ ' + rmvFormatPnlMoney(summary.payment_requested_final) + '</td></tr>';
    html += '<tr><th>Payments Done</th><td>₹ ' + rmvFormatPnlMoney(summary.payment_paid) + '</td></tr>';
    html += '<tr><th>Convenience Requests</th><td>₹ ' + rmvFormatPnlMoney(summary.convenience_requested) + '</td></tr>';
    html += '<tr><th>Convenience Paid</th><td>₹ ' + rmvFormatPnlMoney(summary.convenience_paid) + '</td></tr>';
    html += '<tr><th>Convenience Pending</th><td>₹ ' + rmvFormatPnlMoney(summary.convenience_pending) + '</td></tr>';
    html += '<tr><th>Projected Total Cost</th><td>₹ ' + rmvFormatPnlMoney(summary.projected_cost) + '</td></tr>';
    html += '<tr><th>Actual Paid Cost</th><td>₹ ' + rmvFormatPnlMoney(summary.actual_cost) + '</td></tr>';
    html += '<tr><th>Budget Remaining (vs requests)</th><td>₹ ' + rmvFormatPnlMoney(summary.budget_remaining) + '</td></tr>';
    html += '</tbody></table></div>';

    html += '<h6 class="mb-2">Comparison Graph</h6>';
    $.each(bars, function (idx, bar) {
        var width = Math.max(0, Math.min(100, parseFloat(bar.percent) || 0));
        html += '<div class="rmv-pnl-bar-row">';
        html += '<div class="rmv-pnl-bar-label"><span>' + rmvEscapeHtml(bar.label || '') + '</span><strong>₹ ' + rmvFormatPnlMoney(bar.value) + '</strong></div>';
        html += '<div class="rmv-pnl-bar-track"><div class="rmv-pnl-bar-fill" style="width:' + width + '%;background:' + rmvEscapeHtml(bar.color || '#334155') + ';"></div></div>';
        html += '</div>';
    });

    return html;
}

function rmvOpenPnlModal(ticketPK, ticketID, canConfirm) {
    ticketPK = parseInt(ticketPK, 10) || 0;
    if (ticketPK <= 0) {
        return;
    }

    $('#rmv_pnl_ticket_id').text(ticketID || '');
    $('#rmv_pnl_ticket_pk').val(ticketPK);
    $('#rmv_pnl_can_confirm').val(canConfirm ? '1' : '0');
    $('#rmv_pnl_company_branch').text('');
    $('#rmv_pnl_type_status').text('');
    $('#rmvPnlBody').html('<div class="text-muted">Loading P&amp;L comparison...</div>');
    if (canConfirm) {
        $('#rmvConfirmPnlBtn').show().prop('disabled', false).text('Confirm P&L & Verify');
    } else {
        $('#rmvConfirmPnlBtn').hide();
    }
    $('#rmvPnlModal').modal('show');

    $.post('action/get_rm_ticket_pnl.php', { TicketPK: ticketPK }, function (res) {
        if (!res || res.error) {
            $('#rmvPnlBody').html('<div class="alert alert-danger mb-0">' + rmvEscapeHtml((res && res.message) ? res.message : 'Unable to load P&L.') + '</div>');
            $('#rmvConfirmPnlBtn').hide();
            return;
        }

        var ticket = res.ticket || {};
        $('#rmv_pnl_ticket_id').text(ticket.ticket_id || ticketID || '');
        $('#rmv_pnl_company_branch').text((ticket.company || '-') + ' / ' + (ticket.branch || '-') + (ticket.city ? (' (' + ticket.city + ')') : ''));
        $('#rmv_pnl_type_status').text((ticket.type || '-') + ' / ' + (ticket.status || '-'));
        $('#rmvPnlBody').html(rmvRenderPnlBody(res));
    }, 'json').fail(function () {
        $('#rmvPnlBody').html('<div class="alert alert-danger mb-0">Unable to load P&amp;L comparison.</div>');
        $('#rmvConfirmPnlBtn').hide();
    });
}

function rmvConfirmPnlVerification() {
    var ticketPK = $('#rmv_pnl_ticket_pk').val();
    if (!ticketPK) {
        return;
    }
    $('#rmvConfirmPnlBtn').prop('disabled', true).text('Verifying...');
    $.post('action/save_rm_verification.php', {
        TicketPK: ticketPK,
        ConfirmPnl: 1
    }, function (res) {
        if (res && res.error === false) {
            $('#rmvPnlModal').modal('hide');
            if ($.fn.DataTable && $('#view-corporate-tickets').length && $.fn.DataTable.isDataTable('#view-corporate-tickets')) {
                $('#view-corporate-tickets').DataTable().ajax.reload(null, false);
            }
            if (typeof toastr !== 'undefined') {
                toastr.success(res.message || 'P&L verified.');
            } else {
                alert(res.message || 'P&L verified.');
            }
        } else {
            if (typeof toastr !== 'undefined') {
                toastr.error((res && res.message) ? res.message : 'Verification failed.');
            } else {
                alert((res && res.message) ? res.message : 'Verification failed.');
            }
            $('#rmvConfirmPnlBtn').prop('disabled', false).text('Confirm P&L & Verify');
        }
    }, 'json').fail(function () {
        if (typeof toastr !== 'undefined') {
            toastr.error('Verification request failed.');
        } else {
            alert('Verification request failed.');
        }
        $('#rmvConfirmPnlBtn').prop('disabled', false).text('Confirm P&L & Verify');
    });
}

$(document).ready(function () {
    $(document).on('click', '.rmv-open-pnl-btn', function () {
        var canConfirm = String($(this).data('can-confirm') || '') === '1';
        rmvOpenPnlModal($(this).data('ticket-pk'), $(this).data('ticket-id'), canConfirm);
    });

    $(document).on('click', '#rmvConfirmPnlBtn', function () {
        rmvConfirmPnlVerification();
    });

    $(document).on('change', '.rmv-check-item', function () {
        rmvRefreshVerifySubmitState();
    });

    $(document).on('change', '.rmv-bulk-select', function () {
        rmvUpdateBulkToolbar();
    });

    $(document).on('change', '#rmvSelectAll', function () {
        var isChecked = $(this).is(':checked');
        $('.rmv-bulk-select').prop('checked', isChecked);
        rmvUpdateBulkToolbar();
    });

    $(document).on('click', '#rmvBulkVerifyBtn', function () {
        rmvOpenBulkVerificationModal();
    });

    $(document).on('draw.dt', '#view-corporate-tickets', function () {
        $('#rmvSelectAll').prop('checked', false);
        rmvUpdateBulkToolbar();
    });
});
