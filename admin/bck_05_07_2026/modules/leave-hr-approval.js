function applyLeaveHrFilters() {
    var employeeId = $('#leave_employee_filter').val() || '-1';
    var status = $('#leave_status_filter').val() || 'SupervisorApproved';
    var params = new URLSearchParams();
    params.set('employee_id', employeeId);
    params.set('status', status);
    window.location.href = 'view-leave-hr-approval.php?' + params.toString();
}

function HrApproveLeave(leaveId) {
    alertify.confirm('TechXpert', 'Give HR final approval for this leave request?', function () {
        $.post('../employees/action/approve_employee_leave.php', { ID: leaveId }, function (data) {
            var response = JSON.parse(data);
            TechXAlert(response.message);
            if (response.error === false) {
                setTimeout(function () {
                    location.reload();
                }, 1500);
            }
        });
    }, function () {
        alertify.error('Approval cancelled');
    });
}

function HrRejectLeave(leaveId) {
    alertify.prompt('TechXpert', 'Rejection reason (optional):', '', function (_evt, value) {
        $.post('../employees/action/reject_employee_leave.php', { ID: leaveId, RejectionReason: value || '' }, function (data) {
            var response = JSON.parse(data);
            TechXAlert(response.message);
            if (response.error === false) {
                setTimeout(function () {
                    location.reload();
                }, 1500);
            }
        });
    }, function () {
        alertify.error('Rejection cancelled');
    });
}
