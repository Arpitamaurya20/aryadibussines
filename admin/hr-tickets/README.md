# HR Ticket Module

Self-contained module for employee HR requests and HR team management.

## Setup

1. Run SQL once on database `techxpertindia`:

   `admin/hr-tickets/sql/install_hr_tickets.sql`

2. After login:

   - **Employees:** `/admin/hr-tickets/view-my-hr-tickets`
   - **HR / Admin:** `/admin/hr-tickets/view-hr-tickets`

3. Detail URLs use Base64-encoded id: `/admin/hr-tickets/view-hr-ticket-detail?t=MQ`

## Ticket code

Format: `TX-HR-000001`

## Categories

- **General** — everyday HR queries
- **Payment Related** — salary, reimbursement, payslip (payment reference required; auto high priority)
- **Benefits** — insurance, perks, policy questions

## Status workflow

| Status | Meaning |
|--------|---------|
| Open | New ticket, awaiting HR |
| In Progress | Assigned and being worked |
| Pending Employee Response | HR needs employee input |
| On Hold | Paused / waiting externally |
| Resolved | HR marked complete; employee may close |
| Closed | Final state |

## Notifications

Uses `pnc_publishNotification()` (portal inbox + mobile push):

- New ticket → all HR role users
- Status change / assignment → employee
- HR comment → employee
- Employee comment / attachment → assigned HR (or all HR if unassigned)

## Files

- Domain class: `admin/classes/hrticket.class.php`
- Uploads: `admin/hr-tickets/uploads/` (gitignored except `.gitkeep`)

No existing action files in other modules were modified.

## Mobile API

See `api/hr-ticket/MOBILE_HR_TICKET_API.md` for employee and HR mobile endpoints with push notifications.
