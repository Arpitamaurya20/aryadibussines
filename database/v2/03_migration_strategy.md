# Legacy → V2 mapping (do not run until V2 is live)

V2 is created **empty**. This document is a later cutover plan. It must not reshape V2 to look like the old tables.

Do **not** auto-migrate. Clean, validate, then load in batches with a dual-write or freeze window.

Legacy source of truth observed in `employees`, `users`, `user_roles`, `user_divisions`, `user_tokens`, `employee_attendance`, `employee_leave*`. Passwords in legacy `users.Password` are not modern hashes (MD5-class). **Do not copy password hashes into V2.** Force a reset.

---

## Mapping

| OLD TABLE | NEW TABLE(S) | Notes |
|---|---|---|
| `employees` | `employees` | Split name; map gender; parse `DateofJoining` to `DATE`; map status from `IsActive` |
| `employees.Designation` (text) | `designations` + `employees.designation_id` | Upsert designation by normalized name per company |
| `employees.Division` (text) | `divisions` + `employees.division_id` | Upsert by name |
| `employees.Department` (text) | `departments` + `employees.department_id` | Upsert by name under a default/unknown division if missing |
| `employees.City` / `State` | `locations` / `branches` | Infer internal branch; do not use client `branch` table |
| `employees.Supervisor` (numeric text) | `employees.supervisor_employee_id` | After all employees exist; skip invalid IDs |
| `employees` salary VARCHAR/INT columns | `employee_compensations` + `employee_compensation_lines` | Parse to `DECIMAL(12,2)`; one open structure `effective_from = date_of_joining` |
| `employees.UANNumber`, `Epf_number`, `Esic_number`, flags | `employee_statutory_accounts` | Treat `NA` / empty as NULL |
| `employees.BankAccount*` | `employee_bank_accounts` | Encrypt account number; parse IFSC out of free text where possible |
| `employees.PAN`, `Aadhar` | `employee_identity_numbers` | Encrypt; last4 only in clear; skip junk |
| `employees.*Image` | `file_assets` + `employee_documents` | Copy files to object storage; store keys, not blobs |
| `employees.AttendanceLatitude/Longitude/Radius` | `attendance_geofences` + `employee_geofence_assignments` | One fence per distinct coordinate set |
| `employees.EmployeeLeave` | **discard as source** | Rebuild from `employee_leave*` if present |
| `employees.WeeklyOff` | `employees.weekly_off_weekday` | Map Sunday→0, etc. |
| `employees.Vendor = 1` | `vendors` + optional `users.vendor_id` | Do not keep vendors inside `employees` |
| `users` | `users` | Map username/email; **new password hash via reset**; `EmployeeID=-1` → NULL |
| `users.UserType` | `user_roles` | Map Admin→ADMIN/SUPER_ADMIN (manual), Employee→EMPLOYEE, Corporate* **out of HR V2** |
| `users.AuthToken` / `TokenExpiry` | **do not copy** | Sessions are issued on next login |
| `user_roles` (legacy, often EmployeeID) | `user_roles` | Resolve employee → `users.user_id`; map role names to `roles.code` |
| `user_divisions` | `user_access_scopes` (`scope_type=division`) | Resolve division name/id to `divisions.division_id` |
| `user_tokens` | `user_sessions` + `user_devices` | **Do not copy raw JWT/refresh.** Users re-authenticate |
| `user_devices` / FCM columns | `user_devices` + `user_push_tokens` | Re-register push on next app open preferred |
| `employee_attendance` | `attendance_records` | Parse VARCHAR dates/times; skip duplicates by `(employee_id, work_date)` |
| `employee_leave` / balances | `employee_leave_requests` / `employee_leave_balances` | Map CL/SL codes to `leave_types` |
| `department` (legacy HR) | `departments` | Names only; heads become employees after load |
| `company` / `branch` (legacy) | **not** V2 `companies`/`branches` | Those rows are **client sites** for tickets. Keep in a future CRM module |
| `vendor_registration` | `vendors` | Separate from employees |
| `portal_login_logs` | optional `auth_events` | Only if worth historical security; IP often missing |
| `password_reset_otp` | **do not copy** | Issue new reset flow |

Keep a `legacy_id` map **outside** V2 (or a temporary `migration_map` table you drop after cutover):

```text
legacy_employees.ID → employees.employee_id
legacy_users.UserID → users.user_id
```

Do not add `legacy_id` columns to production V2 tables unless you explicitly want a long-lived trace. Prefer a throwaway mapping database.

---

## Suggested load order

1. Seed V2 (`02_seed.sql`)
2. Insert one employer `companies` row (Techxpert / Aryadi legal entity)
3. Distinct designations / divisions / departments from employee text → masters
4. Locations/branches from employee city/state (internal only)
5. Employees (no supervisor yet)
6. Patch supervisors
7. Opening `employment_history` row per employee
8. Compensation + statutory + bank + identity + documents + geofences
9. Users **without** passwords (`must_change_password=1`, random unusable hash) + email reset campaign
10. `user_roles` + `user_access_scopes`
11. Attendance and leave in date order
12. Vendors last (those flagged or from `vendor_registration`)

Corporate portal users (`UserType` like Corporate Admin) belong to a **future client-identity module**, not HR `users` tied to employees.

---

## What you must not carry over

- VARCHAR money and dates
- `-1` as a magic FK
- Raw tokens
- MD5 passwords
- `UserType` string as authorization
- Salary/documents/bank on the employee row
- Client `company`/`branch` as employee org
- Vendors stored as employees
- Hardcoded master passwords in PHP
- MyISAM / latin1

---

## Cutover

1. Freeze HR writes on legacy or dual-write if required.
2. Load V2, reconcile counts (employees, active users, attendance last 90 days).
3. Point new API to `aryadi_business_v2`.
4. Force password reset and re-login (new sessions).
5. Keep legacy read-only until payroll/HR sign off.
