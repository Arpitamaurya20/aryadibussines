var attendanceHrFilterSnapshot = null;

function formatDateYMD(date) {
    var y = date.getFullYear();
    var m = String(date.getMonth() + 1).padStart(2, '0');
    var d = String(date.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + d;
}

function getHrAttendanceFilterDateValue() {
    var val = document.getElementById('filter_date').value;
    if (val === 'All Time') {
        return 'all';
    }
    return val;
}

function parseHrAttendanceActionResponse(data) {
    if (typeof data === 'object' && data !== null) {
        return data;
    }
    try {
        return JSON.parse(data);
    } catch (e) {
        return { error: true, message: 'Invalid server response.' };
    }
}

function initHrAttendanceMultiSelectFilters() {
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

function syncHrAttendanceFilterSelect2Ui() {
    $('.attendance-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.trigger('change.select2');
        }
    });
}

function closeHrAttendanceFilterSelect2() {
    $('.attendance-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.select2('close');
        }
    });
}

function captureHrAttendanceFilters() {
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

function restoreHrAttendanceFilters(filterState) {
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
    syncHrAttendanceFilterSelect2Ui();
}

function encodeHrAttendanceMultiFilterValue(values) {
    if (!values || values.length === 0) {
        return '-1';
    }
    return Array.isArray(values) ? values.join(',') : values;
}

function buildHrAttendanceFilterParam(filterState) {
    var state = filterState || captureHrAttendanceFilters();
    var filter_date = state.filter_date === 'All Time' ? 'all' : state.filter_date;
    return '?filter_date=' + encodeURIComponent(filter_date)
        + '&EmployeeID=' + encodeURIComponent(encodeHrAttendanceMultiFilterValue(state.employee))
        + '&ApprovalStatus=' + encodeURIComponent(encodeHrAttendanceMultiFilterValue(state.status))
        + '&state=' + encodeURIComponent(encodeHrAttendanceMultiFilterValue(state.state))
        + '&department=' + encodeURIComponent(encodeHrAttendanceMultiFilterValue(state.department))
        + '&designation=' + encodeURIComponent(encodeHrAttendanceMultiFilterValue(state.designation))
        + '&employee_number=' + encodeURIComponent(state.employee_number || '');
}

function reloadHrAttendanceTableKeepingPosition(selector, ajaxUrl, lastProcessedId, filterState) {
    var scrollY = window.pageYOffset || document.documentElement.scrollTop;
    $(selector).DataTable().ajax.url(ajaxUrl).load(function () {
        restoreHrAttendanceFilters(filterState);
        resetAttendanceHrSelection();
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

function showHrAttendanceActionToast(message) {
    if (typeof alertify !== 'undefined' && alertify.success) {
        alertify.success(message, 2);
        return;
    }
    TechXAlert(message);
}

var ATTENDANCE_HR_BULK_CHUNK_SIZE = 10;

function hideAttendanceHrBulkProgress() {
    var overlay = document.getElementById('attendance-hr-bulk-progress-overlay');
    if (overlay) {
        overlay.remove();
    }
}

function showAttendanceHrBulkProgress(title, processed, total, statusText) {
    var safeTotal = Math.max(1, parseInt(total, 10) || 1);
    var safeProcessed = Math.max(0, Math.min(safeTotal, parseInt(processed, 10) || 0));
    var pct = Math.round((safeProcessed / safeTotal) * 100);
    var overlay = document.getElementById('attendance-hr-bulk-progress-overlay');

    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'attendance-hr-bulk-progress-overlay';
        overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML =
            '<div style="width:min(420px,92vw);background:#fff;border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,.25);padding:22px;font-family:Poppins,Segoe UI,sans-serif;">' +
            '  <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">' +
            '    <div style="width:38px;height:38px;border-radius:50%;border:3px solid #dbeafe;border-top-color:#027dc1;animation:attHrBulkSpin .9s linear infinite;"></div>' +
            '    <div>' +
            '      <div id="attendance-hr-bulk-progress-title" style="font-size:15px;font-weight:700;color:#0f172a;"></div>' +
            '      <div id="attendance-hr-bulk-progress-sub" style="font-size:12px;color:#64748b;margin-top:2px;"></div>' +
            '    </div>' +
            '  </div>' +
            '  <div style="height:10px;background:#e2e8f0;border-radius:999px;overflow:hidden;">' +
            '    <div id="attendance-hr-bulk-progress-bar" style="height:100%;width:0%;background:linear-gradient(90deg,#027dc1,#0ea5e9);transition:width .25s ease;"></div>' +
            '  </div>' +
            '  <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:11px;color:#64748b;">' +
            '    <span id="attendance-hr-bulk-progress-label">Starting…</span>' +
            '    <span id="attendance-hr-bulk-progress-pct">0%</span>' +
            '  </div>' +
            '</div>' +
            '<style>@keyframes attHrBulkSpin{to{transform:rotate(360deg)}}</style>';
        document.body.appendChild(overlay);
    }

    document.getElementById('attendance-hr-bulk-progress-title').textContent = title || 'Processing…';
    document.getElementById('attendance-hr-bulk-progress-sub').textContent =
        (statusText || ('Processed ' + safeProcessed + ' of ' + safeTotal + ' record(s)'));
    document.getElementById('attendance-hr-bulk-progress-bar').style.width = pct + '%';
    document.getElementById('attendance-hr-bulk-progress-label').textContent = safeProcessed + ' / ' + safeTotal;
    document.getElementById('attendance-hr-bulk-progress-pct').textContent = pct + '%';
}

function runAttendanceHrBulkInChunks(options) {
    var ids = (options.ids || []).slice();
    var url = options.url;
    var title = options.title || 'Processing selected records';
    var buildPayload = options.buildPayload || function (chunk) { return { IDs: chunk }; };
    var parseResponse = options.parseResponse || parseHrAttendanceActionResponse;
    var onDone = options.onDone || function () {};
    var total = ids.length;
    var processed = 0;
    var success = 0;
    var failed = 0;

    if (total === 0) {
        onDone({ success: 0, failed: 0, total: 0 });
        return;
    }

    showAttendanceHrBulkProgress(title, 0, total, 'Preparing…');

    function nextChunk() {
        if (ids.length === 0) {
            showAttendanceHrBulkProgress(title, total, total, 'Finishing…');
            setTimeout(function () {
                hideAttendanceHrBulkProgress();
                onDone({ success: success, failed: failed, total: total });
            }, 250);
            return;
        }

        var chunk = ids.splice(0, ATTENDANCE_HR_BULK_CHUNK_SIZE);
        showAttendanceHrBulkProgress(title, processed, total);

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
            showAttendanceHrBulkProgress(title, processed, total);
            setTimeout(nextChunk, 40);
        }).fail(function () {
            failed += chunk.length;
            processed += chunk.length;
            showAttendanceHrBulkProgress(title, processed, total, 'Network issue on a batch — continuing…');
            setTimeout(nextChunk, 80);
        });
    }

    nextChunk();
}

function RefreshHrAttendanceApproval(filterDateValue, afterActionId) {
    var selector = '#view-attendance-hr-approval-records';
    var filterState = captureHrAttendanceFilters();
    if (filterDateValue !== undefined) {
        filterState.filter_date = filterDateValue === 'all' ? 'All Time' : filterDateValue;
    }
    var param = buildHrAttendanceFilterParam(filterState);
    if ($.fn.DataTable.isDataTable(selector)) {
        if (afterActionId) {
            reloadHrAttendanceTableKeepingPosition(selector, 'action/view-attendance-hr-approval-post.php' + param, afterActionId, filterState);
        } else {
            $(selector).DataTable().ajax.url('action/view-attendance-hr-approval-post.php' + param).load(function () {
                restoreHrAttendanceFilters(filterState);
                resetAttendanceHrSelection();
            }, false);
        }
    } else {
        initHrAttendanceApprovalTable(param);
    }
}

function getSelectedAttendanceHrIds() {
    var selected = [];
    $('.attendance-hr-select:checked').each(function () {
        selected.push($(this).val());
    });
    return selected;
}

function updateAttendanceHrSelectionUi() {
    var selected = getSelectedAttendanceHrIds();
    var total = $('.attendance-hr-select').length;
    var count = selected.length;
    $('#attendance_hr_selected_count').text(count + ' selected');
    $('#btn_attendance_hr_bulk_approve, #btn_attendance_hr_bulk_reject').prop('disabled', count === 0);

    var $selectAll = $('#attendance_hr_select_all');
    $selectAll.prop('checked', total > 0 && count === total);
    $selectAll.prop('indeterminate', count > 0 && count < total);
}

function bindAttendanceHrSelectionEvents() {
    $(document).off('change.attendanceHrSelect', '.attendance-hr-select');
    $(document).on('change.attendanceHrSelect', '.attendance-hr-select', updateAttendanceHrSelectionUi);

    $('#attendance_hr_select_all').off('change.attendanceHrSelectAll').on('change.attendanceHrSelectAll', function () {
        var checked = $(this).is(':checked');
        $('.attendance-hr-select').prop('checked', checked);
        updateAttendanceHrSelectionUi();
    });
}

function resetAttendanceHrSelection() {
    $('.attendance-hr-select').prop('checked', false);
    $('#attendance_hr_select_all').prop('checked', false).prop('indeterminate', false);
    updateAttendanceHrSelectionUi();
}

function bindAttendanceHrModalUi() {
    $('#attendance_hr_reject_modal')
        .on('show.bs.modal', function () {
            closeHrAttendanceFilterSelect2();
        })
        .on('hidden.bs.modal', function () {
            syncHrAttendanceFilterSelect2Ui();
        });
}

function setHrAttendanceQuickFilter(type) {
    var today = new Date();
    var start = new Date();
    var filterDate = '';

    if (type === 'pending_hr') {
        $('#status_filter').val(['hr_actionable']).trigger('change');
        filterDate = formatDateYMD(new Date(today.getTime() - (365 * 24 * 60 * 60 * 1000))) + ' - ' + formatDateYMD(today);
    } else if (type === '7days') {
        $('#status_filter').val(null).trigger('change');
        start.setDate(today.getDate() - 7);
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
    RefreshHrAttendanceApproval(filterDate);
}

function initHrAttendanceDateRangePicker() {
    $('#filter_date').daterangepicker({
        startDate: moment().subtract(365, 'days'),
        endDate: moment(),
        locale: { format: 'YYYY-MM-DD' },
        ranges: {
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'Last 1 Year': [moment().subtract(365, 'days'), moment()],
            'All Time': [moment('2000-01-01'), moment()]
        }
    });
}

function initHrAttendanceApprovalPage(initialParam) {
    initHrAttendanceDateRangePicker();
    initHrAttendanceMultiSelectFilters();
    bindAttendanceHrSelectionEvents();
    bindAttendanceHrModalUi();
    initHrAttendanceApprovalTable(initialParam);
}

function initHrAttendanceApprovalTable(param) {
    var selector = '#view-attendance-hr-approval-records';
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.url('action/view-attendance-hr-approval-post.php' + param).load(function () {
            resetAttendanceHrSelection();
        }, false);
        return;
    }
    $(selector).dataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: { url: 'action/view-attendance-hr-approval-post.php' + param },
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
            { data: 'Department', responsivePriority: 1 },
            { data: 'Designation', responsivePriority: 5 }
        ],
        drawCallback: function () {
            resetAttendanceHrSelection();
        }
    });
}

function HrApproveAttendance(id) {
    attendanceHrFilterSnapshot = captureHrAttendanceFilters();
    $.post('action/approve_attendance.php', { ID: id }, function (data) {
        var response = parseHrAttendanceActionResponse(data);
        if (!response.error) {
            RefreshHrAttendanceApproval(undefined, id);
            attendanceHrFilterSnapshot = null;
            showHrAttendanceActionToast(response.message || 'HR approval completed.');
            return;
        }
        TechXAlert(response.message || 'Unable to complete HR approval.');
    }, 'json').fail(function () {
        TechXAlert('Unable to complete HR approval. Please try again.');
    });
}

function openHrAttendanceRejectModal(id) {
    attendanceHrFilterSnapshot = captureHrAttendanceFilters();
    document.getElementById('attendance_hr_reject_mode').value = 'single';
    document.getElementById('attendance_hr_reject_id').value = id;
    document.getElementById('attendance_hr_reject_reason').value = '';
    $('#attendance_hr_reject_bulk_info').addClass('d-none');
    $('#attendance_hr_reject_modal').modal('show');
}

function openAttendanceHrBulkRejectModal() {
    var selected = getSelectedAttendanceHrIds();
    if (selected.length === 0) {
        TechXAlert('Please select at least one pending HR attendance record.');
        return;
    }
    attendanceHrFilterSnapshot = captureHrAttendanceFilters();
    document.getElementById('attendance_hr_reject_mode').value = 'bulk';
    document.getElementById('attendance_hr_reject_id').value = '';
    document.getElementById('attendance_hr_reject_reason').value = '';
    document.getElementById('attendance_hr_reject_bulk_info').textContent = selected.length + ' record(s) selected.';
    $('#attendance_hr_reject_bulk_info').removeClass('d-none');
    $('#attendance_hr_reject_modal').modal('show');
}

function submitAttendanceHrBulkApprove() {
    var selected = getSelectedAttendanceHrIds();
    if (selected.length === 0) {
        TechXAlert('Please select at least one pending HR attendance record.');
        return;
    }
    attendanceHrFilterSnapshot = captureHrAttendanceFilters();
    $('#btn_attendance_hr_bulk_approve, #btn_attendance_hr_bulk_reject').prop('disabled', true);

    runAttendanceHrBulkInChunks({
        ids: selected,
        url: 'action/hr_bulk_approve_attendance.php',
        title: 'Approving attendance (HR)',
        onDone: function (result) {
            RefreshHrAttendanceApproval(undefined, selected[0]);
            attendanceHrFilterSnapshot = null;
            var message = result.success + ' record(s) approved by HR. ' + result.failed + ' record(s) could not be updated.';
            if (result.success <= 0) {
                TechXAlert(message);
            } else {
                showHrAttendanceActionToast(message);
            }
            updateAttendanceHrSelectionUi();
        }
    });
}

function submitHrAttendanceReject() {
    var mode = document.getElementById('attendance_hr_reject_mode').value;
    var reason = document.getElementById('attendance_hr_reject_reason').value;
    var filterState = attendanceHrFilterSnapshot || captureHrAttendanceFilters();

    if (mode !== 'bulk') {
        var id = document.getElementById('attendance_hr_reject_id').value;
        $.post('action/reject_attendance.php', { RejectionReason: reason, ID: id }, function (data) {
            var response = parseHrAttendanceActionResponse(data);
            if (!response.error) {
                $('#attendance_hr_reject_modal').modal('hide');
                var param = buildHrAttendanceFilterParam(filterState);
                reloadHrAttendanceTableKeepingPosition('#view-attendance-hr-approval-records', 'action/view-attendance-hr-approval-post.php' + param, id, filterState);
                attendanceHrFilterSnapshot = null;
                showHrAttendanceActionToast(response.message || 'Attendance rejected by HR.');
                return;
            }
            TechXAlert(response.message || 'Unable to reject attendance.');
        }, 'json').fail(function () {
            TechXAlert('Unable to reject attendance. Please try again.');
        });
        return;
    }

    var selected = getSelectedAttendanceHrIds();
    if (!selected.length) {
        TechXAlert('Please select at least one attendance record.');
        return;
    }

    $('#attendance_hr_reject_modal').modal('hide');
    $('#btn_attendance_hr_bulk_approve, #btn_attendance_hr_bulk_reject').prop('disabled', true);

    runAttendanceHrBulkInChunks({
        ids: selected,
        url: 'action/hr_bulk_reject_attendance.php',
        title: 'Rejecting attendance (HR)',
        buildPayload: function (chunk) {
            return { IDs: chunk, RejectionReason: reason };
        },
        onDone: function (result) {
            var param = buildHrAttendanceFilterParam(filterState);
            reloadHrAttendanceTableKeepingPosition('#view-attendance-hr-approval-records', 'action/view-attendance-hr-approval-post.php' + param, selected[0], filterState);
            attendanceHrFilterSnapshot = null;
            var message = result.success + ' record(s) rejected by HR. ' + result.failed + ' record(s) could not be updated.';
            if (result.success <= 0) {
                TechXAlert(message);
            } else {
                showHrAttendanceActionToast(message);
            }
            updateAttendanceHrSelectionUi();
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
