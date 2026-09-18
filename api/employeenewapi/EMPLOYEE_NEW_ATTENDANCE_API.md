# TechXpert — Employee Attendance API v2 (Branch Locations)

**Base URL:** `https://techxpertindia.in/api/employeenewapi/`

**Mobile developers:** See **[MOBILE_API_CALL_SEQUENCE.md](./MOBILE_API_CALL_SEQUENCE.md)** for step-by-step API call order (which API first, second, third for punch in and punch out).

This API extends the standard employee attendance flow with split location rules:

- **Check-in:** assigned **employee location only** (from employee settings)
- **Check-out:** **employee location or any active branch** (from `branch` table)

All endpoints use:
- **Method:** `POST`
- **Header:** `Content-Type: application/json`
- **Body:** JSON (raw)

---

## What is different from `/api/` attendance APIs?

| Feature | Old `/api/` | New `/api/employeenewapi/` |
|---------|-------------|----------------------------|
| Check-in: employee location | Yes | Yes |
| Check-in: branch locations | No | **No** |
| Check-out: employee + branches | No | **Yes** |
| Branch list in policy | No | **Yes** (for check-out) |

**Radius rule:** Branch locations use the employee's `AttendanceRadiusMeters` setting from admin.

**Check-in:** GPS at **assigned employee location only** when `boundaryEnabled = true`.

**Check-out:** GPS at **employee location or any branch** when `boundaryEnabled = true`.

---

## Response format

| Field     | Type    | Description                          |
|-----------|---------|--------------------------------------|
| `error`   | boolean | `false` = success, `true` = failed   |
| `message` | string  | Message to show the user             |
| `data`    | object  | Extra details (when available)       |

---

## Recommended mobile flow

```
App open
  └─► get_attendance_status.php

Load branch map markers (optional)
  └─► get_branch_attendance_locations.php

Check-in
  └─► Get device GPS
  └─► validate_location.php  (Action: checkin)
  └─► punch_in.php

Check-out
  └─► get_checkout_eligibility.php  (optional)
  └─► validate_location.php  (Action: checkout)
  └─► punch_out.php
```

---

## API list

| # | Endpoint | Purpose |
|---|----------|---------|
| 1 | `get_attendance_status.php` | Today's punch status + employee + branch location policy |
| 2 | `get_location_policy.php` | Location policy with branch list |
| 3 | `get_branch_attendance_locations.php` | All active branch punch locations |
| 4 | `validate_location.php` | Validate before punch (employee or branch location) |
| 5 | `get_checkout_eligibility.php` | Can check-out? + hours worked |
| 6 | `punch_in.php` | Final check-in |
| 7 | `punch_out.php` | Final check-out |

---

## 1. Get today's attendance status

**URL:** `https://techxpertindia.in/api/employeenewapi/get_attendance_status.php`

### Request

```json
{
  "EmployeeID": 123
}
```

### Success response (example)

```json
{
  "error": false,
  "message": "Records fetched",
  "EmployeeName": "John Doe",
  "EmployeeDesignation": "Technician",
  "InTimeStatus": 0,
  "OutTimeStatus": 0,
  "data": {
    "attendace_records": "",
    "location_policy": {
      "EmployeeID": 123,
      "IsAllowLocationBoundary": 1,
      "AttendanceLatitude": "28.613939",
      "AttendanceLongitude": "77.209023",
      "AttendanceRadiusMeters": 100,
      "boundaryEnabled": true,
      "allowBranchLocations": true,
      "locationSource": "employee_and_branches",
      "branchLocations": [
        {
          "BranchID": 5,
          "BranchSite": "Delhi HQ",
          "BranchCode": "DL01",
          "Latitude": "28.613939",
          "Longitude": "77.209023",
          "BranchCity": "New Delhi",
          "BranchState": "Delhi",
          "AttendanceRadiusMeters": 100
        }
      ]
    },
    "checkout_eligibility": {
      "canCheckout": false,
      "checkedIn": false,
      "message": "You must check in before checking out."
    }
  }
}
```

---

## 2. Get location policy

**URL:** `https://techxpertindia.in/api/employeenewapi/get_location_policy.php`

Same payload as status API. Returns only `data.location_policy` fields (employee + branches).

---

## 3. Get branch attendance locations

**URL:** `https://techxpertindia.in/api/employeenewapi/get_branch_attendance_locations.php`

Use this to show all branch markers on the mobile map.

### Request

```json
{
  "EmployeeID": 123
}
```

### Success response

```json
{
  "error": false,
  "message": "Branch attendance locations fetched",
  "data": {
    "EmployeeID": 123,
    "AttendanceRadiusMeters": 100,
    "boundaryEnabled": true,
    "employeeLocation": {
      "AttendanceLatitude": "28.613939",
      "AttendanceLongitude": "77.209023"
    },
    "branchLocations": [
      {
        "BranchID": 5,
        "BranchSite": "Delhi HQ",
        "BranchCode": "DL01",
        "Latitude": "28.613939",
        "Longitude": "77.209023",
        "BranchCity": "New Delhi",
        "BranchState": "Delhi",
        "AttendanceRadiusMeters": 100
      }
    ],
    "totalBranches": 1
  }
}
```

---

## 4. Validate location

**URL:** `https://techxpertindia.in/api/employeenewapi/validate_location.php`

### Check-in request

```json
{
  "EmployeeID": 123,
  "Latitude": 28.614100,
  "Longitude": 77.209200,
  "Action": "checkin"
}
```

### Success — matched branch

```json
{
  "error": false,
  "message": "Location verified at branch Delhi HQ (45 m away).",
  "data": {
    "EmployeeID": 123,
    "boundaryEnabled": true,
    "allowed": true,
    "action": "checkin",
    "locationCheckRequired": true,
    "allowBranchLocations": true,
    "branchLocations": [],
    "matchedLocation": {
      "locationType": "branch",
      "BranchID": 5,
      "BranchSite": "Delhi HQ",
      "distanceMeters": 45,
      "AttendanceRadiusMeters": 100,
      "Latitude": "28.613939",
      "Longitude": "77.209023"
    },
    "distanceMeters": 45
  }
}
```

### Error — outside all locations

```json
{
  "error": true,
  "message": "You are outside all allowed attendance locations (250 m from nearest location, maximum allowed 100 m)",
  "data": {
    "allowed": false,
    "boundaryEnabled": true,
    "action": "checkin"
  }
}
```

### Check-out request (GPS required if boundary enabled)

```json
{
  "EmployeeID": 123,
  "Latitude": 28.613939,
  "Longitude": 77.209023,
  "Action": "checkout"
}
```

---

## 5. Punch in

**URL:** `https://techxpertindia.in/api/employeenewapi/punch_in.php`

Call after `validate_location.php` succeeds.

### Request

```json
{
  "EmployeeID": 123,
  "Latitude": 28.614100,
  "Longitude": 77.209200,
  "imageData": "BASE64_ENCODED_JPEG_STRING_WITHOUT_PREFIX"
}
```

### Success response

```json
{
  "error": false,
  "message": "Attendance punched in",
  "data": {
    "matchedLocation": {
      "locationType": "branch",
      "BranchID": 5,
      "BranchSite": "Delhi HQ",
      "distanceMeters": 45,
      "AttendanceRadiusMeters": 100,
      "Latitude": "28.613939",
      "Longitude": "77.209023"
    }
  }
}
```

---

## 6. Punch out

**URL:** `https://techxpertindia.in/api/employeenewapi/punch_out.php`

Location radius check on check-out when `boundaryEnabled = true` (employee location or any branch).

### Request

```json
{
  "EmployeeID": 123,
  "Latitude": 28.614100,
  "Longitude": 77.209200,
  "imageData": "BASE64_ENCODED_JPEG_STRING_WITHOUT_PREFIX"
}
```

---

## Business rules

1. If `boundaryEnabled = false`, GPS check is skipped on check-in and check-out.
2. If `boundaryEnabled = true`, **check-in** is allowed only within radius of the **assigned employee location**.
3. If `boundaryEnabled = true`, **check-out** is allowed within radius of **employee location or any active branch**.
4. Branch radius uses the employee's `AttendanceRadiusMeters` value.
5. Only **active** branches (`IsActive = 1`) with non-empty coordinates are included for check-out.
6. Policy fields: `checkinLocationRule` = `employee_only`, `checkoutLocationRule` = `employee_and_branches`.
7. Attendance is stored in the same `employee_attendance` table as the old API.

---

## Branch SQL reference

Branches are loaded from:

```sql
SELECT ID, BranchSite, BranchCode, Latitude, Longitude, BranchCity, BranchState
FROM branch
WHERE IsActive = 1
  AND Latitude IS NOT NULL AND TRIM(Latitude) != '' AND Latitude != '0'
  AND Longitude IS NOT NULL AND TRIM(Longitude) != '' AND Longitude != '0'
ORDER BY BranchSite ASC;
```

Ensure each branch has correct `Latitude` and `Longitude` in admin **Branch** module.

---

## cURL quick test

```bash
curl -X POST "https://techxpertindia.in/api/employeenewapi/get_branch_attendance_locations.php" \
  -H "Content-Type: application/json" \
  -d "{\"EmployeeID\":123}"
```

```bash
curl -X POST "https://techxpertindia.in/api/employeenewapi/validate_location.php" \
  -H "Content-Type: application/json" \
  -d "{\"EmployeeID\":123,\"Latitude\":28.613939,\"Longitude\":77.209023,\"Action\":\"checkin\"}"
```

---

*Document version: May 2026 — TechXpert Employee Attendance with Branch Locations*
