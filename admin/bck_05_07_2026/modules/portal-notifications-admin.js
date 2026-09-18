function toggleNotificationTargetFields() {
    var target = $('#notify_target').val() || 'all';
    $('#notify_user_wrap').toggle(target === 'user');
    $('#notify_employee_wrap').toggle(target === 'employee');
}

function sendPortalNotification() {
    var target = $('#notify_target').val() || 'all';
    var payload = {
        Target: target,
        Title: $('#notify_title').val(),
        Body: $('#notify_body').val(),
        Screen: $('#notify_screen').val(),
        Module: $('#notify_module').val(),
        SendPush: $('#notify_send_push').is(':checked') ? '1' : '0'
    };

    if (target === 'user') {
        payload.UserID = $('#notify_user_id').val();
        if (!payload.UserID) {
            TechXAlert('Please select a portal user');
            return false;
        }
    }
    if (target === 'employee') {
        payload.EmployeeID = $('#notify_employee_id').val();
        if (!payload.EmployeeID) {
            TechXAlert('Please select an employee');
            return false;
        }
    }

    $.post('action/send_notification.php', payload, function (data) {
        var response = (typeof data === 'object') ? data : JSON.parse(data);
        TechXAlert(response.message);
        if (!response.error) {
            setTimeout(function () { location.reload(); }, 1500);
        }
    }, 'json').fail(function () {
        TechXAlert('Unable to send notification');
    });

    return false;
}
