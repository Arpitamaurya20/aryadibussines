# Dynamic PPM Module (New Flow)

This module is a new and isolated implementation for dynamic PPM checklist handling.
It does not change the existing `admin/ppm-ticket` action files.

## Why this module

- Different clients require different checklist formats for the same category.
- One category can have multiple checklist versions/formats.
- Ticket report should be generated from mapped checklist dynamically.
- Avoid creating one database table per category checklist.

## Folder Structure

- `admin/dynamic-ppm/sql/create_dynamic_ppm_tables.sql`
  - New dynamic master + mapping + report tables.
- `admin/dynamic-ppm/controller/dynamic_ppm_controller.php`
  - Reusable backend functions for checklist resolve and report save.
- `admin/dynamic-ppm/view-master-checklist.php`
  - Admin portal setup screen (master, items, company mapping).
- `admin/dynamic-ppm/action/save_checklist_master.php`
- `admin/dynamic-ppm/action/save_checklist_item.php`
- `admin/dynamic-ppm/action/save_company_checklist_mapping.php`
- `api/dynamic-ppm/get_ppm_ticket_flow.php`
  - Mobile API: decide dynamic vs legacy flow for a ticket.
- `api/dynamic-ppm/get_dynamic_ppm_checklist_form.php`
  - Mobile API: get mapped checklist and prefilled data by ticket.
- `api/dynamic-ppm/submit_dynamic_ppm_service_report.php`
  - Mobile API: save/update dynamic report for a ticket.

## Portal Setup (First Time)

1. Run SQL: `admin/dynamic-ppm/sql/create_dynamic_ppm_tables.sql`
2. Open portal page: `admin/dynamic-ppm/view-master-checklist.php`
3. Complete in this order:
   - Create Checklist Master
   - Add Checklist Items for that checklist
   - Map Company + Category to that checklist
4. Raise PPM ticket normally (existing flow), then mobile app will load mapped dynamic checklist.
5. If company/category is **not mapped**, mobile app must use legacy PPM APIs (existing category-wise flow).

## Mobile Flow Decision

1. Call `api/dynamic-ppm/get_ppm_ticket_flow.php` with `TicketID`
2. If `use_dynamic_ppm = 1` → use dynamic APIs
3. If `use_legacy_ppm = 1` → use legacy APIs from `legacy_flow` object (example: `post_hvac_service_report.php`, `post_ep_service_report.php`)

`get_dynamic_ppm_checklist_form.php` also returns `use_legacy_ppm = 1` when no mapping exists (instead of hard error).

## Data Model Summary

- `ppm_dynamic_checklist_master`
  - Checklist header (code, category, version).
- `ppm_dynamic_checklist_items`
  - Checklist fields/items (input type, options, mandatory, sort).
- `ppm_dynamic_company_checklist_map`
  - Company + category mapped to one active checklist.
- `ppm_dynamic_service_report`
  - One report per ticket, includes common fields and general details JSON.
- `ppm_dynamic_service_report_items`
  - Per checklist item responses.

## Rollout Plan (Suggested)

1. Run SQL file in staging and production.
2. Build admin screens in this module:
   - Checklist master create/edit.
   - Checklist item builder.
   - Company checklist mapping.
3. Update mobile app:
   - call `get_dynamic_ppm_checklist_form.php` before opening checklist form.
   - submit to `submit_dynamic_ppm_service_report.php`.
4. Keep old category-specific APIs for fallback during migration.
5. PDF generation can read data from dynamic report tables by `TicketID`.

## Existing Process Compatibility

- Existing tables (`ppm_ep_service_report`, `ppm_hvac_service_report`, etc.) remain unchanged.
- Existing image upload flow (`ppm_ticket_media`) can continue as-is.
- Existing ticket raise process in `ppm_tickets` remains unchanged.

