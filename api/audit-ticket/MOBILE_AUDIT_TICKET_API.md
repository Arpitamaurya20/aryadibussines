# Corporate Audit Ticket — Mobile API (Complete Guide)

Base URL:
- Production: `https://techxpertindia.in/api/audit-ticket/`
- Local: `http://localhost/projects/techxpert/api/audit-ticket/`

All endpoints return JSON unless noted. Supports **POST** (JSON body), **GET** (query params), or both.

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

## End-to-end mobile flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│ 1. LIST TICKETS BY EMPLOYEE ID                                          │
│    get_audit_tickets.php  (technician / BAM / state manager)            │
└───────────────────────────────┬─────────────────────────────────────────┘
                                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ 2. OPEN TICKET — master audit, sub audit, branch address                │
│    get_audit_ticket_detail.php                                          │
└───────────────────────────────┬─────────────────────────────────────────┘
                                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ 3. LOAD CHECKLIST FORM (lightweight option)                             │
│    get_audit_checklist_form.php                                         │
└───────────────────────────────┬─────────────────────────────────────────┘
                                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ 4. SUBMIT CHECKLIST (value + OK/Not OK + remarks per checkpoint)        │
│    submit_audit_checklist.php                                           │
└───────────────────────────────┬─────────────────────────────────────────┘
                                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ 5. SAVE REPORT DETAILS (floor, address, client rep, observation)        │
│    save_audit_report_details.php                                        │
└───────────────────────────────┬─────────────────────────────────────────┘
                                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ 6. GENERATE PROFESSIONAL BRANCH PDF REPORT                              │
│    generate_audit_branch_report.php                                     │
└─────────────────────────────────────────────────────────────────────────┘
```

**BAM workflow (before technician):**
- `get_audit_tickets.php?view_role=manager` → `assign_audit_technician.php`

---

## All endpoints

| # | File | Purpose |
|---|------|---------|
| 1 | `get_audit_tickets.php` | List tickets by Employee ID (technician / manager / state) |
| 2 | `get_audit_ticket_detail.php` | Full ticket + sub audit + branch + checklist + history |
| 3 | `get_audit_checklist_form.php` | Checklist form only (lightweight) |
| 4 | `submit_audit_checklist.php` | Save checklist responses |
| 5 | `save_audit_report_details.php` | Save floor, address, client rep, observation for PDF |
| 6 | `generate_audit_branch_report.php` | Build & download professional PDF report |
| 7 | `assign_audit_technician.php` | BAM assign / reassign technician |
| 8 | `get_audit_ticket_history.php` | Status & assignment timeline |
| 9 | `create_audit_ticket.php` | Raise new ticket (portal / admin) |

---

## 1. get_audit_tickets.php — List by Employee ID

### Technician — my assigned tickets
```http
POST /api/audit-ticket/get_audit_tickets.php
```
```json
{
  "EmployeeID": 205,
  "Status": "Assigned"
}
```

### Branch Account Manager — tickets in my queue
```json
{
  "EmployeeID": 101,
  "view_role": "manager"
}
```

### State Corporate Lead — tickets in mapped state(s)
```json
{
  "EmployeeID": 55,
  "view_role": "state_manager"
}
```

### Optional filters
| Key | Description |
|-----|-------------|
| `EmployeeID` | Required for role-based filtering |
| `view_role` | `technician` (default), `manager`, `state_manager` |
| `Status` | `Raised`, `Assigned`, `In Progress`, `Completed`, `Closed` |
| `CorporateID` | Filter by company |
| `BranchID` | Filter by branch |

### Sample response
```json
{
  "error": false,
  "message": "Audit tickets fetched.",
  "total_records": 2,
  "data": [
    {
      "id": 1,
      "ticket_id": "CS-AUD-000001",
      "corporate_id": 12,
      "branch_id": 45,
      "master_audit_id": 1,
      "sub_audit_id": 3,
      "technician": 205,
      "branch_account_manager": 101,
      "status": "Assigned",
      "last_status": "Raised",
      "company_name": "ABC Corp",
      "branch_site": "Mumbai Branch",
      "branch_city": "Mumbai",
      "master_audit_name": "Electrical Audit",
      "sub_audit_name": "UPS System",
      "report_generated": 0,
      "branch_details": {
        "branch_site": "Mumbai Branch",
        "address_line_1": "Ground Floor, Shop 12",
        "city": "Mumbai",
        "state": "Maharashtra",
        "postal_code": "400001",
        "floor": "",
        "full_address": "Ground Floor, Shop 12, Mumbai, Maharashtra, 400001",
        "mobile": "9876543210"
      },
      "sub_audit": {
        "master_audit_id": 1,
        "master_audit_name": "Electrical Audit",
        "sub_audit_id": 3,
        "sub_audit_name": "UPS System"
      },
      "report_meta": {
        "report_floor": "",
        "client_representative": "",
        "report_generated": 0
      }
    }
  ]
}
```

---

## 2. get_audit_ticket_detail.php — Ticket + Sub Audit + Checklist

```json
{
  "AuditTicketID": 1
}
```
Or: `{ "TicketID": "CS-AUD-000001" }`

### Key response fields
| Section | Description |
|---------|-------------|
| `branch_details` | Site address, floor, city, state, PIN, mobile, email, GPS |
| `sub_audit` | Master audit + sub audit names & descriptions |
| `mobile_checklist_form.fields[]` | Dynamic form per checkpoint |
| `checklist_summary` | total, filled, ok, not_ok, completion % |
| `status_history` | Assignment & status timeline |
| `report_meta` | Saved report fields for PDF |

### Per-checkpoint mobile form
```json
{
  "checklist_id": 10,
  "value_field": {
    "key": "ResponseValue",
    "label": "Measured / Observed Value",
    "field_meta": {
      "input_type": "number",
      "validation": { "min_value": "210", "max_value": "250", "unit": "V" }
    }
  },
  "ok_status_field": {
    "key": "OkStatus",
    "label": "Status",
    "input_type": "select",
    "options": [
      { "value": "OK", "label": "OK" },
      { "value": "Not OK", "label": "Not OK" }
    ],
    "required": true
  },
  "remarks_field": {
    "key": "Remarks",
    "label": "Remarks",
    "input_type": "textarea",
    "required": false,
    "placeholder": "Required when status is Not OK"
  }
}
```

---

## 3. get_audit_checklist_form.php — Checklist only

Use when ticket header is already cached.

```json
{ "AuditTicketID": 1 }
```

Returns `mobile_checklist_form.fields[]` for the ticket's **sub audit** checkpoints.

---

## 4. submit_audit_checklist.php — Submit checklist

### Rules per checkpoint
| Field | Key | Required |
|-------|-----|----------|
| Measured value | `ResponseValue` | Yes |
| Status | `OkStatus` → `OK` or `Not OK` | Yes |
| Remarks | `Remarks` | Yes when `Not OK` |

### Full submit (mark completed)
```json
{
  "AuditTicketID": 1,
  "EmployeeID": 205,
  "FilledBy": "John Technician",
  "TechnicianNotes": "All readings captured on site",
  "mark_completed": 1,
  "responses": [
    {
      "ChecklistID": 10,
      "ResponseValue": "228",
      "OkStatus": "OK",
      "Remarks": "Within range"
    },
    {
      "ChecklistID": 11,
      "ResponseValue": "198",
      "OkStatus": "Not OK",
      "Remarks": "Below minimum — needs correction"
    }
  ]
}
```

### Save draft (partial — survives page refresh)
```json
{
  "AuditTicketID": 1,
  "EmployeeID": 205,
  "mark_completed": 0,
  "save_draft": 1,
  "1": "No",
  "value_1": "228",
  "remark_1": "Below range",
  "image_1": "data:image/jpeg;base64,...",
  "ProblemReportedByClient": "Client reported voltage issue",
  "Observation": "Checked main panel",
  "ClientRepresentative": "Mr. Rajesh",
  "userdata": {}
}
```

**Draft rules**
- Send `mark_completed: 0` or `save_draft: 1` while the technician is still filling the form.
- Partial data is saved to the database — on page refresh, call `get_audit_ticket_detail.php` and bind `data.mobile_legacy_payload` to restore the form.
- Images: base64 (`data:image/jpeg;base64,...`) or a saved filename from a previous response.
- Sign-off fields (client rep, observation, signature) can be sent in the same payload.

**Legacy flat keys** (same as corporate service reports): `"1"`/`"2"` = OK status (`Yes`/`No`), `value_N`, `remark_N`, `image_N`, plus `userdata` / `userdetails` nested objects (auto-merged).

### Response
```json
{
  "error": false,
  "message": "Checklist responses saved successfully.",
  "saved_count": 2,
  "TicketID": "CS-AUD-000001",
  "Status": "Completed",
  "checklist_summary": {
    "total": 6,
    "filled": 6,
    "ok": 5,
    "not_ok": 1,
    "completion_percent": 100
  }
}
```

---

## 5. save_audit_report_details.php — Professional report fields

Fill **before** generating PDF. Used for floor, full address, client sign-off, observations.

```json
{
  "AuditTicketID": 1,
  "ReportFloor": "Ground Floor",
  "ReportSiteAddress": "Shop 12, Ground Floor, Phoenix Mall, Lower Parel, Mumbai - 400013",
  "ClientRepresentative": "Mr. Rajesh Kumar",
  "ClientRepresentativeContact": "9876543210",
  "ClientRepresentativeDesignation": "Facility Manager",
  "ClientRepresentativeEmail": "rajesh@company.com",
  "AuditObservation": "UPS input voltage stable. Battery backup tested for 15 minutes.",
  "AuditConclusion": "Overall compliance satisfactory. One checkpoint needs follow-up.",
  "TechnicianNotes": "Site access granted at 10:00 AM"
}
```

### Field reference
| Key | PDF section |
|-----|-------------|
| `ReportFloor` | Customer / Site Details → Floor |
| `ReportSiteAddress` | Full site address (overrides branch address if set) |
| `ClientRepresentative` | Sign-off → Client Representative |
| `ClientRepresentativeContact` | Client contact on sign-off |
| `ClientRepresentativeDesignation` | Client designation |
| `ClientRepresentativeEmail` | Client email (stored, optional in PDF) |
| `AuditObservation` | Audit Notes → Observation |
| `AuditConclusion` | Audit Notes → Conclusion |
| `TechnicianNotes` | Audit Notes → Technician Notes |

If `ReportSiteAddress` is empty, PDF uses branch address from master data automatically.

---

## 6. generate_audit_branch_report.php — Build PDF

### Option A — Generate only (after saving report details)
```json
{ "AuditTicketID": 1 }
```

### Option B — Save details + generate in one call
```json
{
  "AuditTicketID": 1,
  "save_report_meta": 1,
  "ReportFloor": "3rd Floor",
  "ReportSiteAddress": "Tower B, 3rd Floor, Andheri East, Mumbai",
  "ClientRepresentative": "Ms. Priya Shah",
  "ClientRepresentativeContact": "9123456789",
  "ClientRepresentativeDesignation": "Branch Manager",
  "AuditObservation": "All electrical panels inspected.",
  "AuditConclusion": "Audit completed successfully."
}
```

### Response
```json
{
  "error": false,
  "message": "Audit branch report generated.",
  "pdfname": "audit-branch-report-CS-AUD-000001.pdf",
  "pdf_url": "https://techxpertindia.in/admin/audit-ticket/reports/audit-branch-report-CS-AUD-000001.pdf",
  "AuditTicketID": 1,
  "TicketID": "CS-AUD-000001"
}
```

### PDF includes
- Company name, branch, branch code
- **Floor**, full address, city, state, PIN, contact, email
- Ticket ID, master audit, sub audit, status, dates
- Technician & Branch Account Manager names
- Checklist table: checkpoint, ideal/min/max, actual value, OK/Not OK, remarks, compliance
- Observation, conclusion, technician notes
- Technician & client sign-off block

---

## 7. assign_audit_technician.php — BAM assign

**First assign (Raised → Assigned)**
```json
{
  "AuditTicketID": 1,
  "TechnicianID": 205,
  "EmployeeID": 101,
  "UpdatedBy": "bam_user",
  "Remarks": "Assign to site technician"
}
```

**Reassign**
```json
{
  "AuditTicketID": 1,
  "TechnicianID": 210,
  "EmployeeID": 101,
  "IsReassign": 1,
  "Remarks": "Reassign due to leave"
}
```

---

## 8. get_audit_ticket_history.php

```json
{ "AuditTicketID": 1 }
```

---

## 9. create_audit_ticket.php — Raise ticket

### Single audit (backward compatible)
```json
{
  "CorporateID": 12,
  "BranchID": 45,
  "MasterAuditID": 1,
  "SubAuditID": 3,
  "CreatedBy": "portal_user",
  "Remarks": "Quarterly audit"
}
```

### Multiple audits on one ticket
```json
{
  "CorporateID": 12,
  "BranchID": 45,
  "MasterAuditID": 1,
  "SubAuditIDs": [3, 5, 7],
  "CreatedBy": "mobile_user",
  "Remarks": "Combined branch audit"
}
```

Or with explicit master/sub pairs (supports different master audits):
```json
{
  "CorporateID": 12,
  "BranchID": 45,
  "Audits": [
    { "MasterAuditID": 1, "SubAuditID": 3 },
    { "MasterAuditID": 2, "SubAuditID": 8 }
  ],
  "CreatedBy": "mobile_user"
}
```

Response includes `audit_count` and `audits[]` array.

Ticket ID format: `CS-AUD-000001`. Assigned to Branch Account Manager automatically.

---

## Multi-audit tickets

- One ticket can include **multiple sub audits** (same or different master audits).
- `get_audit_ticket_detail.php` returns `audits[]`, `audit_count`, `is_multi_audit`, and `checklists_by_sub_audit[]`.
- `get_audit_checklist_form.php` accepts optional `SubAuditID` to load one audit's form; omit to load all audits.
- `submit_audit_checklist.php` accepts optional `SubAuditID` to save one audit at a time, or submit all checkpoints together.
- Ticket is **Completed** only when all checkpoints across **all** audits are filled.

---

## Master audit config (reference only)

To browse available audits **before** raising a ticket, use the separate master config API:

`https://techxpertindia.in/api/audit/`

| File | Purpose |
|------|---------|
| `get_mobile_corporate_audit.php` | Full audit → sub audit → checklist tree |
| `get_master_sub_audits.php` | Sub audits by master audit ID |
| `get_sub_audit_detail.php` | Sub audit + checklist template |

Once a ticket is raised, always use **audit-ticket** APIs above — checklist is tied to the ticket's audit list (`audits[]` / `SubAuditID` when filtering one audit).

---

## Database migrations (production)

Run in order if tables already exist:

1. `admin/audit-ticket/sql/alter_audit_ticket_add_history.sql`
2. `admin/audit-ticket/sql/alter_audit_ticket_bam_workflow.sql`
3. `admin/audit-ticket/sql/alter_audit_ticket_checklist_ok_status.sql`
4. `admin/audit-ticket/sql/alter_audit_ticket_report_meta.sql` ← **report fields**
5. `admin/audit-ticket/sql/alter_audit_ticket_mobile_draft.sql` ← **images + draft + sign-off**
6. `admin/audit-ticket/sql/alter_audit_ticket_multi_audit.sql` ← **multiple audits per ticket**

Fresh install: `admin/audit-ticket/sql/create_audit_ticket_tables.sql`

Ensure folder is writable: `admin/audit-ticket/reports/`

---

## Quick copy — technician session

```bash
# 1. My tickets
POST get_audit_tickets.php  { "EmployeeID": 205 }

# 2. Open ticket
POST get_audit_ticket_detail.php  { "AuditTicketID": 1 }

# 3. Submit checklist
POST submit_audit_checklist.php  { "AuditTicketID": 1, "EmployeeID": 205, "mark_completed": 1, "responses": [...] }

# 4. Save report details
POST save_audit_report_details.php  { "AuditTicketID": 1, "ReportFloor": "Ground Floor", ... }

# 5. Generate PDF
POST generate_audit_branch_report.php  { "AuditTicketID": 1 }
```
