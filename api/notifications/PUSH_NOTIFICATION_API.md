# Common Push Notification API (Background Queue)

Standalone APIs for testing in Postman. Does **not** change attendance/leave action files.

## 1) Run SQL once

```sql
-- File: admin/sql/create_push_notification_queue.sql
```

## 2) Queue push (returns immediately)

**POST** `https://techxpertindia.in/api/notifications/queue-push.php`

### Send to one user

```json
{
  "Target": "user",
  "UserID": 542,
  "Title": "Test Notification",
  "Body": "Hello from common queue API",
  "payload": {
    "screen": "dashboard",
    "module": "general"
  }
}
```

### Send to one employee (resolves device like portal)

```json
{
  "Target": "employee",
  "EmployeeID": 7035,
  "Title": "Leave Update",
  "Body": "Your leave was approved",
  "payload": {
    "screen": "dashboard",
    "module": "leave"
  }
}
```

### Broadcast to all active app users (latest device per user)

```json
{
  "Target": "all",
  "Title": "Company Announcement",
  "Body": "System maintenance tonight 10 PM",
  "payload": {
    "screen": "dashboard",
    "module": "announcement"
  }
}
```

**Response example**

```json
{
  "error": false,
  "message": "Notification queued for background delivery",
  "batch_id": "bn_20260617123045_a1b2c3d4",
  "target": "user",
  "queued": 1,
  "worker_dispatched": true,
  "process_url": "https://techxpertindia.in/api/notifications/process-queue.php",
  "status_url": "https://techxpertindia.in/api/notifications/batch-status.php?batch_id=bn_..."
}
```

Set `"DispatchWorker": false` if you only want to queue and process manually via cron.

Set `"SavePortal": false` to skip writing to portal in-app inbox (push queue only).

## 2b) Portal inbox + push together

When `SavePortal` is true (default), the API also stores the notification in `portal_notifications` so it appears in the **portal header bell** for all users or the targeted user. Mobile push is still queued separately.

## 3) Process queue (background worker / cron)

**GET** `https://techxpertindia.in/api/notifications/process-queue.php?secret=techxpert_push_worker_2026&limit=50`

Cron example (every minute):

```bash
* * * * * curl -s "https://techxpertindia.in/api/notifications/process-queue.php?secret=YOUR_SECRET&limit=100" >/dev/null 2>&1
```

Change secret on production:

- Environment variable: `PUSH_QUEUE_WORKER_SECRET`
- Or edit `api/notifications/inc/notification_config.php`

## 4) Check batch status

**GET** `https://techxpertindia.in/api/notifications/batch-status.php?secret=techxpert_push_worker_2026&batch_id=bn_20260617123045_a1b2c3d4`

```json
{
  "error": false,
  "batch_id": "bn_20260617123045_a1b2c3d4",
  "status": "completed",
  "total": 1,
  "pending": 0,
  "sent": 1,
  "failed": 0
}
```

## Notes

- Portal attendance/leave still use existing `push_notification_controller.php` (unchanged).
- This queue uses table `push_notification_queue` (separate from `push_notifications_log`).
- `queue-push.php` triggers worker in background (~400ms fire-and-forget) so Postman/portal is not blocked.
- For large broadcast (`Target: all`), rely on cron `process-queue.php` every minute.
