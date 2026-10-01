var convenienceHrTable = null;
var convenienceHrFilterSnapshot = null;

function parseConvenienceHrActionResponse(data) {
    if (typeof data === 'object' && data !== null) {
        return data;
    }
    try {
        return JSON.parse(data);
    } catch (e) {
        return { error: true, message: 'Invalid server response.' };
    }
}

var CONVENIENCE_HR_BULK_CHUNK_SIZE = 10;

function hideConvenienceHrBulkProgress() {
    var overlay = document.getElementById('convenience-hr-bulk-progress-overlay');
    if (overlay) {
        overlay.remove();
    }
}

function showConvenienceHrBulkProgress(title, processed, total, statusText) {
    var safeTotal = Math.max(1, parseInt(total, 10) || 1);
    var safeProcessed = Math.max(0, Math.min(safeTotal, parseInt(processed, 10) || 0));
    var pct = Math.round((safeProcessed / safeTotal) * 100);
    var overlay = document.getElementById('convenience-hr-bulk-progress-overlay');

    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'convenience-hr-bulk-progress-overlay';
        overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML =
            '<div style="width:min(420px,92vw);background:#fff;border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,.25);padding:22px;font-family:Poppins,Segoe UI,sans-serif;">' +
            '  <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">' +
            '    <div style="width:38px;height:38px;border-radius:50%;border:3px solid #dbeafe;border-top-color:#027dc1;animation:convHrBulkSpin .9s linear infinite;"></div>' +
            '    <div>' +
            '      <div id="convenience-hr-bulk-progress-title" style="font-size:15px;font-weight:700;color:#0f172a;"></div>' +
            '      <div id="convenience-hr-bulk-progress-sub" style="font-size:12px;color:#64748b;margin-top:2px;"></div>' +
            '    </div>' +
            '  </div>' +
            '  <div style="height:10px;background:#e2e8f0;border-radius:999px;overflow:hidden;">' +
            '    <div id="convenience-hr-bulk-progress-bar" style="height:100%;width:0%;background:linear-gradient(90deg,#027dc1,#0ea5e9);transition:width .25s ease;"></div>' +
            '  </div>' +
            '  <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:11px;color:#64748b;">' +
            '    <span id="convenience-hr-bulk-progress-label">Starting…</span>' +
            '    <span id="convenience-hr-bulk-progress-pct">0%</span>' +
            '  </div>' +
            '</div>' +
            '<style>@keyframes convHrBulkSpin{to{transform:rotate(360deg)}}</style>';
        document.body.appendChild(overlay);
    }

    document.getElementById('convenience-hr-bulk-progress-title').textContent = title || 'Processing…';
    document.getElementById('convenience-hr-bulk-progress-sub').textContent =
        (statusText || ('Processed ' + safeProcessed + ' of ' + safeTotal + ' record(s)'));
    document.getElementById('convenience-hr-bulk-progress-bar').style.width = pct + '%';
    document.getElementById('convenience-hr-bulk-progress-label').textContent = safeProcessed + ' / ' + safeTotal;
    document.getElementById('convenience-hr-bulk-progress-pct').textContent = pct + '%';
}

function runConvenienceHrBulkInChunks(options) {
    var ids = (options.ids || []).slice();
    var url = options.url;
    var title = options.title || 'Processing selected records';
    var buildPayload = options.buildPayload || function (chunk) { return { IDs: chunk }; };
    var parseResponse = options.parseResponse || parseConvenienceHrActionResponse;
    var onDone = options.onDone || function () {};
    var total = ids.length;
    var processed = 0;
    var success = 0;
    var failed = 0;
    var chunkSize = options.chunkSize || CONVENIENCE_HR_BULK_CHUNK_SIZE;

    if (total === 0) {
        onDone({ success: 0, failed: 0, total: 0 });
        return;
    }

    showConvenienceHrBulkProgress(title, 0, total, 'Preparing…');

    function nextChunk() {
        if (ids.length === 0) {
            showConvenienceHrBulkProgress(title, total, total, 'Finishing…');
            setTimeout(function () {
                hideConvenienceHrBulkProgress();
                onDone({ success: success, failed: failed, total: total });
            }, 250);
            return;
        }

        var chunk = ids.splice(0, chunkSize);
        showConvenienceHrBulkProgress(title, processed, total);

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
            showConvenienceHrBulkProgress(title, processed, total);
            setTimeout(nextChunk, 40);
        }).fail(function () {
            failed += chunk.length;
            processed += chunk.length;
            showConvenienceHrBulkProgress(title, processed, total, 'Network issue on a batch — continuing…');
            setTimeout(nextChunk, 80);
        });
    }

    nextChunk();
}

function getConvenienceHrMultiFilterValue(selectId) {
    var values = $('#' + selectId).val();
    if (!values || values.length === 0) {
        return '-1';
    }
    return Array.isArray(values) ? values.join(',') : values;
}

function initConvenienceHrMultiSelectFilters() {
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

function syncConvenienceHrFilterSelect2Ui() {
    $('.convenience-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.trigger('change.select2');
        }
    });
}

function captureConvenienceHrFilters() {
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

function restoreConvenienceHrFilters(filterState) {
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
    syncConvenienceHrFilterSelect2Ui();
}

function encodeConvenienceHrMultiFilterValue(values) {
    if (!values || values.length === 0) {
        return '-1';
    }
    return Array.isArray(values) ? values.join(',') : values;
}

function closeConvenienceHrFilterSelect2() {
    $('.convenience-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.select2('close');
        }
    });
}

function bindConvenienceHrModalUi() {
    $('#convenience_hr_modal')
        .on('show.bs.modal', function () {
            closeConvenienceHrFilterSelect2();
        })
        .on('hidden.bs.modal', function () {
            syncConvenienceHrFilterSelect2Ui();
        });
}

function getSelectedConvenienceHrIds() {
    var selected = [];
    $('.convenience-hr-select:checked').each(function () {
        selected.push($(this).val());
    });
    return selected;
}

function updateConvenienceHrSelectionUi() {
    var selected = getSelectedConvenienceHrIds();
    var total = $('.convenience-hr-select').length;
    var count = selected.length;
    $('#convenience_hr_selected_count').text(count + ' selected');
    $('#btn_hr_bulk_approve, #btn_hr_bulk_reject').prop('disabled', count === 0);

    var $selectAll = $('#convenience_hr_select_all');
    $selectAll.prop('checked', total > 0 && count === total);
    $selectAll.prop('indeterminate', count > 0 && count < total);
}

function bindConvenienceHrSelectionEvents() {
    $(document).off('change.convenienceHrSelect', '.convenience-hr-select');
    $(document).on('change.convenienceHrSelect', '.convenience-hr-select', updateConvenienceHrSelectionUi);

    $('#convenience_hr_select_all').off('change.convenienceHrSelectAll').on('change.convenienceHrSelectAll', function () {
        var checked = $(this).is(':checked');
        $('.convenience-hr-select').prop('checked', checked);
        updateConvenienceHrSelectionUi();
    });
}

function resetConvenienceHrSelection() {
    $('.convenience-hr-select').prop('checked', false);
    $('#convenience_hr_select_all').prop('checked', false).prop('indeterminate', false);
    updateConvenienceHrSelectionUi();
}

function initConvenienceHrApprovalPage(initialParam) {
    $("#_Nav_Convenience_HR_Approval").addClass("active open");
    $('#filter_date').daterangepicker({ locale: { format: 'YYYY-MM-DD' } });
    initConvenienceHrMultiSelectFilters();
    bindConvenienceHrSelectionEvents();
    bindConvenienceHrModalUi();
    initConvenienceHrApprovalTable(initialParam);
    refreshConvenienceHrDashboard();
}

function buildConvenienceHrParams(filterState) {
    var state = filterState || captureConvenienceHrFilters();
    var ticket = state.ticket || -1;
    return '?filter_date=' + encodeURIComponent(state.filter_date)
        + '&EmployeeID=' + encodeURIComponent(encodeConvenienceHrMultiFilterValue(state.employee))
        + '&status=' + encodeURIComponent(encodeConvenienceHrMultiFilterValue(state.status))
        + '&state=' + encodeURIComponent(encodeConvenienceHrMultiFilterValue(state.state))
        + '&department=' + encodeURIComponent(encodeConvenienceHrMultiFilterValue(state.department))
        + '&designation=' + encodeURIComponent(encodeConvenienceHrMultiFilterValue(state.designation))
        + '&ticket_id=' + encodeURIComponent(ticket)
        + '&employee_number=' + encodeURIComponent(state.employee_number || '');
}

function refreshConvenienceHrDashboard(filterState) {
    loadConvenienceDashboard('hr', buildConvenienceHrParams(filterState || captureConvenienceHrFilters()));
}

function reloadConvenienceHrApprovalTable(filterState) {
    var params = buildConvenienceHrParams(filterState);
    refreshConvenienceHrDashboard(filterState);
    var selector = '#view-convenience-hr-approval';
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.url('ajax/view-convenience-hr-approval-post.php' + params).load(function () {
            restoreConvenienceHrFilters(filterState);
            resetConvenienceHrSelection();
        }, false);
        return;
    }
    initConvenienceHrApprovalTable(params);
}

function initConvenienceHrApprovalTable(param) {
    var selector = '#view-convenience-hr-approval';
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.url('ajax/view-convenience-hr-approval-post.php' + param).load(function () {
            resetConvenienceHrSelection();
        }, false);
        return;
    }
    convenienceHrTable = $(selector).DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: { url: 'ajax/view-convenience-hr-approval-post.php' + param },
        columns: [
            { data: 'Select', orderable: false, searchable: false },
            { data: null, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: 'Employee' },
            { data: 'Ticket' },
            { data: 'Journey' },
            { data: 'Amount' },
            { data: 'Remarks' },
            { data: 'RecordDate' },
            { data: 'State' },
            { data: 'Department', responsivePriority: 1 },
            { data: 'Designation', responsivePriority: 5 },
            { data: 'Status' },
            { data: 'PaymentStatus' },
            { data: 'ApprovalInfo' },
            { data: 'Action' }
        ],
        drawCallback: function () {
            resetConvenienceHrSelection();
        }
    });
}

function RefreshConvenienceHrApproval() {
    reloadConvenienceHrApprovalTable(captureConvenienceHrFilters());
}

function setConvenienceHrQuickFilter(type) {
    if (type === 'pending_hr') {
        $('#status_filter').val(['hr_actionable']).trigger('change');
    }
    if (type === 'supervisor_timeout') {
        $('#status_filter').val(['supervisor_timeout']).trigger('change');
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
    RefreshConvenienceHrApproval();
}

function setConvenienceHrModalMode(mode, actionType) {
    document.getElementById('convenience_hr_action_mode').value = mode;
    document.getElementById('convenience_hr_bulk_action_type').value = actionType || '';
    var isBulk = mode === 'bulk';
    $('#convenience_hr_bulk_info').toggleClass('d-none', !isBulk);
    $('#btn_convenience_hr_modal_approve').toggle(!isBulk || actionType === 'approve');
    $('#btn_convenience_hr_modal_reject').toggle(!isBulk || actionType === 'reject');
    $('#convenience_hr_amount_group').toggle(!isBulk || actionType === 'approve');
    $('.modal-title', '#convenience_hr_modal').text(isBulk ? 'HR Bulk ' + (actionType === 'approve' ? 'Approval' : 'Rejection') : 'HR Final Approval');
}

function openConvenienceHrAction(id, amount) {
    convenienceHrFilterSnapshot = captureConvenienceHrFilters();
    setConvenienceHrModalMode('single', '');
    document.getElementById('convenience_hr_action_id').value = id;
    document.getElementById('convenience_hr_approved_amount').value = amount || '';
    document.getElementById('convenience_hr_remarks').value = '';
    $('#convenience_hr_modal').modal('show');
}

function openConvenienceHrBulkModal(actionType) {
    var selected = getSelectedConvenienceHrIds();
    if (selected.length === 0) {
        TechXAlert('Please select at least one pending HR convenience request.');
        return;
    }
    convenienceHrFilterSnapshot = captureConvenienceHrFilters();
    setConvenienceHrModalMode('bulk', actionType);
    document.getElementById('convenience_hr_action_id').value = '';
    document.getElementById('convenience_hr_approved_amount').value = '';
    document.getElementById('convenience_hr_remarks').value = '';
    document.getElementById('convenience_hr_bulk_info').textContent = selected.length + ' record(s) selected.';
    $('#convenience_hr_modal').modal('show');
}

function submitConvenienceHrApprove() {
    var filterState = convenienceHrFilterSnapshot || captureConvenienceHrFilters();
    var mode = document.getElementById('convenience_hr_action_mode').value;
    var payloadBase = {
        approved_amount: document.getElementById('convenience_hr_approved_amount').value,
        remarks: document.getElementById('convenience_hr_remarks').value
    };

    if (mode !== 'bulk') {
        $.post('action/hr_approve_convenience.php', {
            approved_amount: payloadBase.approved_amount,
            remarks: payloadBase.remarks,
            ID: document.getElementById('convenience_hr_action_id').value
        }, function (data) {
            var response = parseConvenienceHrActionResponse(data);
            if (!response.error) {
                $('#convenience_hr_modal').modal('hide');
                reloadConvenienceHrApprovalTable(filterState);
                convenienceHrFilterSnapshot = null;
            }
            TechXAlert(response.message);
        }, 'json').fail(function (xhr) {
            TechXAlert('Unable to approve. ' + (xhr.responseText || 'Server error'));
        });
        return;
    }

    var selected = getSelectedConvenienceHrIds();
    if (!selected.length) {
        TechXAlert('Please select at least one pending HR convenience request.');
        return;
    }

    $('#convenience_hr_modal').modal('hide');
    $('#btn_hr_bulk_approve, #btn_hr_bulk_reject').prop('disabled', true);

    runConvenienceHrBulkInChunks({
        ids: selected,
        url: 'action/hr_bulk_approve_convenience.php',
        title: 'Approving convenience (HR)',
        buildPayload: function (chunk) {
            return {
                IDs: chunk,
                approved_amount: payloadBase.approved_amount,
                remarks: payloadBase.remarks
            };
        },
        onDone: function (result) {
            reloadConvenienceHrApprovalTable(filterState);
            convenienceHrFilterSnapshot = null;
            TechXAlert(result.success + ' record(s) approved by HR. ' + result.failed + ' record(s) could not be updated.');
        }
    });
}

function submitConvenienceHrReject() {
    var filterState = convenienceHrFilterSnapshot || captureConvenienceHrFilters();
    var mode = document.getElementById('convenience_hr_action_mode').value;
    var reason = document.getElementById('convenience_hr_remarks').value;

    if (mode !== 'bulk') {
        $.post('action/hr_reject_convenience.php', {
            reason: reason,
            ID: document.getElementById('convenience_hr_action_id').value
        }, function (data) {
            var response = parseConvenienceHrActionResponse(data);
            if (!response.error) {
                $('#convenience_hr_modal').modal('hide');
                reloadConvenienceHrApprovalTable(filterState);
                convenienceHrFilterSnapshot = null;
            }
            TechXAlert(response.message);
        }, 'json').fail(function (xhr) {
            TechXAlert('Unable to reject. ' + (xhr.responseText || 'Server error'));
        });
        return;
    }

    var selected = getSelectedConvenienceHrIds();
    if (!selected.length) {
        TechXAlert('Please select at least one pending HR convenience request.');
        return;
    }

    $('#convenience_hr_modal').modal('hide');
    $('#btn_hr_bulk_approve, #btn_hr_bulk_reject').prop('disabled', true);

    runConvenienceHrBulkInChunks({
        ids: selected,
        url: 'action/hr_bulk_reject_convenience.php',
        title: 'Rejecting convenience (HR)',
        buildPayload: function (chunk) {
            return { IDs: chunk, reason: reason };
        },
        onDone: function (result) {
            reloadConvenienceHrApprovalTable(filterState);
            convenienceHrFilterSnapshot = null;
            TechXAlert(result.success + ' record(s) rejected by HR. ' + result.failed + ' record(s) could not be updated.');
        }
    });
}

function ExportConvenienceHrData() {
    document.getElementById('filter_date_export').value = document.getElementById('filter_date').value;
    document.getElementById('employee_filter_export').value = getConvenienceHrMultiFilterValue('employee_filter');
    document.getElementById('status_export').value = getConvenienceHrMultiFilterValue('status_filter');
    document.getElementById('state_export').value = getConvenienceHrMultiFilterValue('state_filter');
    document.getElementById('department_export').value = getConvenienceHrMultiFilterValue('department_filter');
    document.getElementById('designation_export').value = getConvenienceHrMultiFilterValue('designation_filter');
    document.getElementById('ticket_export').value = document.getElementById('ticket_filter').value || -1;
    document.getElementById('employee_number_export').value = document.getElementById('employee_number_filter').value;
    $.ajax({
        url: '../employees-convenience/action/export_employee_convenience_data.php',
        type: 'POST',
        data: $('#export_form').serialize(),
        success: function () {
            window.location.href = '../employees-convenience/report.xls';
        }
    });
}
