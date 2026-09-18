# Employee Leave Management Module

Policy-driven leave system with monthly CL/SL quotas, annual caps, carry-forward, half-day, sandwich rule, advance leave, and comp-off.

## Install

Run once on your database:

```sql
SOURCE admin/employee-leave-mgmt/sql/install_employee_leave_mgmt.sql;
```

Or import `install_employee_leave_mgmt.sql` via phpMyAdmin.

## Default policy

| Rule | Default |
|------|---------|
| Monthly CL | 1 |
| Monthly SL | 1 |
| Annual total | 24 (12 CL + 12 SL) per **financial year** |
| Financial year | April–March (start month = 4) |
| Half-day | Enabled |
| Sandwich rule | Enabled (WO + holidays between leave) |
| Carry forward | Enabled (0 max = carry all unused) |
| Advance leave | Enabled (max 3 days/type/FY) |
| Comp-off | Enabled (90-day validity, HR approval) |

Accrual starts from the **later of** joining date or current financial-year start. Example: FY starts April, joined last year, no leave taken — by June you have **3 months** accrued (Apr + May + Jun = 3 CL + 3 SL).

Adjust via **Leave Policy** in the admin portal (HR). For existing databases, also run:

```sql
SOURCE admin/employee-leave-mgmt/sql/update_financial_year_start.sql;
```

Employees should open **My Leave** once after deploy so monthly balances rebuild for the financial year.

## Portal pages

| Page | URL | Who |
|------|-----|-----|
| My Leave | `/admin/employee-leave-mgmt/view-my-leaves` | All employees |
| Leave Policy | `/admin/employee-leave-mgmt/view-leave-policy` | HR / Admin |
| Comp-off Approval | `/admin/employee-leave-mgmt/view-comp-off-approval` | HR / Admin |

Supervisor and HR **approval** still use existing modules:

- `leave-approval` (supervisor)
- `leave-hr-approval` (HR final)

Balance is deducted automatically when leave status becomes `Approved` (sync on balance fetch / apply).

## Mobile API (JSON POST)

Base path: `/api/employee-leave/`

| Endpoint | Purpose |
|----------|---------|
| `get_leave_apply_screen.php` | **Recommended** — balance + types + policy for Apply screen |
| `get_leave_meta.php` | Types, durations, policy (+ balance if EmployeeID sent) |
| `get_leave_balance.php` | Full balance (same as portal cards) |
| `get_leave_policy.php` | Company policy only |
| `validate_leave.php` | Preview days, balance before/after, errors |
| `apply_leave.php` | Apply leave — returns updated `balance` |
| `cancel_leave.php` | Cancel pending leave — returns `balance` |
| `get_leave_history.php` | History + optional `balance` |
| `get_comp_off.php` | Comp-off list + balance |
| `request_comp_off.php` | Claim comp-off |

### Mobile — open Apply Leave screen (one call)

```json
POST /api/employee-leave/get_leave_apply_screen.php
{ "EmployeeID": 154 }
```

Response includes `balance.cl.available`, `balance.sl.available`, `balance.total_cl_sl_available`, `balance.financial_year_label`, `balance.financial_year`, `joining_date`, `accrual_months`, `apply_rules`, `apply_hint`.

### Mobile — check before submit

```json
POST /api/employee-leave/validate_leave.php
{
  "EmployeeID": 154,
  "TypeOfLeave": "CL",
  "FromDate": "2026-07-06",
  "ToDate": "2026-07-10",
  "Duration": "Full Day",
  "ReasonOfLeave": "Personal"
}
```

Response: `days_to_deduct`, `balance_available`, `balance_after`, `calculation.working_days`, `balance` object.

### Apply leave example

```json
POST /api/employee-leave/apply_leave.php
{
  "EmployeeID": 154,
  "TypeOfLeave": "CL",
  "FromDate": "2026-07-01",
  "ToDate": "2026-07-01",
  "Duration": "Half Day",
  "HalfDaySession": "first_half",
  "ReasonOfLeave": "Personal work",
  "CreatedBy": "mobile_app"
}
```

## Notes

- Cannot apply without sufficient balance (unless advance leave is within policy limit).
- Overlapping leave dates are blocked.
- Comp-off requires attendance punch on a holiday or weekly off.
- Existing `employee_leave` rows are unchanged; new applications use extended columns.
