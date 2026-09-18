# Ticket Analytics Module

This module is a standalone analytics area for leadership dashboards and does not modify existing ticket action flows.

## Ticket Sources
- `corporate_tickets` for `R&M`, `Projects`, `Supply`, and `AMC` (displayed as `AMC Breakdown`)
- `ppm_tickets` for `PPM`

## Main Pages
- `index.php`: CFO one-screen KPIs + trends
- `trends.php`: trend-focused view
- `drilldown.php`: filtered ticket list

## APIs
- `api/kpi_summary.php`
- `api/trend_series.php`
- `api/type_breakdown.php`
- `api/drilldown.php`

## Notes
- Filters default to last 30 days.
- Dates are normalized from string date fields using `STR_TO_DATE`.
- Current implementation is read-only analytics.
