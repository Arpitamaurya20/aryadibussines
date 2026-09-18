# Home Care Raise Ticket module

Self-contained admin module. Does not modify navigation or other features until you wire them in.

## Setup

1. Run the SQL once on database `techxpertindia`:

   `admin/hc-tickets/sql/install_hc_tickets.sql`  
   If the table already exists with a text `state` column, also run:  
   `admin/hc-tickets/sql/alter_hc_ticket_state_id.sql`

2. Open in browser (after login):

   `/admin/hc-tickets/view-hc-tickets`

3. Ticket detail URLs use **no `.php`** and **Base64-encoded id**:

   `/admin/hc-tickets/view-hc-ticket-detail?t=MQ` (example for ticket id 1)

## Ticket code

Format: `TX-HC-000001` (zero-padded numeric id).

## Fields

- **Who is asking:** name*, address, phone, email, city, state (searchable dropdown — stores `state.ID`, shows `StateName`), location, landmark, postal code
- **Timeline:** immediate or custom (custom requires date/time)
- **Budget:** optional
- **Purpose:** rent or self — shows contact name, phone, address (required)
- **Description:** required
- **System:** booking_date, booking_time, status (`new`), created_by (session `pb_username`), created_at, updated_at

## Ticket detail, assignment & history

- List → **View** opens `view-hc-ticket-detail?t={base64_id}` (URL-safe Base64 of numeric id)
- Tabs: **Details**, **Assignment & Status**, **History**
- Technicians: active `employees` where `IsActive = 1` (searchable Select2)
- **Reassign only**: in Edit modal, choose *Reassign only (keep current status)* — changes technician, status stays the same, logs a `reassigned` history row
- Every action is stored in `hc_ticket_history` (raised, assigned, reassigned, status_changed, due_date_updated, remarks_updated)

### SQL (run once if needed)

- `sql/alter_hc_ticket_assignment.sql` — assignment columns on `hc_ticket`
- `sql/install_hc_ticket_history.sql` — history table

## Add to admin menu (optional)

In `admin/navigation/admin_navigation.php` and `common_controllers.php`, add a nav flag and link to `hc-tickets/view-hc-tickets` when you are ready.
