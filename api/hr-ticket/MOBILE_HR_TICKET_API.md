# HR Ticket — Mobile API

Base URL:
- Production: `https://techxpertindia.in/api/hr-ticket/`
- Local: `http://localhost/projects/techxpert/api/hr-ticket/`

All endpoints return JSON. Supports **POST** (JSON body), **GET** (query params), or both.

Standard response:
```json
{
  "error": false,
  "message": "...",
  "total_records": 1,
  "data": []
}
```

---

## End-to-end flows

### Employee
```
1. get_hr_tickets.php          (view_role=employee)
2. create_hr_ticket.php        (raise ticket + optional attachment)
3. get_hr_ticket_detail.php    (track status, comments, attachments)
4. add_hr_ticket_comment.php   (reply when HR asks)
5. upload_hr_ticket_attachment.php
6. close_hr_ticket.php         (when status = resolved)
```

### HR
```
1. get_hr_tickets.php          (view_role=hr)
2. get_hr_ticket_detail.php
3. update_hr_ticket.php        (assign + change status)
4. add_hr_ticket_comment.php   (reply to employee; IsInternal=1 for HR-only notes)
5. upload_hr_ticket_attachment.php
6. get_hr_ticket_history.php
```

---

## All endpoints

| # | File | Purpose |
|---|------|---------|
| 1 | `get_hr_tickets.php` | List tickets (employee or HR) |
| 2 | `get_hr_ticket_detail.php` | Full ticket + comments + attachments + history |
| 3 | `create_hr_ticket.php` | Employee raises ticket |
| 4 | `add_hr_ticket_comment.php` | Add comment (employee or HR) |
| 5 | `upload_hr_ticket_attachment.php` | Upload base64 or multipart file |
| 6 | `update_hr_ticket.php` | HR assign / update status |
| 7 | `close_hr_ticket.php` | Employee closes resolved ticket |
| 8 | `get_hr_ticket_history.php` | Audit timeline |
| 9 | `get_hr_ticket_meta.php` | Categories, statuses, priorities |

---

## 1. get_hr_tickets.php

### Employee — my tickets
```http
POST /api/hr-ticket/get_hr_tickets.php
```
```json
{
  "EmployeeID": 205,
  "Status": "open",
  "view_role": "employee"
}
```

### HR — all tickets
```json
{
  "EmployeeID": 101,
  "view_role": "hr",
  "Status": "open",
  "Category": "payment_related",
  "AssignedTo": 101
}
```

| Key | Description |
|-----|-------------|
| `EmployeeID` | Required — caller's employee ID |
| `view_role` | `employee` (default) or `hr` |
| `Status` | Filter: open, in_progress, pending_employee_response, on_hold, resolved, closed |
| `Category` | general, payment_related, benefits |
| `AssignedTo` | HR filter — tickets assigned to this employee |

---

## 2. get_hr_ticket_detail.php

```json
{
  "EmployeeID": 205,
  "HrTicketID": 1
}
```
Or: `{ "TicketID": "TX-HR-000001" }`

Response includes `comments`, `attachments`, `history`, `permissions`, and `meta`.

---

## 3. create_hr_ticket.php

```json
{
  "EmployeeID": 205,
  "Category": "payment_related",
  "Subject": "March salary not credited",
  "Description": "Salary for March 2026 is not reflected in bank account.",
  "PaymentReference": "March 2026",
  "Priority": "high",
  "Attachment": "data:image/jpeg;base64,...",
  "AttachmentName": "bank_statement.jpg"
}
```

| Key | Required | Notes |
|-----|----------|-------|
| `EmployeeID` | Yes | Ticket owner |
| `Subject` | Yes | |
| `Description` | Yes | |
| `Category` | No | general, payment_related, benefits (default: general) |
| `PaymentReference` | Yes if payment_related | Salary month / payslip ref |
| `Priority` | No | low, normal, high (payment_related defaults to high) |
| `Attachment` | No | Base64 image/document |

**Notifications:** All HR users notified on create.

---

## 4. add_hr_ticket_comment.php

### Employee reply
```json
{
  "EmployeeID": 205,
  "HrTicketID": 1,
  "CommentText": "Please find the details attached."
}
```

### HR reply
```json
{
  "EmployeeID": 101,
  "HrTicketID": 1,
  "CommentText": "Your payslip has been released. Please check.",
  "IsInternal": 0
}
```

### HR internal note (hidden from employee)
```json
{
  "EmployeeID": 101,
  "HrTicketID": 1,
  "CommentText": "Escalate to payroll team.",
  "IsInternal": 1
}
```

Optional `Attachment` + `AttachmentName` in same request.

**Notifications:**
- HR comment → employee
- Employee comment → assigned HR (or all HR if unassigned)

---

## 5. upload_hr_ticket_attachment.php

### Base64 (recommended for mobile)
```json
{
  "EmployeeID": 205,
  "HrTicketID": 1,
  "Attachment": "data:application/pdf;base64,...",
  "AttachmentName": "payslip.pdf",
  "CommentID": 0
}
```

Max size: 10 MB. Allowed: pdf, jpg, png, gif, webp, doc, docx, xls, xlsx, txt.

---

## 6. update_hr_ticket.php — HR only

### Assign and start work
```json
{
  "EmployeeID": 101,
  "HrTicketID": 1,
  "AssignedTo": 101,
  "Status": "in_progress"
}
```

### Request employee input
```json
{
  "EmployeeID": 101,
  "HrTicketID": 1,
  "Status": "pending_employee_response"
}
```

### Mark resolved
```json
{
  "EmployeeID": 101,
  "HrTicketID": 1,
  "Status": "resolved"
}
```

**Notifications:** Employee notified on status change; assignee notified on assignment.

---

## 7. close_hr_ticket.php — Employee only

```json
{
  "EmployeeID": 205,
  "HrTicketID": 1
}
```

Only works when ticket status is `resolved`.

---

## 8. get_hr_ticket_history.php

```json
{
  "EmployeeID": 205,
  "HrTicketID": 1
}
```

---

## 9. get_hr_ticket_meta.php

No body required. Returns categories, statuses, priorities, and push payload keys.

---

## Push notification payload

Mobile app should handle `portal_notifications` / push payload:

```json
{
  "module": "hr_ticket",
  "screen": "hr_ticket",
  "ticket_id": 1,
  "ticket_code": "TX-HR-000001"
}
```

| screen | Who | Action |
|--------|-----|--------|
| `hr_ticket` | Employee | Open my ticket detail |
| `hr_ticket_manage` | HR | Open HR management detail |

---

## Database setup

Run once: `admin/hr-tickets/sql/install_hr_tickets.sql`

---

## Quick copy — employee session

```bash
# 1. My tickets
POST get_hr_tickets.php  { "EmployeeID": 205 }

# 2. Raise ticket
POST create_hr_ticket.php  { "EmployeeID": 205, "Category": "general", "Subject": "...", "Description": "..." }

# 3. Open ticket
POST get_hr_ticket_detail.php  { "EmployeeID": 205, "HrTicketID": 1 }

# 4. Reply
POST add_hr_ticket_comment.php  { "EmployeeID": 205, "HrTicketID": 1, "CommentText": "..." }

# 5. Close when resolved
POST close_hr_ticket.php  { "EmployeeID": 205, "HrTicketID": 1 }
```

## Quick copy — HR session

```bash
# 1. All tickets
POST get_hr_tickets.php  { "EmployeeID": 101, "view_role": "hr" }

# 2. Assign & update
POST update_hr_ticket.php  { "EmployeeID": 101, "HrTicketID": 1, "AssignedTo": 101, "Status": "in_progress" }

# 3. Reply
POST add_hr_ticket_comment.php  { "EmployeeID": 101, "HrTicketID": 1, "CommentText": "..." }
```
