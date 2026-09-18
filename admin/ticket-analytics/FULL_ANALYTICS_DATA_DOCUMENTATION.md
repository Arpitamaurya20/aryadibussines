# TechXpert Ticket Analytics - Full Data Documentation

This document defines all analytics that can be produced from the current ticket system data for leadership/CFO dashboards.

## 1) Core Data Sources

## Primary Ticket Tables
- `corporate_tickets`
  - Covers: `R&M`, `Projects`, `Supply`, `AMC` (shown as `AMC Breakdown`)
  - Key fields:
    - Identifiers: `ID`, `TicketID`
    - Classification: `Type`, `Service`, `Subservice`, `Priority`, `Status`
    - Ownership: `CorporateID`, `BranchID`, `AssignedTo`, `Technician`
    - Timeline: `CreatedDate`, `CreatedTime`, `DueDate`, `CloseDate`, `CloseTime`
    - Finance: `CustumerPrice`, `ExpensePrice`, `CallType`
    - Governance: `IsActive`, `Remarks`
- `ppm_tickets`
  - Covers: `PPM`
  - Key fields:
    - Identifiers: `ID`, `TicketID`
    - Ownership: `CorporateID`, `BranchID`, `AssignedTo`
    - Timeline: `PPMDate`, `CreatedDate`, `CreatedTime`, `DueDate`, `CloseDate`, `CloseTime`
    - Status: `Status`, `IsActive`

## Dimension / Master Tables
- `company`: company/account metadata (`ID`, `CompanyName`, etc.)
- `branch`: branch/site metadata (`ID`, `BranchSite`, city/state, etc.)
- `employees`: assigned employee metadata (`ID`, `Name`, etc.)

## Optional Detail Tables (for deeper analytics)
- `corporate_ticket_status_history`: status movement timeline
- `corporate_tickets_finance`: additional finance records
- `project_tasks`: task-level progress for `Projects` / `Supply`
- `ticket_media`, `customer_rating`, quotation tables (process quality views)

---

## 2) Ticket Type Standardization

For analytics consistency:
- `PPM` -> `PPM`
- `R&M` -> `R&M`
- `Projects` -> `Projects`
- `Supply` -> `Supply`
- `AMC` -> `AMC Breakdown`

This normalized view is used across KPI cards, charts, filters, and drilldowns.

---

## 3) Global Filters Supported

Current filter model supports:
- Date range: `start_date`, `end_date`
- Ticket types: single or multi-select (`ticket_types`)
- Status filter
- Company filter (`company_id`)
- Branch filter (`branch_id`)

Default window: last 30 days (if no dates provided).

---

## 4) KPI Metrics You Can Show

## Volume KPIs
- Total tickets
- Open tickets
- Closed tickets
- Closure rate (%)

## Service Level KPIs
- Average TAT (hours)
  - Closed ticket resolution time from created date to close date
- SLA breach count
  - Closed after due date OR still open past due date
- SLA breach %

## Backlog Health KPIs
- Aging backlog 0-2 days
- Aging backlog 3-7 days
- Aging backlog 8-15 days
- Aging backlog 16+ days
- Total backlog count

## Finance KPIs
- Total customer price (where available)
- Total expense price
- Gross margin proxy: customer price - expense price
- Cost per ticket (expense / total tickets)

---

## 5) Trend & Time-Series Analytics

## Monthly Trends
- Opened tickets by month (based on created date)
- Closed tickets by month (based on close date)
- Opened vs closed line chart

## Ticket Type Trends
- Month-wise trend by each type (`PPM`, `R&M`, `Projects`, `Supply`, `AMC Breakdown`)
- Share (%) of each type over time

## Status Trends
- Status distribution (current period)
- Month-wise status volume trend (if desired)
- Status transition flow (from history table)

## SLA / Aging Trends
- Monthly SLA breach trend
- Monthly average TAT trend
- Aging bucket trend over time

---

## 6) Breakdown Views (Cross-Sections)

## By Ticket Type
- Count
- Closed count
- Open count
- Closure %
- Expense total
- Avg TAT

## By Status
- Count by status
- Share of total by status

## By Company
- Ticket volume by company
- SLA breach by company
- Expense by company

## By Branch / Location
- Ticket volume by branch
- Backlog by branch
- SLA breach by branch

## By Assignee / Team
- Assigned load
- Closed volume
- Avg TAT
- SLA breach contribution

## By Priority
- Critical / non-critical distribution
- Priority-wise closure performance

---

## 7) Drilldown Data Columns (Detailed Grid)

A complete drilldown table can include:
- Ticket code (`TicketID`)
- Ticket type
- Status
- Priority
- Company name
- Branch name
- Assigned employee
- Created date
- Due date
- Closed date
- TAT (calculated)
- SLA flag (calculated)
- Expense price
- Customer price
- Margin (calculated)
- Service / subservice
- Remarks

---

## 8) Advanced Analytics Possible (From Existing DB)

## Workflow / Process Analytics
- Status funnel (Raised -> Assigned -> In Progress -> Closed)
- Stage drop-off and bottlenecks
- Average time spent per stage (from history table)

## Projects / Supply Progress Analytics
- Task completion ratio from `project_tasks`
- Tickets closed with incomplete tasks
- Project execution lag metrics

## Quotation / Approval Analytics
- Quote pending, approved, rejected trends
- Quote-to-close conversion time

## Quality Analytics
- Reopen / cancel rates
- Ticket image/report completion compliance
- Customer rating by type/branch/assignee

## Productivity Analytics
- Tickets handled per employee per period
- Normalized productivity vs ticket complexity
- Repeat issue hotspots

---

## 9) CFO/Leadership Dashboard Pages Recommended

## Page 1: Executive Summary (One Screen)
- KPI cards
- Open vs closed trend
- Type mix pie chart
- Status distribution chart
- Top 5 backlog branches
- Top 5 cost centers (company/branch)

## Page 2: Operational Performance
- SLA and TAT trends
- Assignee/team performance
- Aging and overdue analysis

## Page 3: Financial View
- Expense vs customer value trend
- Cost by ticket type
- Cost by company/branch
- Margin proxy and high-cost outliers

## Page 4: Drilldown Explorer
- Search + filter + sortable table
- CSV export
- Drill-through links to ticket detail

---

## 10) API Outputs Already Implemented

Current module endpoints:
- `api/kpi_summary.php`
- `api/trend_series.php`
- `api/type_breakdown.php`
- `api/status_breakdown.php`
- `api/drilldown.php`

These cover:
- KPI cards
- Opened/Closed trend
- Type pie
- Status doughnut
- Detailed records table

---

## 11) Data Quality Notes / Constraints

- Date fields are stored as strings in legacy tables and are normalized via SQL date parsing.
- `CustumerPrice` / `ExpensePrice` may be blank for some records.
- PPM tickets do not always carry the same finance fields as corporate tickets.
- Some statuses are free-text variants; optional status mapping improves consistency.
- `IsActive` filtering is essential to avoid archived/noise records.

---

## 12) Full "Possible Data" Checklist

Use this as a complete capability list:
- Volume: total/open/closed/canceled/submitted/assigned/in progress
- Rates: closure %, SLA breach %, cancel %, quote conversion %
- Time: TAT avg/median/p90, aging, stage time
- Mix: by type/status/priority/company/branch/assignee/service/subservice
- Trend: daily/weekly/monthly with period-over-period comparison
- Finance: expense, customer value, margin proxy, cost per ticket
- Risk: overdue backlog, high-cost tickets, high-delay branches
- Quality: closure compliance, report/media completeness, rating trends
- Operations: team load balancing, workload spikes, execution hotspots

---

## 13) Suggested Next Additions

If you want "all possible data" visible in product UI, next practical additions are:
- Company and branch dropdown filters on main page
- Status multi-checkbox filters
- KPI comparison vs previous period
- Export endpoints (CSV) for KPI/trend/drilldown
- Saved dashboard presets (Monthly, Quarterly, YTD, Custom)

