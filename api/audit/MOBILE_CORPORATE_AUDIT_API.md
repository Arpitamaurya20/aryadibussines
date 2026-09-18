# Corporate Audit Mobile API

Base URL examples:
- Production: `https://techxpertindia.in/api/audit/`
- Local: `http://localhost/projects/techxpert/api/audit/`

All endpoints return JSON:
```json
{
  "error": false,
  "message": "...",
  "total_records": 3,
  "data": []
}
```

Supports **POST** (JSON body), **GET** (query params), or both.

---

## Recommended flow for mobile app

### Option A – Single bootstrap (best for offline / one call)
```
GET/POST  get_mobile_corporate_audit.php
```
Returns full tree: **audits → sub_audits → checklists** with `field_meta` for dynamic forms.

### Option B – Step-by-step navigation
1. `get_master_audits.php` – show audit tiles (Electrical, Energy, etc.)
2. `get_master_sub_audits.php?master_audit_id=1` – show sub modules (UPS, Power Supply, etc.)
3. `get_sub_audit_detail.php?sub_audit_id=3` – load dynamic checklist form

---

## Endpoints

| File | Purpose |
|------|---------|
| `get_mobile_corporate_audit.php` | **Primary** – full mobile payload in one call |
| `get_audit_full_hierarchy.php` | Full nested tree (audits array at root) |
| `get_master_audits.php` | List master audits only |
| `get_master_audit_detail.php` | One audit + sub audits + checklists |
| `get_master_sub_audits.php` | Sub audits (filter by `master_audit_id`) |
| `get_sub_audit_detail.php` | One sub audit + dynamic checklist form |
| `get_audit_checklist.php` | Flat checklist list |

---

## 1. get_mobile_corporate_audit.php

**Request**
```json
{}
```
Or GET with no params.

**Response**
```json
{
  "error": false,
  "message": "Corporate audit mobile data fetched.",
  "api_version": "1.0",
  "total_records": 2,
  "data": {
    "audits": [
      {
        "id": 1,
        "audit_name": "Electrical Audit",
        "description": "...",
        "icon_class": "fa-solid fa-bolt",
        "icon_image_url": "https://techxpertindia.in/admin/media/corporate-audit/...",
        "sort_order": 1,
        "is_active": 1,
        "sub_audit_count": 2,
        "checklist_count": 8,
        "sub_audits": [
          {
            "id": 3,
            "master_audit_id": 1,
            "master_audit_name": "Electrical Audit",
            "sub_audit_name": "Power Supply System",
            "icon_image_url": "...",
            "checklist_count": 5,
            "checklists": [
              {
                "id": 10,
                "sub_audit_id": 3,
                "checkpoint_name": "Input voltage at main panel",
                "description": "Measure line voltage",
                "field_type": "number",
                "ideal_value": "230",
                "min_value": "210",
                "max_value": "250",
                "unit": "V",
                "options": [],
                "help_text": "Use calibrated meter",
                "sort_order": 1,
                "is_mandatory": 1,
                "is_active": 1,
                "field_meta": {
                  "input_type": "number",
                  "keyboard": "numeric",
                  "multiline": false,
                  "options": [],
                  "placeholder": "230 V",
                  "validation": {
                    "required": true,
                    "min_value": "210",
                    "max_value": "250",
                    "ideal_value": "230",
                    "unit": "V"
                  }
                }
              }
            ]
          }
        ]
      }
    ],
    "summary": {
      "master_audit_count": 2,
      "sub_audit_count": 5,
      "checklist_count": 24
    }
  }
}
```

---

## 2. get_master_audits.php

List all master audit modules.

**Optional params:** `include_inactive=1`

---

## 3. get_master_audit_detail.php

**Required:** `master_audit_id` or `id`

```json
{ "master_audit_id": 1 }
```

Returns one audit with nested `sub_audits` and each sub audit's `checklists`.

---

## 4. get_master_sub_audits.php

**Optional:** `master_audit_id` (0 or omit = all sub audits)

```json
{ "master_audit_id": 1 }
```

Each item includes `master_audit_name`, `checklist_count`, icons.

---

## 5. get_sub_audit_detail.php

**Required:** `sub_audit_id` or `id`

```json
{ "sub_audit_id": 3 }
```

**Response structure**
```json
{
  "data": {
    "master_audit": { "id": 1, "audit_name": "Electrical Audit", ... },
    "sub_audit": { "id": 3, "sub_audit_name": "Power Supply System", ... },
    "checklists": [ ... ],
    "dynamic_form": {
      "sub_audit_id": 3,
      "fields": [ ... same as checklists with field_meta ... ]
    }
  }
}
```

Use `dynamic_form.fields` to render the mobile form dynamically.

---

## 6. get_audit_checklist.php

Flat list of checkpoints.

| Param | Description |
|-------|-------------|
| `sub_audit_id` | Checklists for one sub audit |
| `master_audit_id` | All checklists under a master audit |
| `all=1` | All checklists in system |

---

## Dynamic field types (`field_type`)

| field_type | Mobile `field_meta.input_type` | Use |
|------------|-------------------------------|-----|
| `text` | text | Short text |
| `number` | number | Integer input |
| `decimal` | decimal | Decimal input |
| `textarea` | textarea | Long text (multiline) |
| `select` | select | Dropdown – use `options` array |
| `checkbox` | checkbox | Yes/No |
| `date` | date | Date picker |

Render each checklist row using `field_meta` + `validation` for min/max/ideal/required.

---

## Icons

- `icon_class` – Font Awesome class for in-app icon font
- `icon_image_url` – Full URL if image uploaded in admin

---

## Example Ionic / Angular call

```typescript
const url = 'https://techxpertindia.in/api/audit/get_mobile_corporate_audit.php';

this.http.post(url, {}).subscribe((res: any) => {
  if (!res.error) {
    this.audits = res.data.audits;
    this.summary = res.data.summary;
  }
});
```

```typescript
// Load form for one sub audit
const url = 'https://techxpertindia.in/api/audit/get_sub_audit_detail.php';
this.http.post(url, { sub_audit_id: 3 }).subscribe((res: any) => {
  this.formFields = res.data.dynamic_form.fields;
});
```

---

## CORS

Enabled via `common_api_header.php` (`Access-Control-Allow-Origin: *`).
