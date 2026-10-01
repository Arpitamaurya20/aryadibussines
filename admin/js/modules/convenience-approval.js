var convenienceApprovalTable = null;
var convenienceApprovalFilterSnapshot = null;

function parseConvenienceActionResponse(data) {
    if (typeof data === 'object' && data !== null) {
        return data;
    }
    try {
        return JSON.parse(data);
    } catch (e) {
        return { error: true, message: 'Invalid server response.' };
    }
}

var CONVENIENCE_BULK_CHUNK_SIZE = 10;

function hideConvenienceBulkProgress() {
    var overlay = document.getElementById('convenience-bulk-progress-overlay');
    if (overlay) {
        overlay.remove();
    }
}

function showConvenienceBulkProgress(title, processed, total, statusText) {
    var safeTotal = Math.max(1, parseInt(total, 10) || 1);
    var safeProcessed = Math.max(0, Math.min(safeTotal, parseInt(processed, 10) || 0));
    var pct = Math.round((safeProcessed / safeTotal) * 100);
    var overlay = document.getElementById('convenience-bulk-progress-overlay');

    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'convenience-bulk-progress-overlay';
        overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML =
            '<div style="width:min(420px,92vw);background:#fff;border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,.25);padding:22px;font-family:Poppins,Segoe UI,sans-serif;">' +
            '  <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">' +
            '    <div style="width:38px;height:38px;border-radius:50%;border:3px solid #dbeafe;border-top-color:#027dc1;animation:convBulkSpin .9s linear infinite;"></div>' +
            '    <div>' +
            '      <div id="convenience-bulk-progress-title" style="font-size:15px;font-weight:700;color:#0f172a;"></div>' +
            '      <div id="convenience-bulk-progress-sub" style="font-size:12px;color:#64748b;margin-top:2px;"></div>' +
            '    </div>' +
            '  </div>' +
            '  <div style="height:10px;background:#e2e8f0;border-radius:999px;overflow:hidden;">' +
            '    <div id="convenience-bulk-progress-bar" style="height:100%;width:0%;background:linear-gradient(90deg,#027dc1,#0ea5e9);transition:width .25s ease;"></div>' +
            '  </div>' +
            '  <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:11px;color:#64748b;">' +
            '    <span id="convenience-bulk-progress-label">Starting…</span>' +
            '    <span id="convenience-bulk-progress-pct">0%</span>' +
            '  </div>' +
            '</div>' +
            '<style>@keyframes convBulkSpin{to{transform:rotate(360deg)}}</style>';
        document.body.appendChild(overlay);
    }

    document.getElementById('convenience-bulk-progress-title').textContent = title || 'Processing…';
    document.getElementById('convenience-bulk-progress-sub').textContent =
        (statusText || ('Processed ' + safeProcessed + ' of ' + safeTotal + ' record(s)'));
    document.getElementById('convenience-bulk-progress-bar').style.width = pct + '%';
    document.getElementById('convenience-bulk-progress-label').textContent = safeProcessed + ' / ' + safeTotal;
    document.getElementById('convenience-bulk-progress-pct').textContent = pct + '%';
}

function runConvenienceBulkInChunks(options) {
    var ids = (options.ids || []).slice();
    var url = options.url;
    var title = options.title || 'Processing selected records';
    var buildPayload = options.buildPayload || function (chunk) { return { IDs: chunk }; };
    var parseResponse = options.parseResponse || parseConvenienceActionResponse;
    var onDone = options.onDone || function () {};
    var total = ids.length;
    var processed = 0;
    var success = 0;
    var failed = 0;

    if (total === 0) {
        onDone({ success: 0, failed: 0, total: 0 });
        return;
    }

    showConvenienceBulkProgress(title, 0, total, 'Preparing…');

    function nextChunk() {
        if (ids.length === 0) {
            showConvenienceBulkProgress(title, total, total, 'Finishing…');
            setTimeout(function () {
                hideConvenienceBulkProgress();
                onDone({ success: success, failed: failed, total: total });
            }, 250);
            return;
        }

        var chunk = ids.splice(0, CONVENIENCE_BULK_CHUNK_SIZE);
        showConvenienceBulkProgress(title, processed, total);

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
            showConvenienceBulkProgress(title, processed, total);
            setTimeout(nextChunk, 40);
        }).fail(function () {
            failed += chunk.length;
            processed += chunk.length;
            showConvenienceBulkProgress(title, processed, total, 'Network issue on a batch — continuing…');
            setTimeout(nextChunk, 80);
        });
    }

    nextChunk();
}

function getConvenienceMultiFilterValue(selectId) {
    var values = $('#' + selectId).val();
    if (!values || values.length === 0) {
        return '-1';
    }
    return Array.isArray(values) ? values.join(',') : values;
}

function initConvenienceMultiSelectFilters() {
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

function syncConvenienceFilterSelect2Ui() {
    $('.convenience-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.trigger('change.select2');
        }
    });
}

function captureConvenienceApprovalFilters() {
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

function restoreConvenienceApprovalFilters(filterState) {
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
    syncConvenienceFilterSelect2Ui();
}

function encodeConvenienceMultiFilterValue(values) {
    if (!values || values.length === 0) {
        return '-1';
    }
    return Array.isArray(values) ? values.join(',') : values;
}

function closeConvenienceFilterSelect2() {
    $('.convenience-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.select2('close');
        }
    });
}

function bindConvenienceSupervisorModalUi() {
    $('#convenience_supervisor_modal')
        .on('show.bs.modal', function () {
            closeConvenienceFilterSelect2();
        })
        .on('hidden.bs.modal', function () {
            syncConvenienceFilterSelect2Ui();
        });
}

function getSelectedConvenienceSupervisorIds() {
    var selected = [];
    $('.convenience-supervisor-select:checked').each(function () {
        selected.push($(this).val());
    });
    return selected;
}

function updateConvenienceSupervisorSelectionUi() {
    var selected = getSelectedConvenienceSupervisorIds();
    var total = $('.convenience-supervisor-select').length;
    var count = selected.length;
    $('#convenience_supervisor_selected_count').text(count + ' selected');
    $('#btn_supervisor_bulk_approve, #btn_supervisor_bulk_reject').prop('disabled', count === 0);

    var $selectAll = $('#convenience_supervisor_select_all');
    $selectAll.prop('checked', total > 0 && count === total);
    $selectAll.prop('indeterminate', count > 0 && count < total);
}

function bindConvenienceSupervisorSelectionEvents() {
    $(document).off('change.convenienceSupervisorSelect', '.convenience-supervisor-select');
    $(document).on('change.convenienceSupervisorSelect', '.convenience-supervisor-select', updateConvenienceSupervisorSelectionUi);

    $('#convenience_supervisor_select_all').off('change.convenienceSupervisorSelectAll').on('change.convenienceSupervisorSelectAll', function () {
        var checked = $(this).is(':checked');
        $('.convenience-supervisor-select').prop('checked', checked);
        updateConvenienceSupervisorSelectionUi();
    });
}

function resetConvenienceSupervisorSelection() {
    $('.convenience-supervisor-select').prop('checked', false);
    $('#convenience_supervisor_select_all').prop('checked', false).prop('indeterminate', false);
    updateConvenienceSupervisorSelectionUi();
}

function initConvenienceApprovalPage(initialParam) {
    $("#_Nav_Convenience_Approval").addClass("active open");
    $('#filter_date').daterangepicker({ locale: { format: 'YYYY-MM-DD' } });
    initConvenienceMultiSelectFilters();
    bindConvenienceSupervisorSelectionEvents();
    bindConvenienceSupervisorModalUi();
    initConvenienceApprovalTable(initialParam);
    refreshConvenienceSupervisorDashboard();
}

function buildConvenienceApprovalParams(filterState) {
    var state = filterState || captureConvenienceApprovalFilters();
    var ticket = state.ticket || -1;
    return '?filter_date=' + encodeURIComponent(state.filter_date)
        + '&EmployeeID=' + encodeURIComponent(encodeConvenienceMultiFilterValue(state.employee))
        + '&status=' + encodeURIComponent(encodeConvenienceMultiFilterValue(state.status))
        + '&department=' + encodeURIComponent(encodeConvenienceMultiFilterValue(state.department))
        + '&designation=' + encodeURIComponent(encodeConvenienceMultiFilterValue(state.designation))
        + '&ticket_id=' + encodeURIComponent(ticket)
        + '&employee_number=' + encodeURIComponent(state.employee_number || '')
        + '&Supervisor_EmployeeID=' + encodeURIComponent(document.getElementById('supervisor_employee_id').value)
        + '&state=' + encodeURIComponent(encodeConvenienceMultiFilterValue(state.state));
}

function refreshConvenienceSupervisorDashboard(filterState) {
    loadConvenienceDashboard('supervisor', buildConvenienceApprovalParams(filterState || captureConvenienceApprovalFilters()));
}

function reloadConvenienceApprovalTable(filterState) {
    var params = buildConvenienceApprovalParams(filterState);
    refreshConvenienceSupervisorDashboard(filterState);
    var selector = '#view-convenience-approval';
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.url('ajax/view-convenience-approval-post.php' + params).load(function () {
            restoreConvenienceApprovalFilters(filterState);
            resetConvenienceSupervisorSelection();
        }, false);
        return;
    }
    initConvenienceApprovalTable(params);
}

function initConvenienceApprovalTable(param) {
    var selector = '#view-convenience-approval';
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.url('ajax/view-convenience-approval-post.php' + param).load(function () {
            resetConvenienceSupervisorSelection();
        }, false);
        return;
    }
    convenienceApprovalTable = $(selector).DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: { url: 'ajax/view-convenience-approval-post.php' + param },
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
            { data: 'Status' },
            { data: 'ApprovalInfo' },
            { data: 'Action' }
        ],
        drawCallback: function () {
            resetConvenienceSupervisorSelection();
        }
    });
}

function RefreshConvenienceApproval() {
    reloadConvenienceApprovalTable(captureConvenienceApprovalFilters());
}

function setConvenienceQuickFilter(type) {
    if (type === 'pending') {
        $('#status_filter').val(['1']).trigger('change');
    }
    if (type === '7days') {
        var end = moment().format('YYYY-MM-DD');
        var start = moment().subtract(7, 'days').format('YYYY-MM-DD');
        document.getElementById('filter_date').value = start + ' - ' + end;
    }
    if (type === '365days') {
        var endDate = moment().format('YYYY-MM-DD');
        var startDate = moment().subtract(365, 'days').format('YYYY-MM-DD');
        document.getElementById('filter_date').value = startDate + ' - ' + endDate;
    }
    if (type === 'all') {
        document.getElementById('filter_date').value = '2020-01-01 - ' + moment().format('YYYY-MM-DD');
        $('#status_filter').val(null).trigger('change');
    }
    RefreshConvenienceApproval();
}

function setConvenienceSupervisorModalMode(mode, actionType) {
    document.getElementById('convenience_action_mode').value = mode;
    document.getElementById('convenience_bulk_action_type').value = actionType || '';
    var isBulk = mode === 'bulk';
    $('#convenience_supervisor_bulk_info').toggleClass('d-none', !isBulk);
    $('#btn_convenience_supervisor_modal_approve').toggle(!isBulk || actionType === 'approve');
    $('#btn_convenience_supervisor_modal_reject').toggle(!isBulk || actionType === 'reject');
    $('.modal-title', '#convenience_supervisor_modal').text(isBulk ? 'Supervisor Bulk ' + (actionType === 'approve' ? 'Approval' : 'Rejection') : 'Supervisor Approval');
}

function openConvenienceSupervisorAction(id) {
    convenienceApprovalFilterSnapshot = captureConvenienceApprovalFilters();
    setConvenienceSupervisorModalMode('single', '');
    document.getElementById('convenience_action_id').value = id;
    document.getElementById('convenience_action_remarks').value = '';
    $('#convenience_supervisor_modal').modal('show');
}

function openConvenienceSupervisorBulkModal(actionType) {
    var selected = getSelectedConvenienceSupervisorIds();
    if (selected.length === 0) {
        TechXAlert('Please select at least one pending convenience request.');
        return;
    }
    convenienceApprovalFilterSnapshot = captureConvenienceApprovalFilters();
    setConvenienceSupervisorModalMode('bulk', actionType);
    document.getElementById('convenience_action_id').value = '';
    document.getElementById('convenience_action_remarks').value = '';
    document.getElementById('convenience_supervisor_bulk_info').textContent = selected.length + ' record(s) selected.';
    $('#convenience_supervisor_modal').modal('show');
}

function submitConvenienceSupervisorApprove() {
    var filterState = convenienceApprovalFilterSnapshot || captureConvenienceApprovalFilters();
    var mode = document.getElementById('convenience_action_mode').value;
    var remarks = document.getElementById('convenience_action_remarks').value;

    if (mode !== 'bulk') {
        $.post('action/supervisor_approve_convenience.php', {
            remarks: remarks,
            ID: document.getElementById('convenience_action_id').value
        }, function (data) {
            var response = parseConvenienceActionResponse(data);
            if (!response.error) {
                $('#convenience_supervisor_modal').modal('hide');
                reloadConvenienceApprovalTable(filterState);
                convenienceApprovalFilterSnapshot = null;
            }
            TechXAlert(response.message);
        }, 'json').fail(function (xhr) {
            TechXAlert('Unable to approve. ' + (xhr.responseText || 'Server error'));
        });
        return;
    }

    var selected = getSelectedConvenienceSupervisorIds();
    if (!selected.length) {
        TechXAlert('Please select at least one pending convenience request.');
        return;
    }

    $('#convenience_supervisor_modal').modal('hide');
    $('#btn_supervisor_bulk_approve, #btn_supervisor_bulk_reject').prop('disabled', true);

    runConvenienceBulkInChunks({
        ids: selected,
        url: 'action/supervisor_bulk_approve_convenience.php',
        title: 'Approving convenience',
        buildPayload: function (chunk) {
            return { IDs: chunk, remarks: remarks };
        },
        onDone: function (result) {
            reloadConvenienceApprovalTable(filterState);
            convenienceApprovalFilterSnapshot = null;
            TechXAlert(result.success + ' record(s) approved by supervisor. ' + result.failed + ' record(s) could not be updated.');
        }
    });
}

function submitConvenienceSupervisorReject() {
    var filterState = convenienceApprovalFilterSnapshot || captureConvenienceApprovalFilters();
    var mode = document.getElementById('convenience_action_mode').value;
    var reason = document.getElementById('convenience_action_remarks').value;

    if (mode !== 'bulk') {
        $.post('action/supervisor_reject_convenience.php', {
            reason: reason,
            ID: document.getElementById('convenience_action_id').value
        }, function (data) {
            var response = parseConvenienceActionResponse(data);
            if (!response.error) {
                $('#convenience_supervisor_modal').modal('hide');
                reloadConvenienceApprovalTable(filterState);
                convenienceApprovalFilterSnapshot = null;
            }
            TechXAlert(response.message);
        }, 'json').fail(function (xhr) {
            TechXAlert('Unable to reject. ' + (xhr.responseText || 'Server error'));
        });
        return;
    }

    var selected = getSelectedConvenienceSupervisorIds();
    if (!selected.length) {
        TechXAlert('Please select at least one pending convenience request.');
        return;
    }

    $('#convenience_supervisor_modal').modal('hide');
    $('#btn_supervisor_bulk_approve, #btn_supervisor_bulk_reject').prop('disabled', true);

    runConvenienceBulkInChunks({
        ids: selected,
        url: 'action/supervisor_bulk_reject_convenience.php',
        title: 'Rejecting convenience',
        buildPayload: function (chunk) {
            return { IDs: chunk, reason: reason };
        },
        onDone: function (result) {
            reloadConvenienceApprovalTable(filterState);
            convenienceApprovalFilterSnapshot = null;
            TechXAlert(result.success + ' record(s) rejected by supervisor. ' + result.failed + ' record(s) could not be updated.');
        }
    });
}

function ExportConvenienceSupervisorData() {
    document.getElementById('filter_date_export').value = document.getElementById('filter_date').value;
    document.getElementById('employee_filter_export').value = getConvenienceMultiFilterValue('employee_filter');
    document.getElementById('status_export').value = getConvenienceMultiFilterValue('status_filter');
    document.getElementById('state_export').value = getConvenienceMultiFilterValue('state_filter');
    document.getElementById('department_export').value = getConvenienceMultiFilterValue('department_filter');
    document.getElementById('designation_export').value = getConvenienceMultiFilterValue('designation_filter');
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
