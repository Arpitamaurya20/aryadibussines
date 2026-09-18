# Attendance Approval Module — Production Deployment Guide

**Project:** TechXpert  
**Module:** Two-layer attendance approval (Supervisor → HR final)  
**Document version:** 1.0  
**Base path on server:** `/path-to-your-project/techxpert/` (adjust for your hosting)

---

## 1. Approval flow (production behaviour)

| Step | Who | Status after action |
|------|-----|---------------------|
| 1 | Employee punches in (mobile/API) | `Pending` |
| 2 | Supervisor approves | `SupervisorApproved` (waiting for HR) |
| 3 | HR final approves | `Approved` |
| Reject (supervisor) | Supervisor | `Rejected` (only when status was `Pending`) |
| Reject (HR) | HR / Admin / Super Admin | `Rejected` (only when status was `SupervisorApproved`) |

---

## 2. Database — run on production (line by line)

Run in **phpMyAdmin** or MySQL client on the **production database** (e.g. `techxpertindia`).

### Step 2.1 — First migration (safe to re-run)

**File on disk:**

```
admin/attendance-list/sql/add_attendance_approval_columns.sql
```

**What it does:**

- Adds `ApprovalStatus`, `ApprovedBy`, `ApprovedAt`, `RejectionReason` to `employee_attendance`
- Skips columns that already exist (no duplicate-column error)
- Marks old `Pending` rows as `Approved` only if two-layer columns are not installed yet

**Action:** Execute the full SQL file once.

---

### Step 2.2 — Second migration (two-layer approval)

**File on disk:**

```
admin/attendance-list/sql/add_attendance_two_layer_approval.sql
```

**What it does:**

- Adds `SupervisorApprovedBy`, `SupervisorApprovedAt` to `employee_attendance`
- Safe to re-run if columns already exist

**Action:** Execute the full SQL file once after Step 2.1.

---

### Step 2.3 — Verify columns

```sql
SHOW COLUMNS FROM employee_attendance
WHERE Field IN (
  'ApprovalStatus',
  'ApprovedBy',
  'ApprovedAt',
  'RejectionReason',
  'SupervisorApprovedBy',
  'SupervisorApprovedAt'
);
```

**Expected:** 6 rows returned.

---

## 3. New folders to create on production

Create these folders if they do not exist (upload files into them):

```
admin/attendance-approval/
admin/attendance-approval/action/
admin/attendance-hr-approval/
admin/attendance-hr-approval/action/
```

---

## 4. New files to upload (full list)

### 4.1 SQL (reference only — do not expose via web)

| # | File path |
|---|-----------|
| 1 | `admin/attendance-list/sql/add_attendance_approval_columns.sql` |
| 2 | `admin/attendance-list/sql/add_attendance_two_layer_approval.sql` |

---

### 4.2 Supervisor module — Team Attendance

| # | File path | Type |
|---|-----------|------|
| 3 | `admin/attendance-approval/view-attendance-approval.php` | Page (UI) |
| 4 | `admin/attendance-approval/action/view-attendance-approval-post.php` | AJAX — DataTable data |
| 5 | `admin/attendance-approval/action/approve_attendance.php` | AJAX — supervisor approve |
| 6 | `admin/attendance-approval/action/reject_attendance.php` | AJAX — supervisor reject |

**Production URLs (example):**

```
https://your-domain.com/admin/attendance-approval/view-attendance-approval.php
https://your-domain.com/admin/attendance-approval/action/view-attendance-approval-post.php
https://your-domain.com/admin/attendance-approval/action/approve_attendance.php
https://your-domain.com/admin/attendance-approval/action/reject_attendance.php
```

---

### 4.3 HR module — HR Attendance Approval

| # | File path | Type |
|---|-----------|------|
| 7 | `admin/attendance-hr-approval/view-attendance-hr-approval.php` | Page (UI) |
| 8 | `admin/attendance-hr-approval/action/view-attendance-hr-approval-post.php` | AJAX — DataTable data |
| 9 | `admin/attendance-hr-approval/action/approve_attendance.php` | AJAX — HR final approve |
| 10 | `admin/attendance-hr-approval/action/reject_attendance.php` | AJAX — HR final reject |

**Production URLs (example):**

```
https://your-domain.com/admin/attendance-hr-approval/view-attendance-hr-approval.php
https://your-domain.com/admin/attendance-hr-approval/action/view-attendance-hr-approval-post.php
https://your-domain.com/admin/attendance-hr-approval/action/approve_attendance.php
https://your-domain.com/admin/attendance-hr-approval/action/reject_attendance.php
```

---

### 4.4 JavaScript modules

| # | File path | Used by |
|---|-----------|---------|
| 11 | `admin/js/modules/attendance-approval.js` | Supervisor Team Attendance page |
| 12 | `admin/js/modules/attendance-hr-approval.js` | HR Attendance Approval page |

---

## 5. Existing files to **replace** on production

Upload these over the current production copies.

| # | File path | What changed |
|---|-----------|--------------|
| 13 | `admin/attendance-list/controller/attendance_controller.php` | Approval helpers, supervisor/HR approve functions |
| 14 | `admin/attendance-list/action/view-attendance-list-post.php` | Approval columns in admin attendance list |
| 15 | `admin/attendance-list/view-attendance-list.php` | Table headers for approval status |
| 16 | `admin/controllers/common_controllers.php` | Nav flags + `enableAttendanceApprovalNavForSupervisor()` |
| 17 | `admin/navigation/admin_navigation.php` | Menu: Team Attendance + HR Attendance Approval |
| 18 | `admin/navigation/roles_navigation.json` | HR / Admin / Super Admin → `_Nav_Attendance_HR_Approval` |
| 19 | `api/punch_in_employee_attendance.php` | New punch → `ApprovalStatus = Pending` |
| 20 | `api/employeenewapi/punch_in.php` | New punch → `ApprovalStatus = Pending` |

---

## 6. Action files — request / response summary

### 6.1 Supervisor — `approve_attendance.php`

| Item | Value |
|------|--------|
| **Path** | `admin/attendance-approval/action/approve_attendance.php` |
| **Method** | POST |
| **POST params** | `ID` = `employee_attendance.ID` |
| **Success message** | Supervisor approval done. Record sent to HR for final approval. |
| **DB change** | `ApprovalStatus` → `SupervisorApproved`, sets `SupervisorApprovedBy`, `SupervisorApprovedAt` |

---

### 6.2 Supervisor — `reject_attendance.php`

| Item | Value |
|------|--------|
| **Path** | `admin/attendance-approval/action/reject_attendance.php` |
| **Method** | POST |
| **POST params** | `ID`, optional `RejectionReason` |
| **DB change** | `ApprovalStatus` → `Rejected` |

---

### 6.3 Supervisor — `view-attendance-approval-post.php`

| Item | Value |
|------|--------|
| **Path** | `admin/attendance-approval/action/view-attendance-approval-post.php` |
| **Method** | POST (DataTables server-side) |
| **GET params** | `filter_date`, `EmployeeID`, `ApprovalStatus`, `Supervisor_EmployeeID` |
| **Scope** | Only employees where `employees.Supervisor` = logged-in user's `EmployeeID` |

---

### 6.4 HR — `approve_attendance.php`

| Item | Value |
|------|--------|
| **Path** | `admin/attendance-hr-approval/action/approve_attendance.php` |
| **Method** | POST |
| **POST params** | `ID` |
| **Roles allowed** | HR, Admin, Super Admin |
| **DB change** | `ApprovalStatus` → `Approved`, sets `ApprovedBy`, `ApprovedAt` |

---

### 6.5 HR — `reject_attendance.php`

| Item | Value |
|------|--------|
| **Path** | `admin/attendance-hr-approval/action/reject_attendance.php` |
| **Method** | POST |
| **POST params** | `ID`, optional `RejectionReason` |
| **DB change** | `ApprovalStatus` → `Rejected` |

---

### 6.6 HR — `view-attendance-hr-approval-post.php`

| Item | Value |
|------|--------|
| **Path** | `admin/attendance-hr-approval/action/view-attendance-hr-approval-post.php` |
| **Method** | POST (DataTables server-side) |
| **GET params** | `filter_date`, `EmployeeID`, `ApprovalStatus` |
| **Scope** | All employees (HR view) |

---

## 7. Navigation & access (production)

### 7.1 Menu items

| Menu label | Nav flag | Page file |
|------------|----------|-----------|
| Employees Attendance | `_Nav_Attendance_List` | `admin/attendance-list/view-attendance-list.php` |
| Team Attendance | `_Nav_Attendance_Approval` | `admin/attendance-approval/view-attendance-approval.php` |
| HR Attendance Approval | `_Nav_Attendance_HR_Approval` | `admin/attendance-hr-approval/view-attendance-hr-approval.php` |

### 7.2 Who sees which menu

| User type | Team Attendance | HR Attendance Approval |
|-----------|-----------------|------------------------|
| Any employee with subordinates (`employees.Supervisor` = their ID) | Yes (auto) | No |
| HR role | No (unless also supervisor) | Yes |
| Admin / Super Admin | No (unless also supervisor) | Yes |
| Other roles | No | No |

**Note:** Team Attendance is added automatically in `common_controllers.php` → `enableAttendanceApprovalNavForSupervisor()` when the user has at least one active supervised employee.

### 7.3 Roles in `roles_navigation.json` (HR menu)

These roles include `_Nav_Attendance_HR_Approval`:

- Super Admin  
- Admin  
- HR  

---

## 8. Employee setup (production data)

For each employee who should use approval:

1. **Supervisor**  
   - User must link to an **EmployeeID** in `users` / session `Roles['EmployeeID']`.  
   - Subordinates must have `employees.Supervisor` = supervisor's `employees.ID`.

2. **HR**  
   - User must have role **HR**, **Admin**, or **Super Admin** in `user_roles`.

3. **Set supervisor on employee profile**  
   - Admin → Employees → Role tab → **Supervisor** dropdown.

---

## 9. Production deployment checklist (line by line)

1. Backup production database.  
2. Backup production `admin/` and `api/` folders.  
3. Run `add_attendance_approval_columns.sql` on production DB.  
4. Run `add_attendance_two_layer_approval.sql` on production DB.  
5. Run `SHOW COLUMNS` verify query (Section 2.3).  
6. Create folders: `admin/attendance-approval/action/`, `admin/attendance-hr-approval/action/`.  
7. Upload all **12 new files** (Section 4.1–4.4).  
8. Upload all **8 modified files** (Section 5).  
9. Clear browser cache (Ctrl+F5) for admin users.  
10. Log in as supervisor → open **Team Attendance** → approve one test record.  
11. Log in as HR → open **HR Attendance Approval** → final-approve test record.  
12. Confirm DataTable refreshes after approve/reject.  
13. Confirm mobile punch-in creates `Pending` attendance.

---

## 10. `employee_attendance` columns reference

| Column | Purpose |
|--------|---------|
| `ApprovalStatus` | `Pending`, `SupervisorApproved`, `Approved`, `Rejected` |
| `SupervisorApprovedBy` | `employees.ID` of supervisor who approved |
| `SupervisorApprovedAt` | Date/time of supervisor approval |
| `ApprovedBy` | `employees.ID` of HR (final approver); NULL if not set |
| `ApprovedAt` | Date/time of HR final approval |
| `RejectionReason` | Text if rejected |

---

## 11. Dependencies (already in project — do not delete)

These existing files are required by the module:

| Path | Purpose |
|------|---------|
| `admin/controllers/common_controllers.php` | DB, session, `_UpdateTableRecords`, navigation |
| `admin/includes/autoloader.inc.php` | Loads `Employee`, `Dbh`, `Core` classes |
| `admin/classes/employee.class.php` | `calculatetimeDifference()` |
| `admin/classes/dbh.class.php` | Database connection (AJAX fallback) |
| `admin/attendance-list/action/get_address.php` | Location map address on approval pages |
| `admin/media/employee_attendance/` | Check-in / check-out images |
| `admin/navigation/admin_navigation.php` | Sidebar menu |
| `admin/js/modules/employee.js` | `ViewAttendanceImage()` modal |

---

## 12. Quick file tree (copy checklist)

```
techxpert/
├── api/
│   ├── punch_in_employee_attendance.php          [MODIFY]
│   └── employeenewapi/
│       └── punch_in.php                          [MODIFY]
└── admin/
    ├── attendance-approval/                      [NEW FOLDER]
    │   ├── view-attendance-approval.php          [NEW]
    │   └── action/
    │       ├── view-attendance-approval-post.php [NEW]
    │       ├── approve_attendance.php            [NEW]
    │       └── reject_attendance.php               [NEW]
    ├── attendance-hr-approval/                     [NEW FOLDER]
    │   ├── view-attendance-hr-approval.php       [NEW]
    │   └── action/
    │       ├── view-attendance-hr-approval-post.php [NEW]
    │       ├── approve_attendance.php            [NEW]
    │       └── reject_attendance.php               [NEW]
    ├── attendance-list/
    │   ├── sql/
    │   │   ├── add_attendance_approval_columns.sql      [NEW]
    │   │   └── add_attendance_two_layer_approval.sql    [NEW]
    │   ├── controller/
    │   │   └── attendance_controller.php         [MODIFY]
    │   ├── action/
    │   │   └── view-attendance-list-post.php     [MODIFY]
    │   ├── view-attendance-list.php              [MODIFY]
    │   └── ATTENDANCE_APPROVAL_PRODUCTION_DEPLOY.md [THIS FILE]
    ├── controllers/
    │   └── common_controllers.php                [MODIFY]
    ├── navigation/
    │   ├── admin_navigation.php                  [MODIFY]
    │   └── roles_navigation.json                 [MODIFY]
    └── js/
        └── modules/
            ├── attendance-approval.js            [NEW]
            └── attendance-hr-approval.js         [NEW]
```

**Totals:** 12 new files + 2 new folders + 8 modified files + 2 SQL migrations.

---

## 13. Support / troubleshooting

| Issue | Check |
|-------|--------|
| Duplicate column SQL error | Use the safe SQL files in Section 2 (they skip existing columns) |
| Invalid JSON on DataTable | Ensure all action PHP files uploaded; check PHP error log |
| Supervisor menu missing | User needs `EmployeeID` in session + employees with `Supervisor` = their ID |
| HR menu missing | User needs HR / Admin / Super Admin role |
| Approve works but error alert | Ensure latest `attendance_controller.php` + approve action files uploaded |
| Table not refreshing | Hard refresh; ensure `attendance-approval.js` / `attendance-hr-approval.js` uploaded |

---

**End of production deployment guide.**
