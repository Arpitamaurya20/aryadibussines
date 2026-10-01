var convenienceFinanceTable = null;
var convenienceFinanceFilterSnapshot = null;

function parseConvenienceFinanceActionResponse(data) {
    if (typeof data === 'object' && data !== null) {
        return data;
    }
    try {
        return JSON.parse(data);
    } catch (e) {
        return { error: true, message: 'Invalid server response.' };
    }
}

var CONVENIENCE_FINANCE_BULK_CHUNK_SIZE = 10;

function hideConvenienceFinanceBulkProgress() {
    var overlay = document.getElementById('convenience-finance-bulk-progress-overlay');
    if (overlay) {
        overlay.remove();
    }
}

function showConvenienceFinanceBulkProgress(title, processed, total, statusText) {
    var safeTotal = Math.max(1, parseInt(total, 10) || 1);
    var safeProcessed = Math.max(0, Math.min(safeTotal, parseInt(processed, 10) || 0));
    var pct = Math.round((safeProcessed / safeTotal) * 100);
    var overlay = document.getElementById('convenience-finance-bulk-progress-overlay');

    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'convenience-finance-bulk-progress-overlay';
        overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML =
            '<div style="width:min(420px,92vw);background:#fff;border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,.25);padding:22px;font-family:Poppins,Segoe UI,sans-serif;">' +
            '  <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">' +
            '    <div style="width:38px;height:38px;border-radius:50%;border:3px solid #dbeafe;border-top-color:#027dc1;animation:convFinBulkSpin .9s linear infinite;"></div>' +
            '    <div>' +
            '      <div id="convenience-finance-bulk-progress-title" style="font-size:15px;font-weight:700;color:#0f172a;"></div>' +
            '      <div id="convenience-finance-bulk-progress-sub" style="font-size:12px;color:#64748b;margin-top:2px;"></div>' +
            '    </div>' +
            '  </div>' +
            '  <div style="height:10px;background:#e2e8f0;border-radius:999px;overflow:hidden;">' +
            '    <div id="convenience-finance-bulk-progress-bar" style="height:100%;width:0%;background:linear-gradient(90deg,#027dc1,#0ea5e9);transition:width .25s ease;"></div>' +
            '  </div>' +
            '  <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:11px;color:#64748b;">' +
            '    <span id="convenience-finance-bulk-progress-label">Starting…</span>' +
            '    <span id="convenience-finance-bulk-progress-pct">0%</span>' +
            '  </div>' +
            '</div>' +
            '<style>@keyframes convFinBulkSpin{to{transform:rotate(360deg)}}</style>';
        document.body.appendChild(overlay);
    }

    document.getElementById('convenience-finance-bulk-progress-title').textContent = title || 'Processing…';
    document.getElementById('convenience-finance-bulk-progress-sub').textContent =
        (statusText || ('Processed ' + safeProcessed + ' of ' + safeTotal + ' record(s)'));
    document.getElementById('convenience-finance-bulk-progress-bar').style.width = pct + '%';
    document.getElementById('convenience-finance-bulk-progress-label').textContent = safeProcessed + ' / ' + safeTotal;
    document.getElementById('convenience-finance-bulk-progress-pct').textContent = pct + '%';
}

function runConvenienceFinanceBulkInChunks(options) {
    var ids = (options.ids || []).slice();
    var url = options.url;
    var title = options.title || 'Processing selected records';
    var buildPayload = options.buildPayload || function (chunk) { return { IDs: chunk }; };
    var parseResponse = options.parseResponse || parseConvenienceFinanceActionResponse;
    var onDone = options.onDone || function () {};
    var total = ids.length;
    var processed = 0;
    var success = 0;
    var failed = 0;

    if (total === 0) {
        onDone({ success: 0, failed: 0, total: 0 });
        return;
    }

    showConvenienceFinanceBulkProgress(title, 0, total, 'Preparing…');

    function nextChunk() {
        if (ids.length === 0) {
            showConvenienceFinanceBulkProgress(title, total, total, 'Finishing…');
            setTimeout(function () {
                hideConvenienceFinanceBulkProgress();
                onDone({ success: success, failed: failed, total: total });
            }, 250);
            return;
        }

        var chunk = ids.splice(0, CONVENIENCE_FINANCE_BULK_CHUNK_SIZE);
        showConvenienceFinanceBulkProgress(title, processed, total);

        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            data: buildPayload(chunk),
            timeout: 120000
        }).done(function (data) {
            var response = parseResponse(data);
            var chunkSuccess = parseInt((response.summary && response.summary.success) || 0, 10);
            var chunkFailed = parseInt((response.summary && response.summary.failed) || 0, 10);
            if (!chunkSuccess && !chunkFailed) {
                if (!response.error) {
                    chunkSuccess = chunk.length;
                } else {
                    chunkFailed = chunk.length;
                }
            }
            success += chunkSuccess;
            failed += chunkFailed;
            processed += chunk.length;
            showConvenienceFinanceBulkProgress(title, processed, total);
            setTimeout(nextChunk, 40);
        }).fail(function () {
            failed += chunk.length;
            processed += chunk.length;
            showConvenienceFinanceBulkProgress(title, processed, total, 'Network issue on a batch — continuing…');
            setTimeout(nextChunk, 80);
        });
    }

    nextChunk();
}

function initConvenienceFinanceMultiSelectFilters() {
    $('.convenience-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            return;
        }
        $el.select2({
            placeholder: $el.data('placeholder') || 'All',
            allowClear: true,
            width: '100%',
            dropdownParent: $(document.body)
        });
    });
}

function syncConvenienceFinanceFilterSelect2Ui() {
    $('.convenience-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.trigger('change.select2');
        }
    });
}

function captureConvenienceFinanceFilters() {
    return {
        employee: $('#employee_filter').val() || [],
        status: $('#status_filter').val() || [],
        state: $('#state_filter').val() || [],
        department: $('#department_filter').val() || [],
        designation: $('#designation_filter').val() || [],
        filter_date: document.getElementById('filter_date').value,
        employee_number: document.getElementById('employee_number_filter').value,
        ticket: document.getElementById('ticket_filter').value
    };
}

function restoreConvenienceFinanceFilters(filterState) {
    if (!filterState) {
        return;
    }
    $('#employee_filter').val(filterState.employee.length ? filterState.employee : null).trigger('change');
    $('#status_filter').val(filterState.status.length ? filterState.status : null).trigger('change');
    $('#state_filter').val(filterState.state.length ? filterState.state : null).trigger('change');
    $('#department_filter').val(filterState.department.length ? filterState.department : null).trigger('change');
    $('#designation_filter').val(filterState.designation.length ? filterState.designation : null).trigger('change');
    document.getElementById('filter_date').value = filterState.filter_date || '';
    document.getElementById('employee_number_filter').value = filterState.employee_number || '';
    document.getElementById('ticket_filter').value = filterState.ticket || '';
    syncConvenienceFinanceFilterSelect2Ui();
}

function encodeConvenienceFinanceMultiFilterValue(values) {
    if (!values || values.length === 0) {
        return '-1';
    }
    return Array.isArray(values) ? values.join(',') : values;
}

function closeConvenienceFinanceFilterSelect2() {
    $('.convenience-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.select2('close');
        }
    });
}

function bindConvenienceFinanceModalUi() {
    $('#convenience_finance_modal')
        .on('show.bs.modal', function () {
            closeConvenienceFinanceFilterSelect2();
        })
        .on('hidden.bs.modal', function () {
            syncConvenienceFinanceFilterSelect2Ui();
        });
}

function getSelectedConvenienceFinanceIds() {
    var selected = [];
    $('.convenience-finance-select:checked').each(function () {
        selected.push($(this).val());
    });
    return selected;
}

function updateConvenienceFinanceSelectionUi() {
    var selected = getSelectedConvenienceFinanceIds();
    var total = $('.convenience-finance-select').length;
    var count = selected.length;
    $('#convenience_finance_selected_count').text(count + ' selected');
    $('#btn_finance_bulk_paid').prop('disabled', count === 0);
    $('#btn_finance_bulk_reject').prop('disabled', count === 0);

    var $selectAll = $('#convenience_finance_select_all');
    $selectAll.prop('checked', total > 0 && count === total);
    $selectAll.prop('indeterminate', count > 0 && count < total);
}

function bindConvenienceFinanceSelectionEvents() {
    $(document).off('change.convenienceFinanceSelect', '.convenience-finance-select');
    $(document).on('change.convenienceFinanceSelect', '.convenience-finance-select', updateConvenienceFinanceSelectionUi);

    $('#convenience_finance_select_all').off('change.convenienceFinanceSelectAll').on('change.convenienceFinanceSelectAll', function () {
        var checked = $(this).is(':checked');
        $('.convenience-finance-select').prop('checked', checked);
        updateConvenienceFinanceSelectionUi();
    });
}

function resetConvenienceFinanceSelection() {
    $('.convenience-finance-select').prop('checked', false);
    $('#convenience_finance_select_all').prop('checked', false).prop('indeterminate', false);
    updateConvenienceFinanceSelectionUi();
}

function initConvenienceFinancePaymentPage(initialParam) {
    $("#_Nav_Convenience_Finance_Payment").addClass("active open");
    $('#filter_date').daterangepicker({ locale: { format: 'YYYY-MM-DD' } });
    initConvenienceFinanceMultiSelectFilters();
    bindConvenienceFinanceSelectionEvents();
    bindConvenienceFinanceModalUi();
    initConvenienceFinancePaymentTable(initialParam);
    refreshConvenienceFinanceDashboard();
}

function buildConvenienceFinanceParams(filterState) {
    var state = filterState || captureConvenienceFinanceFilters();
    var ticket = state.ticket || -1;
    return '?filter_date=' + encodeURIComponent(state.filter_date)
        + '&EmployeeID=' + encodeURIComponent(encodeConvenienceFinanceMultiFilterValue(state.employee))
        + '&status=' + encodeURIComponent(encodeConvenienceFinanceMultiFilterValue(state.status))
        + '&state=' + encodeURIComponent(encodeConvenienceFinanceMultiFilterValue(state.state))
        + '&department=' + encodeURIComponent(encodeConvenienceFinanceMultiFilterValue(state.department))
        + '&designation=' + encodeURIComponent(encodeConvenienceFinanceMultiFilterValue(state.designation))
        + '&ticket_id=' + encodeURIComponent(ticket)
        + '&employee_number=' + encodeURIComponent(state.employee_number || '');
}

function refreshConvenienceFinanceDashboard(filterState) {
    loadConvenienceDashboard('finance', buildConvenienceFinanceParams(filterState || captureConvenienceFinanceFilters()));
}

function reloadConvenienceFinancePaymentTable(filterState) {
    var params = buildConvenienceFinanceParams(filterState);
    refreshConvenienceFinanceDashboard(filterState);
    var selector = '#view-convenience-finance-payment';
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.url('ajax/view-convenience-finance-payment-post.php' + params).load(function () {
            restoreConvenienceFinanceFilters(filterState);
            resetConvenienceFinanceSelection();
        }, false);
        return;
    }
    initConvenienceFinancePaymentTable(params);
}

function initConvenienceFinancePaymentTable(param) {
    var selector = '#view-convenience-finance-payment';
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.url('ajax/view-convenience-finance-payment-post.php' + param).load(function () {
            resetConvenienceFinanceSelection();
        }, false);
        return;
    }
    convenienceFinanceTable = $(selector).DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: { url: 'ajax/view-convenience-finance-payment-post.php' + param },
        columns: [
            { data: 'Select', orderable: false, searchable: false },
            { data: null, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: 'Employee' },
            { data: 'Ticket' },
            { data: 'Journey' },
            { data: 'Amount' },
            { data: 'RecordDate' },
            { data: 'State' },
            { data: 'Department', responsivePriority: 1 },
            { data: 'PaymentStatus' },
            { data: 'ApprovalInfo' },
            { data: 'Action' }
        ],
        drawCallback: function () {
            resetConvenienceFinanceSelection();
        }
    });
}

function RefreshConvenienceFinancePayment() {
    reloadConvenienceFinancePaymentTable(captureConvenienceFinanceFilters());
}

function setConvenienceFinanceQuickFilter(type) {
    if (type === 'pending_payment') {
        $('#status_filter').val(['finance_actionable']).trigger('change');
    }
    if (type === 'payment_done') {
        $('#status_filter').val(['6']).trigger('change');
    }
    if (type === '365days') {
        var endDate = moment().format('YYYY-MM-DD');
        var startDate = moment().subtract(365, 'days').format('YYYY-MM-DD');
        document.getElementById('filter_date').value = startDate + ' - ' + endDate;
    }
    if (type === 'all') {
        document.getElementById('filter_date').value = '2000-01-01 - ' + moment().format('YYYY-MM-DD');
        $('#status_filter').val(null).trigger('change');
    }
    RefreshConvenienceFinancePayment();
}

function resetConvenienceFinancePaymentForm() {
    document.getElementById('convenience_finance_payment_reference').value = '';
    document.getElementById('convenience_finance_payment_mode').value = '';
    document.getElementById('convenience_finance_payment_remarks').value = '';
}

function setConvenienceFinanceModalMode(mode, actionType) {
    document.getElementById('convenience_finance_action_mode').value = mode;
    document.getElementById('convenience_finance_action_type').value = actionType || 'paid';
    var isBulk = mode === 'bulk';
    var isReject = actionType === 'reject';
    $('#convenience_finance_bulk_info').toggleClass('d-none', !isBulk);
    $('#convenience_finance_paid_fields').toggle(!isReject);
    $('#btn_convenience_finance_modal_paid').toggle(!isReject && (!isBulk || actionType === 'paid'));
    $('#btn_convenience_finance_modal_reject').toggle(isReject || (!isBulk && actionType !== 'paid'));
    if (isBulk) {
        $('#btn_convenience_finance_modal_paid').toggle(actionType === 'paid');
        $('#btn_convenience_finance_modal_reject').toggle(actionType === 'reject');
    }
    $('#convenience_finance_remarks_label').text(isReject ? 'Rejection Reason' : 'Remarks');
    document.getElementById('convenience_finance_payment_remarks').placeholder = isReject ? 'Reason for rejection' : 'Optional remarks';
    $('.modal-title', '#convenience_finance_modal').text(
        isBulk
            ? 'Finance Bulk ' + (actionType === 'reject' ? 'Rejection' : 'Payment')
            : (isReject ? 'Reject Payment' : 'Mark Payment Done')
    );
}

function openConvenienceFinanceMarkPaid(id) {
    convenienceFinanceFilterSnapshot = captureConvenienceFinanceFilters();
    setConvenienceFinanceModalMode('single', 'paid');
    document.getElementById('convenience_finance_action_id').value = id;
    resetConvenienceFinancePaymentForm();
    $('#convenience_finance_modal').modal('show');
}

function openConvenienceFinanceReject(id) {
    convenienceFinanceFilterSnapshot = captureConvenienceFinanceFilters();
    setConvenienceFinanceModalMode('single', 'reject');
    document.getElementById('convenience_finance_action_id').value = id;
    resetConvenienceFinancePaymentForm();
    $('#convenience_finance_modal').modal('show');
}

function openConvenienceFinanceBulkModal(actionType) {
    var selected = getSelectedConvenienceFinanceIds();
    if (selected.length === 0) {
        TechXAlert('Please select at least one pending payment record.');
        return;
    }
    convenienceFinanceFilterSnapshot = captureConvenienceFinanceFilters();
    setConvenienceFinanceModalMode('bulk', actionType || 'paid');
    document.getElementById('convenience_finance_action_id').value = '';
    document.getElementById('convenience_finance_bulk_info').textContent = selected.length + ' record(s) selected.';
    resetConvenienceFinancePaymentForm();
    $('#convenience_finance_modal').modal('show');
}

function submitConvenienceFinanceMarkPaid() {
    var filterState = convenienceFinanceFilterSnapshot || captureConvenienceFinanceFilters();
    var mode = document.getElementById('convenience_finance_action_mode').value;
    var payloadBase = {
        payment_reference: document.getElementById('convenience_finance_payment_reference').value,
        payment_mode: document.getElementById('convenience_finance_payment_mode').value,
        payment_remarks: document.getElementById('convenience_finance_payment_remarks').value
    };

    if (mode !== 'bulk') {
        $.post('action/finance_mark_paid_convenience.php', {
            payment_reference: payloadBase.payment_reference,
            payment_mode: payloadBase.payment_mode,
            payment_remarks: payloadBase.payment_remarks,
            ID: document.getElementById('convenience_finance_action_id').value
        }, function (data) {
            var response = parseConvenienceFinanceActionResponse(data);
            if (!response.error) {
                $('#convenience_finance_modal').modal('hide');
                reloadConvenienceFinancePaymentTable(filterState);
                convenienceFinanceFilterSnapshot = null;
            }
            TechXAlert(response.message);
        }, 'json').fail(function (xhr) {
            TechXAlert('Unable to mark payment. ' + (xhr.responseText || 'Server error'));
        });
        return;
    }

    var selected = getSelectedConvenienceFinanceIds();
    if (!selected.length) {
        TechXAlert('Please select at least one pending payment record.');
        return;
    }

    $('#convenience_finance_modal').modal('hide');
    $('#btn_finance_bulk_paid, #btn_finance_bulk_reject').prop('disabled', true);

    runConvenienceFinanceBulkInChunks({
        ids: selected,
        url: 'action/finance_bulk_mark_paid_convenience.php',
        title: 'Marking payments done',
        buildPayload: function (chunk) {
            return {
                IDs: chunk,
                payment_reference: payloadBase.payment_reference,
                payment_mode: payloadBase.payment_mode,
                payment_remarks: payloadBase.payment_remarks
            };
        },
        onDone: function (result) {
            reloadConvenienceFinancePaymentTable(filterState);
            convenienceFinanceFilterSnapshot = null;
            TechXAlert(result.success + ' record(s) marked as paid. ' + result.failed + ' record(s) could not be updated.');
        }
    });
}

function submitConvenienceFinanceReject() {
    var filterState = convenienceFinanceFilterSnapshot || captureConvenienceFinanceFilters();
    var mode = document.getElementById('convenience_finance_action_mode').value;
    var reason = document.getElementById('convenience_finance_payment_remarks').value;

    if (mode !== 'bulk') {
        $.post('action/finance_reject_convenience.php', {
            reason: reason,
            ID: document.getElementById('convenience_finance_action_id').value
        }, function (data) {
            var response = parseConvenienceFinanceActionResponse(data);
            if (!response.error) {
                $('#convenience_finance_modal').modal('hide');
                reloadConvenienceFinancePaymentTable(filterState);
                convenienceFinanceFilterSnapshot = null;
            }
            TechXAlert(response.message);
        }, 'json').fail(function (xhr) {
            TechXAlert('Unable to reject payment. ' + (xhr.responseText || 'Server error'));
        });
        return;
    }

    var selected = getSelectedConvenienceFinanceIds();
    if (!selected.length) {
        TechXAlert('Please select at least one pending payment record.');
        return;
    }

    $('#convenience_finance_modal').modal('hide');
    $('#btn_finance_bulk_paid, #btn_finance_bulk_reject').prop('disabled', true);

    runConvenienceFinanceBulkInChunks({
        ids: selected,
        url: 'action/finance_bulk_reject_convenience.php',
        title: 'Rejecting payments',
        buildPayload: function (chunk) {
            return { IDs: chunk, reason: reason };
        },
        onDone: function (result) {
            reloadConvenienceFinancePaymentTable(filterState);
            convenienceFinanceFilterSnapshot = null;
            TechXAlert(result.success + ' record(s) rejected by finance. ' + result.failed + ' record(s) could not be updated.');
        }
    });
}
