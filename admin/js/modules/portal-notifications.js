var portalNotificationPollTimer = null;

function portalNotificationsListUrl() {
    if (window.TechXpertPortalNotifications && window.TechXpertPortalNotifications.listUrl) {
        return window.TechXpertPortalNotifications.listUrl;
    }
    return '../notifications/action/get_portal_notifications.php';
}

function portalNotificationsSeenUrl() {
    if (window.TechXpertPortalNotifications && window.TechXpertPortalNotifications.seenUrl) {
        return window.TechXpertPortalNotifications.seenUrl;
    }
    return '../notifications/action/mark_notification_seen.php';
}

function loadPortalHeaderNotifications() {
    var $badge = $('#portal-notification-count');
    var $list = $('#portal-notification-list');
    if ($list.length === 0) {
        return;
    }

    $.getJSON(portalNotificationsListUrl(), function (response) {
        if (!response || response.error) {
            return;
        }

        var count = parseInt(response.count, 10) || 0;
        if (count > 0) {
            $badge.text(count > 99 ? '99+' : count).show();
        } else {
            $badge.hide();
        }

        $list.empty();
        var items = response.notifications || [];
        if (items.length === 0) {
            $list.append('<li class="p-3 text-center text-muted">No notifications</li>');
            return;
        }

        items.forEach(function (item) {
            var unread = (item.userstatus || 'NotSeen') === 'NotSeen';
            var created = item.created_at || '';
            var body = item.body || '';
            if (body.length > 120) {
                body = body.substring(0, 117) + '...';
            }
            var targetBadge = '';
            if (item.target_type === 'all') {
                targetBadge = '<span class="badge badge-info ml-1">All</span>';
            }

            var html = '<li class="' + (unread ? 'unread' : '') + '">'
                + '<a href="#" class="d-flex align-items-center portal-notification-item" data-id="' + item.id + '">'
                + '<span class="d-flex flex-column flex-1 ml-1">'
                + '<span class="name fw-500">' + escapePortalHtml(item.title || 'Notification') + targetBadge + '</span>'
                + '<span class="msg-a fs-sm">' + escapePortalHtml(body) + '</span>'
                + '<span class="fs-nano text-muted mt-1">' + escapePortalHtml(created) + '</span>'
                + '</span></a></li>';
            $list.append(html);
        });
    });
}

function escapePortalHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function markPortalNotificationSeen(notificationId) {
    $.post(portalNotificationsSeenUrl(), {
        notification_id: notificationId
    }, function () {
        loadPortalHeaderNotifications();
    });
}

function initPortalHeaderNotifications() {
    loadPortalHeaderNotifications();

    $(document).on('click', '.portal-notification-item', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (id) {
            markPortalNotificationSeen(id);
        }
    });

    if (portalNotificationPollTimer) {
        clearInterval(portalNotificationPollTimer);
    }
    portalNotificationPollTimer = setInterval(loadPortalHeaderNotifications, 60000);
}

$(document).ready(function () {
    if ($('#portal-notification-list').length) {
        initPortalHeaderNotifications();
    }
});
