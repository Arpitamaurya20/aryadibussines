function applyLeaveSupervisorFilters() {
    var employeeId = $('#leave_employee_filter').val() || '-1';
    var status = $('#leave_status_filter').val() || 'Pending';
    var params = new URLSearchParams();
    params.set('employee_id', employeeId);
    params.set('status', status);
    window.location.href = 'view-leave-approval.php?' + params.toString();
}

function parseLeaveActionResponse(data) {
    if (typeof data === 'object' && data !== null) {
        return data;
    }
    try {
        return JSON.parse(data);
    } catch (e) {
        return { error: true, message: 'Unexpected server response. Please try again.' };
    }
}

function SupervisorApproveLeave(leaveId) {
    alertify.confirm('TechXpert', 'Approve this leave and send to HR for final approval?', function () {
        $.post('action/supervisor_approve_leave.php', { ID: leaveId }, function (data) {
            var response = parseLeaveActionResponse(data);
            TechXAlert(response.message);
            if (response.error === false) {
                setTimeout(function () {
                    location.reload();
                }, 1500);
            }
        }, 'json').fail(function () {
            TechXAlert('Unable to approve leave. Please try again.');
        });
    }, function () {
        alertify.error('Approval cancelled');
    });
}

function SupervisorRejectLeave(leaveId) {
    alertify.prompt('TechXpert', 'Rejection reason (optional):', '', function (_evt, value) {
        $.post('action/supervisor_reject_leave.php', { ID: leaveId, RejectionReason: value || '' }, function (data) {
            var response = parseLeaveActionResponse(data);
            TechXAlert(response.message);
            if (response.error === false) {
                setTimeout(function () {
                    location.reload();
                }, 1500);
            }
        }, 'json').fail(function () {
            TechXAlert('Unable to reject leave. Please try again.');
        });
    }, function () {
        alertify.error('Rejection cancelled');
    });
}
