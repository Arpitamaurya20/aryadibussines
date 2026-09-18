# Corporate Audit Ticket — Team Training Guide

**Document version:** 1.0  
**Last updated:** 24 June 2026  
**Audience:** Admin users, Branch Account Managers (BAM), Technicians, Mobile app team, Support / QA  
**Production base URL:** `https://techxpertindia.in`

---

## 1. What we built (summary of last ~10 days)

We introduced a **Corporate Audit Ticket** process end-to-end:

| Area | What changed |
|------|----------------|
| **Master setup** | Corporate Audit master → sub audit → checklist (checkpoints) configured in admin |
| **Ticket lifecycle** | Raise audit ticket → BAM assigns technician → technician fills checklist on site → PDF report |
| **Admin portal** | New menu: Audit Tickets (list, raise, details, assign, history, PDF) |
| **Mobile API** | Full REST API under `/api/audit-ticket/` for technician app |
| **Legacy payload** | Same flat JSON format as corporate service reports (`"1":"No"`, `remark_1`, `image_1`, etc.) |
| **Draft save** | Partial checklist + sign-off data saved to DB — survives page refresh / app crash |
| **Images** | Photo per checkpoint + client signature image support |
| **PDF report** | Professional branch audit PDF with checklist table and client sign-off |

---

## 2. End-to-end business process

```
┌──────────────────┐     ┌──────────────────┐     ┌─────────────────────────┐
│ 1. RAISE TICKET  │────▶│ 2. BAM ASSIGNS   │────▶│ 3. TECHNICIAN ON SITE  │
│ Admin / Portal   │     │ Technician       │     │ Fill checklist + photos │
└──────────────────┘     └──────────────────┘     └───────────┬─────────────┘
                                                                │
                    ┌───────────────────────────────────────────┘
                    ▼
         ┌──────────────────────┐     ┌──────────────────────┐
         │ 4. DRAFT SAVE (auto)   │────▶│ 5. FINAL SUBMIT      │
         │ In Progress            │     │ Completed            │
         └──────────────────────┘     └──────────┬───────────┘
                                                 │
                                                 ▼
                                    ┌────────────────────────┐
                                    │ 6. GENERATE PDF REPORT │
                                    └────────────────────────┘
```

### Ticket statuses

| Status | Meaning | Who acts |
|--------|---------|----------|
| **Raised** | Ticket created, waiting for BAM | BAM assigns technician |
| **Assigned** | Technician assigned, not started | Technician opens ticket |
| **In Progress** | Checklist draft / partial work saved | Technician continues on site |
| **Completed** | All checkpoints submitted | Generate PDF, close if needed |
| **Closed** | Ticket closed | Admin / manager |

### Roles

| Role | Admin portal | Mobile API |
|------|--------------|------------|
| **Admin / Ticket Manager** | Full access — raise, view all, assign | `create_audit_ticket.php` |
| **Branch Account Manager (BAM)** | View branch tickets, assign / reassign technician | `get_audit_tickets.php?view_role=manager`, `assign_audit_technician.php` |
| **Technician** | View assigned tickets (if portal access) | `get_audit_tickets.php`, `submit_audit_checklist.php` |
| **State Corporate Lead** | View tickets in mapped state(s) | `get_audit_tickets.php?view_role=state_manager` |

### Ticket ID format

- **Display ID:** `CS-AUD-000001` (shown to users)
- **Internal ID:** `1, 2, 3…` in table `corporate_audit_tickets.ID` (used in mobile API as `AuditTicketID`)

---

## 3. Admin portal — how to use

**Menu path:** Corporate Audit → Audit Tickets

| Screen | Path | Purpose |
|--------|------|---------|
| Ticket list | `admin/audit-ticket/view-audit-tickets` | Filter by status, company, branch |
| Raise ticket | `admin/audit-ticket/view-raise-audit-ticket` | Select company, branch, master audit, sub audit |
| Ticket details | `admin/audit-ticket/view-audit-ticket-details` | View checklist responses, assign technician, history, PDF |

### Before raising a ticket

Ensure master data exists under **Corporate Audit**:

1. **Master Audit** (e.g. Electrical Audit)
2. **Sub Audit** (e.g. Power Supply System)
3. **Checklist** (checkpoints with field types: number, text, checkbox, etc.)

### BAM assignment flow (portal)

1. Open ticket with status **Raised**
2. Click **Assign Technician**
3. Select technician → status becomes **Assigned**
4. Technician receives ticket in mobile app

---

## 4. Mobile app — API flow for technicians

**Base URL:** `https://techxpertindia.in/api/audit-ticket/`

### Step-by-step

| Step | API | When to call |
|------|-----|--------------|
| 1 | `get_audit_tickets.php` | App home — list my tickets |
| 2 | `get_audit_ticket_detail.php` | Open ticket — load form + **restore draft** |
| 3 | `submit_audit_checklist.php` | Auto-save while filling (`mark_completed: 0`) |
| 4 | `submit_audit_checklist.php` | Final submit (`mark_completed: 1`) |
| 5 | `save_audit_report_details.php` | Optional — if sign-off saved separately |
| 6 | `generate_audit_branch_report.php` | Download / share PDF |

### List my tickets

```http
POST /api/audit-ticket/get_audit_tickets.php
Content-Type: application/json

{
  "EmployeeID": 154,
  "Status": "Assigned"
}
```

### Open ticket (restore saved data after refresh)

```http
POST /api/audit-ticket/get_audit_ticket_detail.php

{
  "AuditTicketID": 1
}
```

**Important response fields:**

| Field | Use in app |
|-------|------------|
| `data.mobile_legacy_payload` | Bind directly to form — same keys as submit payload |
| `data.mobile_checklist_form.fields[]` | Dynamic form per checkpoint |
| `data.report_meta` | Client rep, observation, floor, etc. |
| `data.checklist_summary` | Progress bar (filled / total) |

---

## 5. Submit payload — legacy format (same as service reports)

Technicians can use the **same JSON structure** as corporate PPM / service report checklists.

### Checkpoint keys (by sequence number)

Checkpoints are numbered **1, 2, 3…** in **sort order** of the sub audit checklist (not database ID).

| Key | Example | Maps to |
|-----|---------|---------|
| `"1"`, `"2"` | `"No"` / `"Yes"` | OK status → `Not OK` / `OK` |
| `value_1`, `1_value`, `val_1` | `"228"` | Measured / observed value |
| `remark_1`, `1_remark` | `"Below range"` | Checkpoint remarks |
| `image_1`, `1_image` | base64 or filename | Checkpoint photo |

### Sign-off / report keys (saved to ticket table)

| Key | Stored as |
|-----|-----------|
| `ProblemReportedByClient` | Problem reported by client |
| `Observation` | Audit observation |
| `ActionTaken` | Action taken on site |
| `Remarks` | General remarks |
| `ClientRepresentative` | Client name |
| `ClientRepresentativeContact` | Client phone |
| `ClientRepresentativeEmails` | Client email |
| `ClientRepresentativeDesignation` | Client designation |
| `FloorName` | Report floor |
| `ClientSignature` | Signature image (base64) |
| `Latitude`, `Longitude` | GPS at site |

### Ticket ID keys (any one works)

| Key | Notes |
|-----|-------|
| `AuditTicketID` | Preferred — internal ID (`1`, `2`, …) |
| `ServiceReportID` | Legacy alias for `AuditTicketID` |
| `ServiceReportTicketID` | Same as above |
| `TicketID` | Display ID `CS-AUD-000001` or numeric ID |

### Nested objects (optional)

`userdata` and `userdetails` are **auto-merged** into the main payload. You can send data at root level or inside these objects.

---

## 6. Draft save vs final submit

### Draft save (while technician is still on site)

```json
{
  "AuditTicketID": 1,
  "EmployeeID": 154,
  "CreatedBy": "admin",
  "mark_completed": 0,
  "save_draft": 1,

  "1": "No",
  "2": "Yes",
  "3": "No",

  "ProblemReportedByClient": "Voltage fluctuation reported",
  "Observation": "Checked main panel",
  "ClientRepresentative": "Mr. Rajesh"
}
```

**Draft rules:**

- Partial data is OK — save after each step or every few seconds
- `"No"` without `remark_N` is allowed in draft
- Status stays **In Progress**
- Data is written to database immediately

**Expected response:**

```json
{
  "error": false,
  "saved_count": 3,
  "checklist_saved_count": 3,
  "report_saved": 1,
  "is_draft": 1,
  "Status": "In Progress"
}
```

### Final submit (complete audit)

```json
{
  "AuditTicketID": 1,
  "mark_completed": 1,
  "EmployeeID": 154,

  "1": "No",
  "remark_1": "Incoming supply unstable — needs correction",
  "2": "Yes",
  ...
}
```

**Final submit rules:**

- **All** checkpoints must be filled
- If status is **Not OK** (`"No"`), `remark_N` is **required**
- Status becomes **Completed**

---

## 7. Where data is stored (database)

This is the most common support question.

### Table 1: `corporate_audit_tickets`

**One row per audit ticket.** Stores ticket header + sign-off / report fields.

| Column examples | From payload |
|-----------------|--------------|
| `TicketID` | `CS-AUD-000001` |
| `Status` | Raised → Assigned → In Progress → Completed |
| `ProblemReportedByClient` | `ProblemReportedByClient` |
| `AuditObservation` | `Observation` |
| `ActionTaken` | `ActionTaken` |
| `GeneralRemarks` | `Remarks` |
| `ClientRepresentative*` | Client sign-off block |
| `ReportFloor` | `FloorName` |
| `ClientSignature` | Signature filename |
| `DraftSavedDate` / `DraftSavedTime` | Last draft save timestamp |

**Check in phpMyAdmin:**

```sql
SELECT ID, TicketID, Status, ProblemReportedByClient, AuditObservation,
       ClientRepresentative, ReportFloor, DraftSavedDate
FROM corporate_audit_tickets
WHERE ID = 1;
```

### Table 2: `corporate_audit_ticket_responses`

**One row per checklist checkpoint per ticket.** Stores technician answers.

| Column | From payload |
|--------|--------------|
| `AuditTicketID` | Ticket internal ID |
| `ChecklistID` | Master checklist item ID |
| `ResponseValue` | `value_N` or `"N"` value (`Yes`/`No`) |
| `OkStatus` | `OK` / `Not OK` |
| `Remarks` | `remark_N` |
| `ResponseImage` | `image_N` (saved filename) |

**Check in phpMyAdmin:**

```sql
SELECT AuditTicketID, ChecklistID, ResponseValue, OkStatus, Remarks, ResponseImage
FROM corporate_audit_ticket_responses
WHERE AuditTicketID = 1;
```

### Table 3: `corporate_audit_ticket_status_history`

Assignment and status change timeline (audit trail).

### Image file locations

| Type | Server path |
|------|-------------|
| Checkpoint photos | `admin/media/audit-ticket/checklist/` |
| Client signature | `admin/media/audit-ticket/signatures/` |
| PDF reports | `admin/audit-ticket/reports/` |

---

## 8. All mobile API endpoints

| # | Endpoint | Purpose |
|---|----------|---------|
| 1 | `get_audit_tickets.php` | List tickets by employee / role |
| 2 | `get_audit_ticket_detail.php` | Full ticket + checklist + draft restore |
| 3 | `get_audit_checklist_form.php` | Checklist only (lightweight) |
| 4 | `submit_audit_checklist.php` | Save draft or final checklist + sign-off |
| 5 | `save_audit_report_details.php` | Save report fields only |
| 6 | `generate_audit_branch_report.php` | Generate PDF |
| 7 | `assign_audit_technician.php` | BAM assign / reassign |
| 8 | `get_audit_ticket_history.php` | Status timeline |
| 9 | `create_audit_ticket.php` | Raise ticket from app / integration |

**Technical reference:** `api/audit-ticket/MOBILE_AUDIT_TICKET_API.md`

**Master audit config (before raising tickets):** `api/audit/` — browse audits, sub audits, checklist templates.

---

## 9. Production deployment checklist

### SQL migrations (run once, in order)

Only run scripts not already applied:

1. `alter_audit_ticket_add_history.sql`
2. `alter_audit_ticket_bam_workflow.sql`
3. `alter_audit_ticket_checklist_ok_status.sql`
4. `alter_audit_ticket_report_meta.sql`
5. `alter_audit_ticket_ticketid_default.sql`
6. `alter_audit_ticket_mobile_draft.sql` ← images + draft + sign-off columns

### Writable folders

```
admin/media/audit-ticket/checklist/
admin/media/audit-ticket/signatures/
admin/audit-ticket/reports/
```

### Latest code files (draft + legacy payload fix)

```
api/audit-ticket/audit_ticket_helpers.php
admin/audit-ticket/controller/audit_ticket_controller.php
```

After upload: restart Apache / clear PHP opcache if enabled.

---

## 10. Troubleshooting FAQ

### Q: API says "draft saved" but `corporate_audit_ticket_responses` is empty?

**A:** Check `saved_count` / `checklist_saved_count` in the response.

| Response | Meaning |
|----------|---------|
| `saved_count: 0`, `report_saved: 1` | Only sign-off fields saved — checklist keys (`"1"`, `"2"`, …) were missing or empty |
| `draft_warnings` with remark errors | Old code on server — re-upload latest `audit_ticket_helpers.php` |
| `saved_count: 6` | Checklist saved correctly — query `corporate_audit_ticket_responses` |

### Q: Report fields saved but checklist empty?

**A:** Report goes to `corporate_audit_tickets`. Checklist goes to `corporate_audit_ticket_responses`. They are **two different tables**.

### Q: What is `AuditTicketID` vs `CS-AUD-000001`?

**A:**  
- `AuditTicketID` = internal numeric ID (`1`) — use this in mobile API  
- `CS-AUD-000001` = display ticket ID — use in `TicketID` field

### Q: Page refresh loses data?

**A:** Call `get_audit_ticket_detail.php` and bind `data.mobile_legacy_payload` to the form. Data is server-side, not only in app memory.

### Q: Images not saving?

**A:**  
1. Confirm `alter_audit_ticket_mobile_draft.sql` was run (`ResponseImage` column exists)  
2. Confirm folder `admin/media/audit-ticket/checklist/` is writable  
3. Send base64: `"image_1": "data:image/jpeg;base64,..."`

### Q: Final submit fails?

**A:** Ensure all 6 checkpoints are filled. For any `"No"` / Not OK, `remark_N` is required on final submit.

---

## 11. Training session agenda (suggested 60 min)

| Time | Topic | Demo |
|------|-------|------|
| 10 min | Overview — why audit tickets, who is involved | Process diagram |
| 10 min | Admin — master audit setup + raise ticket | Portal walkthrough |
| 10 min | BAM — assign technician | Assign from ticket details |
| 15 min | Mobile — open ticket, draft save, final submit | Postman / app demo |
| 10 min | Database — two tables, where to verify | phpMyAdmin |
| 5 min | PDF generation | Generate from portal or API |

---

## 12. Quick reference card (print / share)

```
RAISE     → Admin portal → Raise Audit Ticket
ASSIGN    → BAM → Ticket details → Assign Technician
WORK      → Technician app → submit_audit_checklist.php (draft)
COMPLETE  → submit_audit_checklist.php (mark_completed: 1)
RESTORE   → get_audit_ticket_detail.php → mobile_legacy_payload
REPORT    → generate_audit_branch_report.php

CHECKLIST DATA  → corporate_audit_ticket_responses
SIGN-OFF DATA   → corporate_audit_tickets
```

---

## 13. Contacts & references

| Resource | Location |
|----------|----------|
| Mobile API full docs | `api/audit-ticket/MOBILE_AUDIT_TICKET_API.md` |
| Corporate audit master API | `api/audit/MOBILE_CORPORATE_AUDIT_API.md` |
| SQL scripts | `admin/audit-ticket/sql/` |
| This training guide | `admin/audit-ticket/docs/TEAM_TRAINING_AUDIT_PROCESS.md` |

---

*For technical support, include in every ticket: `AuditTicketID`, payload JSON, API response JSON, and screenshot of `corporate_audit_tickets` + `corporate_audit_ticket_responses` for that ID.*
