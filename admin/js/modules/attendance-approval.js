var attendanceApprovalFilterSnapshot = null;

function formatDateYMD(date) {
    var y = date.getFullYear();
    var m = String(date.getMonth() + 1).padStart(2, '0');
    var d = String(date.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + d;
}

function getAttendanceFilterDateValue() {
    var val = document.getElementById('filter_date').value;
    if (val === 'All Time') {
        return 'all';
    }
    return val;
}

function parseAttendanceActionResponse(data) {
    if (typeof data === 'object' && data !== null) {
        return data;
    }
    try {
        return JSON.parse(data);
    } catch (e) {
        return { error: true, message: 'Invalid server response.' };
    }
}

function initAttendanceMultiSelectFilters() {
    $('.attendance-multi-filter').each(function () {
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

function syncAttendanceFilterSelect2Ui() {
    $('.attendance-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.trigger('change.select2');
        }
    });
}

function closeAttendanceFilterSelect2() {
    $('.attendance-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.select2('close');
        }
    });
}

function captureAttendanceApprovalFilters() {
    return {
        employee: $('#employee_filter').val() || [],
        status: $('#status_filter').val() || [],
        state: $('#state_filter').val() || [],
        department: $('#department_filter').val() || [],
        designation: $('#designation_filter').val() || [],
        filter_date: document.getElementById('filter_date').value,
        employee_number: document.getElementById('employee_number_filter').value
    };
}

function restoreAttendanceApprovalFilters(filterState) {
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
    syncAttendanceFilterSelect2Ui();
}

function encodeAttendanceMultiFilterValue(values) {
    if (!values || values.length === 0) {
        return '-1';
    }
    return Array.isArray(values) ? values.join(',') : values;
}

function buildAttendanceFilterParam(filterState) {
    var state = filterState || captureAttendanceApprovalFilters();
    var filter_date = state.filter_date === 'All Time' ? 'all' : state.filter_date;
    var param = '?filter_date=' + encodeURIComponent(filter_date)
        + '&EmployeeID=' + encodeURIComponent(encodeAttendanceMultiFilterValue(state.employee))
        + '&ApprovalStatus=' + encodeURIComponent(encodeAttendanceMultiFilterValue(state.status))
        + '&state=' + encodeURIComponent(encodeAttendanceMultiFilterValue(state.state))
        + '&department=' + encodeURIComponent(encodeAttendanceMultiFilterValue(state.department))
        + '&designation=' + encodeURIComponent(encodeAttendanceMultiFilterValue(state.designation))
        + '&employee_number=' + encodeURIComponent(state.employee_number || '');
    if (document.getElementById('supervisor_employee_id')) {
        param += '&Supervisor_EmployeeID=' + document.getElementById('supervisor_employee_id').value;
    }
    return param;
}

function reloadAttendanceTableKeepingPosition(selector, ajaxUrl, lastProcessedId, filterState) {
    var scrollY = window.pageYOffset || document.documentElement.scrollTop;
    $(selector).DataTable().ajax.url(ajaxUrl).load(function () {
        restoreAttendanceApprovalFilters(filterState);
        resetAttendanceSupervisorSelection();
        window.scrollTo(0, scrollY);
        focusNextAttendanceActionRow(selector, lastProcessedId);
    }, false);
}

function focusNextAttendanceActionRow(selector, lastProcessedId) {
    var $nextRow = $(selector + ' tbody tr').filter(function () {
        return $(this).find('.attendance-action-approve').length > 0;
    }).first();

    if (!$nextRow.length) {
        return;
    }

    $nextRow.addClass('attendance-row-highlight');
    setTimeout(function () {
        $nextRow.removeClass('attendance-row-highlight');
    }, 2500);

    var rowTop = $nextRow.offset().top;
    var viewportTop = window.pageYOffset + 100;
    var viewportBottom = window.pageYOffset + $(window).height() - 120;
    if (rowTop < viewportTop || rowTop > viewportBottom) {
        $('html, body').animate({ scrollTop: rowTop - 120 }, 200);
    }
}

function showAttendanceActionToast(message) {
    if (typeof alertify !== 'undefined' && alertify.success) {
        alertify.success(message, 2);
        return;
    }
    TechXAlert(message);
}

var ATTENDANCE_BULK_CHUNK_SIZE = 10;

function hideAttendanceBulkProgress() {
    var overlay = document.getElementById('attendance-bulk-progress-overlay');
    if (overlay) {
        overlay.remove();
    }
}

function showAttendanceBulkProgress(title, processed, total, statusText) {
    var safeTotal = Math.max(1, parseInt(total, 10) || 1);
    var safeProcessed = Math.max(0, Math.min(safeTotal, parseInt(processed, 10) || 0));
    var pct = Math.round((safeProcessed / safeTotal) * 100);
    var overlay = document.getElementById('attendance-bulk-progress-overlay');

    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'attendance-bulk-progress-overlay';
        overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML =
            '<div style="width:min(420px,92vw);background:#fff;border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,.25);padding:22px;font-family:Poppins,Segoe UI,sans-serif;">' +
            '  <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">' +
            '    <div style="width:38px;height:38px;border-radius:50%;border:3px solid #dbeafe;border-top-color:#027dc1;animation:attBulkSpin .9s linear infinite;"></div>' +
            '    <div>' +
            '      <div id="attendance-bulk-progress-title" style="font-size:15px;font-weight:700;color:#0f172a;"></div>' +
            '      <div id="attendance-bulk-progress-sub" style="font-size:12px;color:#64748b;margin-top:2px;"></div>' +
            '    </div>' +
            '  </div>' +
            '  <div style="height:10px;background:#e2e8f0;border-radius:999px;overflow:hidden;">' +
            '    <div id="attendance-bulk-progress-bar" style="height:100%;width:0%;background:linear-gradient(90deg,#027dc1,#0ea5e9);transition:width .25s ease;"></div>' +
            '  </div>' +
            '  <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:11px;color:#64748b;">' +
            '    <span id="attendance-bulk-progress-label">Starting…</span>' +
            '    <span id="attendance-bulk-progress-pct">0%</span>' +
            '  </div>' +
            '</div>' +
            '<style>@keyframes attBulkSpin{to{transform:rotate(360deg)}}</style>';
        document.body.appendChild(overlay);
    }

    document.getElementById('attendance-bulk-progress-title').textContent = title || 'Processing…';
    document.getElementById('attendance-bulk-progress-sub').textContent =
        (statusText || ('Processed ' + safeProcessed + ' of ' + safeTotal + ' record(s)'));
    document.getElementById('attendance-bulk-progress-bar').style.width = pct + '%';
    document.getElementById('attendance-bulk-progress-label').textContent = safeProcessed + ' / ' + safeTotal;
    document.getElementById('attendance-bulk-progress-pct').textContent = pct + '%';
}

function runAttendanceBulkInChunks(options) {
    var ids = (options.ids || []).slice();
    var url = options.url;
    var title = options.title || 'Processing selected records';
    var buildPayload = options.buildPayload || function (chunk) { return { IDs: chunk }; };
    var parseResponse = options.parseResponse || parseAttendanceActionResponse;
    var onDone = options.onDone || function () {};
    var total = ids.length;
    var processed = 0;
    var success = 0;
    var failed = 0;

    if (total === 0) {
        onDone({ success: 0, failed: 0, total: 0 });
        return;
    }

    showAttendanceBulkProgress(title, 0, total, 'Preparing…');

    function nextChunk() {
        if (ids.length === 0) {
            showAttendanceBulkProgress(title, total, total, 'Finishing…');
            setTimeout(function () {
                hideAttendanceBulkProgress();
                onDone({ success: success, failed: failed, total: total });
            }, 250);
            return;
        }

        var chunk = ids.splice(0, ATTENDANCE_BULK_CHUNK_SIZE);
        showAttendanceBulkProgress(title, processed, total);

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
            showAttendanceBulkProgress(title, processed, total);
            setTimeout(nextChunk, 40);
        }).fail(function () {
            failed += chunk.length;
            processed += chunk.length;
            showAttendanceBulkProgress(title, processed, total, 'Network issue on a batch — continuing…');
            setTimeout(nextChunk, 80);
        });
    }

    nextChunk();
}

function RefreshAttendanceApproval(filterDateValue, afterActionId) {
    var selector = '#view-attendance-approval-records';
    var filterState = captureAttendanceApprovalFilters();
    if (filterDateValue !== undefined) {
        filterState.filter_date = filterDateValue === 'all' ? 'All Time' : filterDateValue;
    }
    var param = buildAttendanceFilterParam(filterState);
    if ($.fn.DataTable.isDataTable(selector)) {
        if (afterActionId) {
            reloadAttendanceTableKeepingPosition(selector, 'action/view-attendance-approval-post.php' + param, afterActionId, filterState);
        } else {
            $(selector).DataTable().ajax.url('action/view-attendance-approval-post.php' + param).load(function () {
                restoreAttendanceApprovalFilters(filterState);
                resetAttendanceSupervisorSelection();
            }, false);
        }
    } else {
        initAttendanceApprovalTable(param);
    }
}

function getSelectedAttendanceSupervisorIds() {
    var selected = [];
    $('.attendance-supervisor-select:checked').each(function () {
        selected.push($(this).val());
    });
    return selected;
}

function updateAttendanceSupervisorSelectionUi() {
    var selected = getSelectedAttendanceSupervisorIds();
    var total = $('.attendance-supervisor-select').length;
    var count = selected.length;
    $('#attendance_supervisor_selected_count').text(count + ' selected');
    $('#btn_attendance_supervisor_bulk_approve, #btn_attendance_supervisor_bulk_reject').prop('disabled', count === 0);

    var $selectAll = $('#attendance_supervisor_select_all');
    $selectAll.prop('checked', total > 0 && count === total);
    $selectAll.prop('indeterminate', count > 0 && count < total);
}

function bindAttendanceSupervisorSelectionEvents() {
    $(document).off('change.attendanceSupervisorSelect', '.attendance-supervisor-select');
    $(document).on('change.attendanceSupervisorSelect', '.attendance-supervisor-select', updateAttendanceSupervisorSelectionUi);

    $('#attendance_supervisor_select_all').off('change.attendanceSupervisorSelectAll').on('change.attendanceSupervisorSelectAll', function () {
        var checked = $(this).is(':checked');
        $('.attendance-supervisor-select').prop('checked', checked);
        updateAttendanceSupervisorSelectionUi();
    });
}

function resetAttendanceSupervisorSelection() {
    $('.attendance-supervisor-select').prop('checked', false);
    $('#attendance_supervisor_select_all').prop('checked', false).prop('indeterminate', false);
    updateAttendanceSupervisorSelectionUi();
}

function bindAttendanceSupervisorModalUi() {
    $('#attendance_reject_modal')
        .on('show.bs.modal', function () {
            closeAttendanceFilterSelect2();
        })
        .on('hidden.bs.modal', function () {
            syncAttendanceFilterSelect2Ui();
        });
}

function setAttendanceQuickFilter(type) {
    var today = new Date();
    var start = new Date();
    var filterDate = '';

    if (type === 'pending') {
        $('#status_filter').val(['pending_supervisor']).trigger('change');
        filterDate = formatDateYMD(new Date(today.getTime() - (365 * 24 * 60 * 60 * 1000))) + ' - ' + formatDateYMD(today);
    } else if (type === 'pending_hr') {
        $('#status_filter').val(['SupervisorApproved']).trigger('change');
        filterDate = formatDateYMD(new Date(today.getTime() - (365 * 24 * 60 * 60 * 1000))) + ' - ' + formatDateYMD(today);
    } else if (type === '7days') {
        $('#status_filter').val(null).trigger('change');
        start.setDate(today.getDate() - 7);
        filterDate = formatDateYMD(start) + ' - ' + formatDateYMD(today);
    } else if (type === '30days') {
        $('#status_filter').val(null).trigger('change');
        start.setDate(today.getDate() - 30);
        filterDate = formatDateYMD(start) + ' - ' + formatDateYMD(today);
    } else if (type === '365days') {
        $('#status_filter').val(null).trigger('change');
        start.setDate(today.getDate() - 365);
        filterDate = formatDateYMD(start) + ' - ' + formatDateYMD(today);
    } else if (type === 'all') {
        $('#status_filter').val(null).trigger('change');
        filterDate = 'all';
    }

    document.getElementById('filter_date').value = filterDate === 'all' ? 'All Time' : filterDate;
    RefreshAttendanceApproval(filterDate);
}

function initAttendanceDateRangePicker() {
    var start = moment().subtract(365, 'days');
    var end = moment();
    $('#filter_date').daterangepicker({
        startDate: start,
        endDate: end,
        locale: { format: 'YYYY-MM-DD' },
        ranges: {
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'Last 90 Days': [moment().subtract(89, 'days'), moment()],
            'Last 1 Year': [moment().subtract(365, 'days'), moment()],
            'All Time': [moment('2000-01-01'), moment()]
        }
    }, function (startPick, endPick) {
        document.getElementById('filter_date').value = startPick.format('YYYY-MM-DD') + ' - ' + endPick.format('YYYY-MM-DD');
    });
}

function initAttendanceApprovalPage(initialParam) {
    initAttendanceDateRangePicker();
    initAttendanceMultiSelectFilters();
    bindAttendanceSupervisorSelectionEvents();
    bindAttendanceSupervisorModalUi();
    initAttendanceApprovalTable(initialParam);
}

function initAttendanceApprovalTable(param) {
    var selector = '#view-attendance-approval-records';
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.url('action/view-attendance-approval-post.php' + param).load(function () {
            resetAttendanceSupervisorSelection();
        }, false);
        return;
    }
    $(selector).dataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: { url: 'action/view-attendance-approval-post.php' + param },
        columns: [
            { data: 'Select', orderable: false, searchable: false },
            { data: 'EmployeeName' },
            { data: 'EmployeeNumber' },
            { data: 'RecordDate' },
            { data: 'CheckInTime' },
            { data: 'CheckOutTime' },
            { data: 'Duration' },
            { data: 'AttendanceType', orderable: false, searchable: false },
            { data: 'AttendanceReason', orderable: false, searchable: false },
            { data: 'ApprovalStatus' },
            { data: 'ApprovalInfo' },
            { data: 'Actions', orderable: false, searchable: false },
            { data: 'State' },
            { data: 'Department', responsivePriority: 1 }
        ],
        drawCallback: function () {
            resetAttendanceSupervisorSelection();
        }
    });
}

function ApproveAttendance(id) {
    attendanceApprovalFilterSnapshot = captureAttendanceApprovalFilters();
    $.post('action/approve_attendance.php', { ID: id }, function (data) {
        var response = parseAttendanceActionResponse(data);
        if (!response.error) {
            RefreshAttendanceApproval(undefined, id);
            attendanceApprovalFilterSnapshot = null;
            showAttendanceActionToast(response.message || 'Attendance approved.');
            return;
        }
        TechXAlert(response.message || 'Unable to approve attendance.');
    }, 'json').fail(function () {
        TechXAlert('Unable to approve attendance. Please try again.');
    });
}

function openAttendanceRejectModal(id) {
    attendanceApprovalFilterSnapshot = captureAttendanceApprovalFilters();
    document.getElementById('attendance_reject_mode').value = 'single';
    document.getElementById('attendance_reject_id').value = id;
    document.getElementById('attendance_reject_reason').value = '';
    $('#attendance_reject_modal').modal('show');
}

function openAttendanceSupervisorBulkRejectModal() {
    var selected = getSelectedAttendanceSupervisorIds();
    if (selected.length === 0) {
        TechXAlert('Please select at least one pending attendance record.');
        return;
    }
    attendanceApprovalFilterSnapshot = captureAttendanceApprovalFilters();
    document.getElementById('attendance_reject_mode').value = 'bulk';
    document.getElementById('attendance_reject_id').value = '';
    document.getElementById('attendance_reject_reason').value = '';
    document.getElementById('attendance_reject_bulk_info').textContent = selected.length + ' record(s) selected.';
    $('#attendance_reject_modal').modal('show');
}

function submitAttendanceSupervisorBulkApprove() {
    var selected = getSelectedAttendanceSupervisorIds();
    if (selected.length === 0) {
        TechXAlert('Please select at least one pending attendance record.');
        return;
    }
    attendanceApprovalFilterSnapshot = captureAttendanceApprovalFilters();
    $('#btn_attendance_supervisor_bulk_approve, #btn_attendance_supervisor_bulk_reject').prop('disabled', true);

    runAttendanceBulkInChunks({
        ids: selected,
        url: 'action/supervisor_bulk_approve_attendance.php',
        title: 'Approving attendance',
        onDone: function (result) {
            RefreshAttendanceApproval(undefined, selected[0]);
            attendanceApprovalFilterSnapshot = null;
            var message = result.success + ' record(s) approved by supervisor. ' + result.failed + ' record(s) could not be updated.';
            if (result.success <= 0) {
                TechXAlert(message);
            } else {
                showAttendanceActionToast(message);
            }
            updateAttendanceSupervisorSelectionUi();
        }
    });
}

function submitAttendanceReject() {
    var mode = document.getElementById('attendance_reject_mode').value;
    var reason = document.getElementById('attendance_reject_reason').value;
    var filterState = attendanceApprovalFilterSnapshot || captureAttendanceApprovalFilters();

    if (mode !== 'bulk') {
        var id = document.getElementById('attendance_reject_id').value;
        $.post('action/reject_attendance.php', { RejectionReason: reason, ID: id }, function (data) {
            var response = parseAttendanceActionResponse(data);
            if (!response.error) {
                $('#attendance_reject_modal').modal('hide');
                var param = buildAttendanceFilterParam(filterState);
                reloadAttendanceTableKeepingPosition('#view-attendance-approval-records', 'action/view-attendance-approval-post.php' + param, id, filterState);
                attendanceApprovalFilterSnapshot = null;
                showAttendanceActionToast(response.message || 'Attendance rejected.');
                return;
            }
            TechXAlert(response.message || 'Unable to reject attendance.');
        }, 'json').fail(function () {
            TechXAlert('Unable to reject attendance. Please try again.');
        });
        return;
    }

    var selected = getSelectedAttendanceSupervisorIds();
    if (!selected.length) {
        TechXAlert('Please select at least one attendance record.');
        return;
    }

    $('#attendance_reject_modal').modal('hide');
    $('#btn_attendance_supervisor_bulk_approve, #btn_attendance_supervisor_bulk_reject').prop('disabled', true);

    runAttendanceBulkInChunks({
        ids: selected,
        url: 'action/supervisor_bulk_reject_attendance.php',
        title: 'Rejecting attendance',
        buildPayload: function (chunk) {
            return { IDs: chunk, RejectionReason: reason };
        },
        onDone: function (result) {
            var param = buildAttendanceFilterParam(filterState);
            reloadAttendanceTableKeepingPosition('#view-attendance-approval-records', 'action/view-attendance-approval-post.php' + param, selected[0], filterState);
            attendanceApprovalFilterSnapshot = null;
            var message = result.success + ' record(s) rejected by supervisor. ' + result.failed + ' record(s) could not be updated.';
            if (result.success <= 0) {
                TechXAlert(message);
            } else {
                showAttendanceActionToast(message);
            }
            updateAttendanceSupervisorSelectionUi();
        }
    });
}

function openLocationModal(lat, lng) {
    document.getElementById('locationModal').style.display = 'block';
    document.getElementById('locationAddress').innerHTML = 'Loading...';
    document.getElementById('mapFrame').src = 'https://www.google.com/maps?q=' + lat + ',' + lng + '&output=embed';
    fetch('../attendance-list/action/get_address.php?lat=' + lat + '&lng=' + lng)
        .then(function (response) { return response.text(); })
        .then(function (data) {
            document.getElementById('locationAddress').innerHTML = data;
        })
        .catch(function () {
            document.getElementById('locationAddress').innerHTML = 'Unable to fetch address.';
        });
}

function closeLocationModal() {
    document.getElementById('locationModal').style.display = 'none';
}

function ViewAttendanceImage(imageUrl) {
    document.getElementById('modalImage').src = '../media/employee_attendance/' + imageUrl;
    var imageModal = new bootstrap.Modal(document.getElementById('imageModal'), { keyboard: true });
    imageModal.show();
}
