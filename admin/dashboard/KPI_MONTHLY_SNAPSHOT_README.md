# Employee KPI monthly snapshot

## Table: `employee_kpi_monthly_snapshot`

One row per **employee per calendar month** (unique: `EmployeeID` + `KpiYear` + `KpiMonth`).

- **Insert** when KPI is sent for the first time in that month  
- **Update** when KPI is sent again for the same employee + month (latest data overwrites)

Saved automatically from `employee_kpi_whatsapp.php` (used by `kpi_worker.php` and `send-kpi-whatsapp.php`).

## HRMS salary slip

On **HRMS → Payroll**, check **Include KPI performance (this month)** when generating a single employee slip. The slip reads this table (`PayableSalary`, overall KPI %, etc.) and shows a KPI summary block; **Net Payable** uses `PayableSalary`. Uncheck and regenerate with overwrite to remove KPI from the slip.

### Columns saved

| Group | Fields |
|--------|--------|
| Employee | ID, Name, Designation, Contact, Date range |
| Assignment | Total, Within 1hr, After 1hr, Reassign, % |
| Quotation | Total, Within 48hr, After 48hr, Pending, Not approved 48hr, AMC, % |
| Closed | Total, Within 24hr, After 24hr, Target, % |
| Attendance | Working days, Present, Absent, % |
| Overall | Overall KPI %, Full salary, Payable salary, Salary % |
| Meta | Chart URL, WhatsApp sent flag, response, SentAt |

## Excel export (end of month)

Open in browser (logged in as admin):

```
/admin/dashboard/ajax/export_employee_kpi_saved.php?year=2026&month=4
```

- `month` optional — omit to export all months in that year  
- Downloads CSV (opens in Excel)

## Manual SQL install (optional)

Table auto-creates on first send. Or run:

`admin/dashboard/sql/employee_kpi_monthly_snapshot.sql`
