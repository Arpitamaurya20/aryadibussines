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
    $.post('action/supervisor_bulk_approve_attendance.php', { IDs: selected }, function (data) {
        var response = parseAttendanceActionResponse(data);
        if (!response.error) {
            RefreshAttendanceApproval(undefined, selected[0]);
            attendanceApprovalFilterSnapshot = null;
            showAttendanceActionToast(response.message);
            return;
        }
        TechXAlert(response.message);
    }, 'json').fail(function () {
        TechXAlert('Unable to approve selected records.');
    });
}

function submitAttendanceReject() {
    var mode = document.getElementById('attendance_reject_mode').value;
    var reason = document.getElementById('attendance_reject_reason').value;
    var filterState = attendanceApprovalFilterSnapshot || captureAttendanceApprovalFilters();
    var url = 'action/reject_attendance.php';
    var payload = { RejectionReason: reason };

    if (mode === 'bulk') {
        payload.IDs = getSelectedAttendanceSupervisorIds();
        url = 'action/supervisor_bulk_reject_attendance.php';
        if (!payload.IDs.length) {
            TechXAlert('Please select at least one attendance record.');
            return;
        }
    } else {
        payload.ID = document.getElementById('attendance_reject_id').value;
    }

    $.post(url, payload, function (data) {
        var response = parseAttendanceActionResponse(data);
        if (!response.error) {
            $('#attendance_reject_modal').modal('hide');
            var afterId = mode === 'bulk' ? payload.IDs[0] : payload.ID;
            var filterDateVal = filterState.filter_date === 'All Time' ? 'all' : filterState.filter_date;
            var param = buildAttendanceFilterParam(filterState);
            reloadAttendanceTableKeepingPosition('#view-attendance-approval-records', 'action/view-attendance-approval-post.php' + param, afterId, filterState);
            attendanceApprovalFilterSnapshot = null;
            showAttendanceActionToast(response.message || 'Attendance rejected.');
            return;
        }
        TechXAlert(response.message || 'Unable to reject attendance.');
    }, 'json').fail(function () {
        TechXAlert('Unable to reject attendance. Please try again.');
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
