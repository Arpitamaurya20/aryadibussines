# TechXpert Portal — End User Training Document

**Version:** 1.0  
**Date:** June 2026  
**Total training time:** 28 minutes  
**Platform:** TechXpert Admin Portal — https://techxpertindia.in

---

## Document purpose

This document trains end users on the **new and updated portal processes** introduced in TechXpert:

- Branch & State dashboards  
- Ticket reassignment & escalation  
- Ticket verification  
- Ticket billing workflow  
- Employee asset acknowledgement  

Each module is designed as a **standalone 3–5 minute session** or as one combined **28-minute** training.

---

## Training overview

| Module | Duration | Topic | Primary users |
|--------|----------|-------|---------------|
| 1 | 4 min | My Branch Dashboard | Branch Account Manager |
| 2 | 4 min | My State Dashboard | State Corporate Lead |
| 3 | 3 min | Reassign technician | Branch manager / coordinator |
| 4 | 4 min | Ticket escalation | Branch manager, state manager |
| 5 | 4 min | Verify tickets | State manager / branch manager |
| 6 | 5 min | Ticket billing | Billing / accounts team |
| 7 | 4 min | Employee asset acknowledgement | HR / admin + employee |
| | **28 min** | **Full session** | |

---

## Module 1 — My Branch Dashboard (4 minutes)

**Menu path:** My Branch Dashboard  
**Who should use this:** Branch Account Manager (BAM)

### What it is

A live **wallboard dashboard** showing ticket and quotation activity for **only the branches assigned to you** as Account Branch Manager.

### What you can see

- Ticket counts by status (open, in progress, closed, etc.)  
- Quotation summary  
- Branch-wise and technician-wise breakdown  
- Charts and filters for quick review  

### How to use

1. Log in to the TechXpert portal.  
2. Click **My Branch Dashboard** in the left navigation menu.  
3. Select a **time period** — current financial year, this month, or custom date range.  
4. Apply filters: **company**, **branch**, **ticket type** (R&M, PPM, Projects, etc.).  
5. Review open tickets and plan technician assignments for the day.

### When to use

| Situation | Action |
|-----------|--------|
| Start of workday | Check open ticket load across your branches |
| Before assigning a technician | See which branch has highest pending count |
| Weekly review | Track ticket closure and quotation trends |

### Key point

You only see branches where you are mapped as **Account Branch Manager**. For a wider state-level view, use **My State Dashboard** (Module 2) if your role includes state access.

---

## Module 2 — My State Dashboard (4 minutes)

**Menu path:** My State Dashboard  
**Who should use this:** State Corporate Lead

### What it is

A **state-wide wallboard** with the same layout as the Branch Dashboard, but covering **all branches in your assigned state(s)**.

### What you can see

- All companies and branches in your state  
- Ticket status distribution across the state  
- Regional filters, company filters, ticket type filters  
- Quotation and technician overview at state level  

### How to use

1. Log in → open **My State Dashboard**.  
2. If you manage more than one state, select the **state** from the filter.  
3. Set **period** and filter by company or ticket type as needed.  
4. Use the dashboard to identify branches that need attention or escalation.

### Branch Dashboard vs State Dashboard

| | Branch Dashboard | State Dashboard |
|---|------------------|-----------------|
| **Scope** | Your assigned branches only | Entire state |
| **Role** | Branch Account Manager | State Corporate Lead |
| **Best for** | Daily branch operations | Oversight, verification, escalation review |

---

## Module 3 — Reassign technician (3 minutes)

**Where:** Corporate Tickets → open a ticket → **Assignment** tab  
**Who can do this:** Branch Account Manager and authorized escalation handlers

### What it is

Move an open ticket from one **technician** to another without closing the ticket.

### When to reassign

- Assigned technician is on leave or unavailable  
- Wrong technician was assigned  
- Site requires a different skill set  
- After escalation — send work back to the field with a new technician  

### Steps

1. Open the **corporate ticket** from the ticket list.  
2. Go to the **Assignment** tab.  
3. Select the **new technician** from the dropdown.  
4. Enter **remarks** explaining why you are reassigning (required for audit trail).  
5. Click **Reassign**.

### What happens after reassign

| Effect | Detail |
|--------|--------|
| New assignee | Ticket appears in the new technician’s queue |
| Escalation cleared | Any active escalation on the ticket is resolved |
| Due date updated | Due date is refreshed (typically extended by one day) |
| History saved | Reassignment is recorded in ticket history |

### Rules

- Cannot reassign **closed** or **cancelled** tickets.  
- You must select a **different** technician — same person is not allowed.  
- Always add a clear remark.

---

## Module 4 — Ticket escalation (4 minutes)

**Where:** Corporate Tickets → open a ticket → **Assignment** tab → Escalation section  
**Who is involved:** Branch Manager → State Manager → CEO

### What it is

A structured process to ensure **overdue tickets** are not ignored. Tickets escalate up the management chain when action is not taken in time.

### Automatic escalation

When a ticket **passes its due date** without resolution:

```
Level 1: Branch Manager
        ↓ (no response in time)
Level 2: State Manager
        ↓ (no response in time)
Level 3: CEO
```

- Ticket status changes to **Escalated**.  
- Each level has a defined **response window** (hours to act).  
- If no action is taken, the ticket moves to the next level automatically.

### Manual escalation

For urgent issues — do not wait for the due date:

1. Open the ticket → **Assignment** tab.  
2. Find **Manual Escalation**.  
3. Select the escalation level.  
4. Enter the reason.  
5. Click **Escalate Now**.

### After escalation

- The escalated manager sees the ticket in their queue.  
- **Reassign to technician** (Module 3) clears escalation and returns work to the field.  
- Full history is visible under **Escalation History** on the Assignment tab.

---

## Module 5 — Verify tickets (4 minutes)

**Where:** Corporate Tickets list → **Verify** action / verification modal  
**Who can verify:** State Corporate Lead, Branch Account Manager, Admin

### What it is

A **quality and documentation check** that must be completed before a closed ticket can enter the **billing queue**.

### Verification checklist

Complete all items before clicking **Verify Ticket**:

| # | Check item | What it means |
|---|------------|---------------|
| 1 | Quality of ticket is satisfactory | Field work and ticket quality meet standards |
| 2 | Digital report is complete | Service report / digital documentation is done |
| 3 | WCC checked | Work completion certificate reviewed |
| 4 | Vendor payment checked | Vendor-side payment reviewed where applicable |
| 5 | Quotation quantity checked | Billed quantity matches approved quotation |
| 6 | DPR checked | *(Project tickets only)* Daily progress report reviewed |

### Steps

1. Open the ticket from the **Corporate Tickets** list.  
2. Click the **verify** icon (green ✓ = verified, red ✗ = not verified).  
3. Tick all checklist items.  
4. Click **Verify Ticket**.

### After verification

- Ticket shows a **verified** status in the list.  
- Ticket becomes **eligible for the billing queue** (Module 6).  
- Tickets that are **not verified** will **not** appear in the Billing **Eligible** tab.

---

## Module 6 — Ticket billing (5 minutes)

**Menu path:** Ticket Billing  
**Who should use this:** Billing / accounts team (role: Ticket Billing)

### What it is

A **stage-based billing queue** to process closed, verified tickets from selection through invoicing to payment tracking.

### Billing queue stages

| Tab / stage | Stage name | What it means |
|-------------|------------|---------------|
| 1 | **Eligible** | SM-verified, unbilled tickets ready to be selected |
| 2 | **Ready for Billing** | Selected tickets ready for invoice creation |
| 3 | **Billing Verification** | Under accounts team review |
| 4 | **Payment Status** | Invoice issued — track payment |
| 5 | **Partial Billing** | Partially billed or partially paid tickets |

### Workflow steps

**Step 1 — Select for Billing (Eligible)**  
- Only tickets that passed **Module 5 verification** appear here.  
- Select one or more tickets.  
- Move them to **Ready for Billing**.

**Step 2 — Ready for Billing**  
- Review ticket details, amounts, PO, and branch information.  
- Process billing and move to verification or payment stage.

**Step 3 — Billing Verification**  
- Accounts team reviews before final invoice.

**Step 4 — Payment Status**  
- Update status: Paid / Partially Paid / Pending.  
- Partial cases remain in the **Partial Billing** tab.

### Filters available

Date range · Company · Branch · State · Region · Ticket type

### Key rules

| Rule | Detail |
|------|--------|
| Verification required | Unverified tickets never appear in **Eligible** |
| Ticket must be closed | Billing is for completed work only |
| Use filters | Process one client or one state at a time for accuracy |

---

## Module 7 — Employee asset acknowledgement (4 minutes)

**Admin menu:** Employee Asset Acknowledgement  
**Employee access:** Public link (no login required)  
**Who:** HR / admin assigns · employee acknowledges

### What it is

A process to **record company assets** issued to employees (laptop, mobile, tools, vehicle, ID card, etc.) and collect **digital acknowledgement** from the employee.

### Asset categories (examples)

Laptop · Desktop · Monitor · Mobile Phone · Tablet · Technician Tools · Safety Equipment · Vehicle · Access Card / ID · Software License · Other

### Admin steps

1. Open **Employee Asset Acknowledgement** from the menu.  
2. Select the **employee**.  
3. Add each asset: category, serial number, description, issue date.  
4. Save — status shows **Pending Acknowledgement**.  
5. Click **Send link** or copy the **public form URL** and share with the employee (email / WhatsApp / SMS).

### Employee steps

1. Open the **link** received from HR.  
2. Review the list of assets assigned to them.  
3. Confirm and **acknowledge** each item (or acknowledge all).  
4. Submit — status changes to **Acknowledged**.

### Asset status updates (admin, later)

| Status | When to use |
|--------|-------------|
| With Employee | Asset actively assigned (default) |
| Returned | Employee returned asset to company |
| Lost / Missing | Asset cannot be located |
| Damaged | Asset is damaged |
| Retired | Asset written off |

### Key rules

- Each link is **employee-specific** — do not forward to others.  
- Follow up on **Pending Acknowledgement** until all items are confirmed.  
- Update hold status when assets are returned or lost.

---

## End-to-end process flow

How all modules connect in the daily ticket lifecycle:

```
┌─────────────────────────────────────────────────────────────────────────┐
│  TICKET RAISED                                                          │
│  Customer / portal raises a corporate ticket                            │
└───────────────────────────────┬─────────────────────────────────────────┘
                                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│  MONITOR — Module 1 or 2                                                │
│  Branch Dashboard (BAM)  OR  State Dashboard (State Lead)               │
│  Track open tickets, quotations, workload by branch                     │
└───────────────────────────────┬─────────────────────────────────────────┘
                                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│  FIELD WORK                                                             │
│  Technician assigned → works on site → closes ticket + digital report   │
└───────────────────────────────┬─────────────────────────────────────────┘
                                ▼
                    ┌───────────┴───────────┐
                    ▼                       ▼
┌──────────────────────────┐   ┌──────────────────────────┐
│  REASSIGN — Module 3     │   │  ESCALATION — Module 4   │
│  Change technician       │   │  Auto or manual escalate │
│  Escalation cleared      │   │  BM → SM → CEO           │
└──────────────────────────┘   └──────────────────────────┘
                    │                       │
                    └───────────┬───────────┘
                                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│  VERIFY — Module 5                                                      │
│  State / Branch manager completes verification checklist                  │
│  ✓ Quality  ✓ Digital report  ✓ WCC  ✓ Vendor  ✓ Quotation qty         │
└───────────────────────────────┬─────────────────────────────────────────┘
                                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│  BILLING — Module 6                                                     │
│  Eligible → Ready → Verification → Payment → (Partial if needed)        │
└───────────────────────────────┬─────────────────────────────────────────┘
                                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│  PAYMENT COMPLETE                                                       │
│  Ticket fully billed and paid — process closed                            │
└─────────────────────────────────────────────────────────────────────────┘


        PARALLEL PROCESS (any time)
┌─────────────────────────────────────────────────────────────────────────┐
│  ASSET ACKNOWLEDGEMENT — Module 7                                       │
│  HR assigns assets → employee opens link → acknowledges → HR updates     │
│  status (Returned / Lost / Damaged) when asset lifecycle changes         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Role quick reference

| Your role | Modules you need |
|-----------|------------------|
| Branch Account Manager | 1, 3, 4, 5 |
| State Corporate Lead | 2, 4, 5 |
| Billing / Accounts team | 6 |
| HR / Admin | 7 |
| Technician | Mobile app (field work — not covered in this document) |

---

## Suggested training session plan (30 minutes)

| Time | Module | Activity |
|------|--------|----------|
| 0:00 – 0:04 | 1 or 2 | Live demo of dashboard for attendee’s role |
| 0:04 – 0:07 | 3 | Demo reassign on a sample ticket |
| 0:07 – 0:11 | 4 | Show escalation history + manual escalate |
| 0:11 – 0:15 | 5 | Walk through verification checklist |
| 0:15 – 0:20 | 6 | Walk through billing queue tabs |
| 0:20 – 0:24 | 7 | Assign sample asset + open employee link |
| 0:24 – 0:30 | Q&A | Questions and practice |

---

## Support

For portal access issues or process questions, contact your TechXpert coordinator with:

- Your **role** (BAM / State Lead / Billing / HR)  
- **Screen name** or menu item where the issue occurred  
- **Ticket ID** (if related to a specific ticket)  
- Screenshot of the error or unexpected result  

---

*TechXpert Facilities India Private Limited — End User Training Document*
