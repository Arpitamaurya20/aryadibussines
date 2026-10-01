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

function buildHrAttendanceFilterParam(filterDateValue) {
    var EmployeeID = document.getElementById('employee_name').value;
    var ApprovalStatus = document.getElementById('approval_status').value;
    var filter_date = filterDateValue !== undefined ? filterDateValue : getHrAttendanceFilterDateValue();
    return '?filter_date=' + encodeURIComponent(filter_date) + '&EmployeeID=' + EmployeeID + '&ApprovalStatus=' + ApprovalStatus;
}

function RefreshHrAttendanceApproval(filterDateValue) {
    var selector = '#view-attendance-hr-approval-records';
    var param = buildHrAttendanceFilterParam(filterDateValue);
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.url('action/view-attendance-hr-approval-post.php' + param).load(null, false);
    } else {
        initHrAttendanceApprovalTable(param);
    }
}

function setHrAttendanceQuickFilter(type) {
    var today = new Date();
    var start = new Date();
    var filterDate = '';
    var statusEl = document.getElementById('approval_status');

    if (type === 'pending_hr') {
        statusEl.value = 'SupervisorApproved';
        filterDate = formatDateYMD(new Date(today.getTime() - (365 * 24 * 60 * 60 * 1000))) + ' - ' + formatDateYMD(today);
    } else if (type === '7days') {
        statusEl.value = '-1';
        start.setDate(today.getDate() - 7);
        filterDate = formatDateYMD(start) + ' - ' + formatDateYMD(today);
    } else if (type === '365days') {
        statusEl.value = '-1';
        start.setDate(today.getDate() - 365);
        filterDate = formatDateYMD(start) + ' - ' + formatDateYMD(today);
    } else if (type === 'all') {
        statusEl.value = '-1';
        filterDate = 'all';
    }

    document.getElementById('filter_date').val(filterDate === 'all' ? 'All Time' : filterDate);
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

function initHrAttendanceApprovalTable(param) {
    $('#view-attendance-hr-approval-records').dataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: { url: 'action/view-attendance-hr-approval-post.php' + param },
        columns: [
            { data: 'EmployeeName' },
            { data: 'EmployeeNumber' },
            { data: 'RecordDate' },
            { data: 'CheckInTime' },
            { data: 'CheckOutTime' },
            { data: 'Duration' },
            { data: 'ApprovalStatus' },
            { data: 'ApprovalInfo' },
            { data: 'Actions' },
            { data: 'State' }
        ]
    });
}

function HrApproveAttendance(id) {
    alertify.confirm('TechXpert', 'Give HR final approval for this attendance?', function () {
        $.post('action/approve_attendance.php', { ID: id }, function (data) {
            var response = parseHrAttendanceActionResponse(data);
            if (!response.error) {
                RefreshHrAttendanceApproval();
            }
            TechXAlert(response.message);
        }, 'json').fail(function () {
            TechXAlert('Unable to complete HR approval. Please try again.');
        });
    }, function () {
        alertify.error('Approval cancelled');
    });
}

function HrRejectAttendance(id) {
    alertify.prompt('TechXpert', 'Reason for HR rejection (optional):', '', function (_evt, value) {
        $.post('action/reject_attendance.php', { ID: id, RejectionReason: value }, function (data) {
            var response = parseHrAttendanceActionResponse(data);
            if (!response.error) {
                RefreshHrAttendanceApproval();
            }
            TechXAlert(response.message);
        }, 'json').fail(function () {
            TechXAlert('Unable to reject attendance. Please try again.');
        });
    }, function () {
        alertify.error('Rejection cancelled');
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
