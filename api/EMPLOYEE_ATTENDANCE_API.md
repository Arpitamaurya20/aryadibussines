# TechXpert — Employee Attendance API (Mobile)

**Base URL:** `https://techxpertindia.in/api/`

All endpoints use:
- **Method:** `POST`
- **Header:** `Content-Type: application/json`
- **Body:** JSON (raw)

---

## Response format (all APIs)

| Field     | Type    | Description                          |
|-----------|---------|--------------------------------------|
| `error`   | boolean | `false` = success, `true` = failed     |
| `message` | string  | Message to show the user               |
| `data`    | object  | Extra details (when available)       |

---

## Business rules

| Action      | Location check (lat/long/radius) | Time rule                          |
|-------------|----------------------------------|------------------------------------|
| **Check-in**  | Yes, if `boundaryEnabled = true` — **assigned employee location only** | None                               |
| **Check-out** | Yes, if `boundaryEnabled = true` — **employee location or any branch** | Any time after check-in (half-day OK) |

---

## Recommended mobile flow

```
App open
  └─► get_employee_attendance_status.php

Check-in
  └─► Get device GPS
  └─► validate_employee_attendance_location.php  (Action: checkin)
  └─► punch_in_employee_attendance.php

Check-out
  └─► get_employee_checkout_eligibility.php  (optional)
  └─► validate_employee_attendance_location.php  (Action: checkout)
  └─► punch_out_employee_attendance.php
```

---

## API list

| # | Endpoint | Purpose |
|---|----------|---------|
| 1 | `get_employee_attendance_status.php` | Today’s punch status + location policy |
| 2 | `get_employee_attendance_location_policy.php` | Location policy only |
| 3 | `validate_employee_attendance_location.php` | Validate before punch (check-in / check-out) |
| 4 | `get_employee_checkout_eligibility.php` | Can check-out? + hours worked |
| 5 | `punch_in_employee_attendance.php` | Final check-in |
| 6 | `punch_out_employee_attendance.php` | Final check-out |

---

## 1. Get today’s attendance status

**URL:** `https://techxpertindia.in/api/get_employee_attendance_status.php`

### Request payload

```json
{
  "EmployeeID": 123
}
```

### Success response (checked in, not out)

```json
{
  "error": false,
  "message": "Records fetched",
  "EmployeeName": "John Doe",
  "EmployeeDesignation": "Technician",
  "InTimeStatus": 1,
  "OutTimeStatus": 0,
  "data": {
    "attendace_records": {
      "ID": "456",
      "EmployeeID": "123",
      "RecordDate": "2026-05-15",
      "InTime": "09:15:00",
      "OutTime": "",
      "Latitude": "28.613939",
      "Longitude": "77.209023",
      "CheckinImage": "ea_checkin_123_2026-05-15abc.jpg"
    },
    "location_policy": {
      "EmployeeID": 123,
      "IsAllowLocationBoundary": 1,
      "AttendanceLatitude": "28.613939",
      "AttendanceLongitude": "77.209023",
      "AttendanceRadiusMeters": 100,
      "boundaryEnabled": true
    },
    "checkout_eligibility": {
      "canCheckout": true,
      "hoursWorked": 4.5,
      "minutesWorked": 270,
      "message": "You can check out (half-day or full-day). Hours so far: 4.5",
      "checkedIn": true,
      "alreadyCheckedOut": false,
      "checkInTime": "09:15:00"
    }
  }
}
```

### Success response (not checked in today)

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
    "location_policy": { "boundaryEnabled": false, "EmployeeID": 123 },
    "checkout_eligibility": {
      "canCheckout": false,
      "checkedIn": false,
      "message": "You must check in before checking out."
    }
  }
}
```

### Error response

```json
{
  "error": true,
  "message": "Missing User Fields!"
}
```

### cURL test

```bash
curl -X POST "https://techxpertindia.in/api/get_employee_attendance_status.php" \
  -H "Content-Type: application/json" \
  -d "{\"EmployeeID\":123}"
```

---

## 2. Get location policy

**URL:** `https://techxpertindia.in/api/get_employee_attendance_location_policy.php`

Use when you only need geofence settings (not full attendance status).

### Request payload

```json
{
  "EmployeeID": 123
}
```

### Success response

```json
{
  "error": false,
  "message": "Attendance location policy fetched",
  "data": {
    "EmployeeID": 123,
    "IsAllowLocationBoundary": 1,
    "AttendanceLatitude": "28.613939",
    "AttendanceLongitude": "77.209023",
    "AttendanceRadiusMeters": 100,
    "boundaryEnabled": true
  }
}
```

If `boundaryEnabled` is `false`, skip GPS radius check on check-in.

### cURL test

```bash
curl -X POST "https://techxpertindia.in/api/get_employee_attendance_location_policy.php" \
  -H "Content-Type: application/json" \
  -d "{\"EmployeeID\":123}"
```

---

## 3. Validate location (before punch)

**URL:** `https://techxpertindia.in/api/validate_employee_attendance_location.php`

### 3A — Check-in (GPS required if boundary enabled)

#### Request payload

```json
{
  "EmployeeID": 123,
  "Latitude": 28.614100,
  "Longitude": 77.209200,
  "Action": "checkin"
}
```

| Field        | Required | Description                    |
|--------------|----------|--------------------------------|
| `EmployeeID` | Yes      | Employee primary key (`employees.ID`) |
| `Latitude`   | Yes*     | Device latitude (*if boundary enabled) |
| `Longitude`  | Yes*     | Device longitude               |
| `Action`     | Yes      | `"checkin"` or `"checkout"`    |

#### Success — inside allowed area

```json
{
  "error": false,
  "message": "Location verified (45 m from work location).",
  "data": {
    "EmployeeID": 123,
    "IsAllowLocationBoundary": 1,
    "AttendanceLatitude": "28.613939",
    "AttendanceLongitude": "77.209023",
    "AttendanceRadiusMeters": 100,
    "boundaryEnabled": true,
    "action": "checkin",
    "locationCheckRequired": true,
    "allowed": true,
    "currentLatitude": 28.6141,
    "currentLongitude": 77.2092,
    "distanceMeters": 45
  }
}
```

#### Error — outside allowed area

```json
{
  "error": true,
  "message": "You are outside the allowed attendance area (250 m away, maximum allowed 100 m)",
  "data": {
    "allowed": false,
    "boundaryEnabled": true,
    "action": "checkin"
  }
}
```

#### cURL test (check-in)

```bash
curl -X POST "https://techxpertindia.in/api/validate_employee_attendance_location.php" \
  -H "Content-Type: application/json" \
  -d "{\"EmployeeID\":123,\"Latitude\":28.6141,\"Longitude\":77.2092,\"Action\":\"checkin\"}"
```

---

### 3B — Check-out (GPS required if boundary enabled)

#### Request payload

```json
{
  "EmployeeID": 123,
  "Latitude": 28.613939,
  "Longitude": 77.209023,
  "Action": "checkout"
}
```

`Latitude` and `Longitude` are **required** for check-out when `boundaryEnabled = true`. Check-out allows **employee location or any branch**; check-in allows **employee location only**.

#### Success

```json
{
  "error": false,
  "message": "You can check out (half-day or full-day). Hours so far: 4.5",
  "data": {
    "EmployeeID": 123,
    "boundaryEnabled": true,
    "action": "checkout",
    "locationCheckRequired": false,
    "allowed": true,
    "canCheckout": true,
    "hoursWorked": 4.5,
    "minutesWorked": 270,
    "checkedIn": true,
    "checkInTime": "09:15:00"
  }
}
```

#### Error — not checked in

```json
{
  "error": true,
  "message": "You must check in before checking out.",
  "data": {
    "allowed": false,
    "canCheckout": false,
    "checkedIn": false
  }
}
```

#### cURL test (check-out)

```bash
curl -X POST "https://techxpertindia.in/api/validate_employee_attendance_location.php" \
  -H "Content-Type: application/json" \
  -d "{\"EmployeeID\":123,\"Action\":\"checkout\"}"
```

---

## 4. Check-out eligibility (optional)

**URL:** `https://techxpertindia.in/api/get_employee_checkout_eligibility.php`

### Request payload

```json
{
  "EmployeeID": 123
}
```

### Success response

```json
{
  "error": false,
  "message": "You can check out (half-day or full-day). Hours so far: 4.5",
  "data": {
    "canCheckout": true,
    "hoursWorked": 4.5,
    "minutesWorked": 270,
    "message": "You can check out (half-day or full-day). Hours so far: 4.5",
    "checkedIn": true,
    "alreadyCheckedOut": false,
    "checkInTime": "09:15:00"
  }
}
```

### cURL test

```bash
curl -X POST "https://techxpertindia.in/api/get_employee_checkout_eligibility.php" \
  -H "Content-Type: application/json" \
  -d "{\"EmployeeID\":123}"
```

---

## 5. Punch in (check-in)

**URL:** `https://techxpertindia.in/api/punch_in_employee_attendance.php`

Call **after** `validate_employee_attendance_location` succeeds (when boundary is enabled).

### Request payload

```json
{
  "EmployeeID": 123,
  "Latitude": 28.614100,
  "Longitude": 77.209200,
  "imageData": "BASE64_ENCODED_JPEG_STRING_WITHOUT_PREFIX"
}
```

| Field        | Required | Description |
|--------------|----------|-------------|
| `EmployeeID` | Yes      | Employee ID |
| `Latitude`   | Yes*     | *Required if location boundary enabled |
| `Longitude`  | Yes*     | *Required if location boundary enabled |
| `imageData`  | No       | Base64 selfie. **Do not** include `data:image/jpeg;base64,` prefix |

### Success response

```json
{
  "error": false,
  "message": "Attendance punched in"
}
```

### Error responses

```json
{
  "error": true,
  "message": "Attendance already punched in"
}
```

```json
{
  "error": true,
  "message": "You are outside the allowed attendance area (250 m away, maximum allowed 100 m)"
}
```

```json
{
  "error": true,
  "message": "GPS location is required for attendance"
}
```

### cURL test

```bash
curl -X POST "https://techxpertindia.in/api/punch_in_employee_attendance.php" \
  -H "Content-Type: application/json" \
  -d "{\"EmployeeID\":123,\"Latitude\":28.6141,\"Longitude\":77.2092,\"imageData\":\"\"}"
```

> Replace `imageData` with actual base64 string from the app camera.

---

## 6. Punch out (check-out)

**URL:** `https://techxpertindia.in/api/punch_out_employee_attendance.php`

- Location radius check on check-out when `boundaryEnabled = true` (employee location or any branch)  
- Employee must be checked in today and not already checked out  
- Half-day is allowed (no minimum hours)

### Request payload

```json
{
  "EmployeeID": 123,
  "Latitude": 28.614100,
  "Longitude": 77.209200,
  "imageData": "BASE64_ENCODED_JPEG_STRING_WITHOUT_PREFIX"
}
```

| Field        | Required | Description |
|--------------|----------|-------------|
| `EmployeeID` | Yes      | Employee ID |
| `Latitude`   | No       | Optional — stored if sent |
| `Longitude`  | No       | Optional — stored if sent |
| `imageData`  | No       | Base64 selfie |

### Success response

```json
{
  "error": false,
  "message": "Attendance punched out"
}
```

### Error responses

```json
{
  "error": true,
  "message": "You must check in before checking out."
}
```

```json
{
  "error": true,
  "message": "Attendance already punched out"
}
```

### cURL test

```bash
curl -X POST "https://techxpertindia.in/api/punch_out_employee_attendance.php" \
  -H "Content-Type: application/json" \
  -d "{\"EmployeeID\":123,\"imageData\":\"\"}"
```

---

## Postman collection (import steps)

1. Open Postman → **Import** → **Raw text**
2. Create a collection variable: `baseUrl` = `https://techxpertindia.in/api`
3. Set collection pre-request: Header `Content-Type: application/json`
4. Use URLs: `{{baseUrl}}/get_employee_attendance_status.php` etc.

### Example Postman request

- **Method:** POST  
- **URL:** `https://techxpertindia.in/api/validate_employee_attendance_location.php`  
- **Body → raw → JSON:**

```json
{
  "EmployeeID": 123,
  "Latitude": 28.613939,
  "Longitude": 77.209023,
  "Action": "checkin"
}
```

---

## Full test scenario (step by step)

Replace `123` with a real `employees.ID` that has location boundary configured in admin.

### Step 1 — Load status

```
POST https://techxpertindia.in/api/get_employee_attendance_status.php
Body: { "EmployeeID": 123 }
```

Expected: `InTimeStatus` = 0 if not punched in today.

### Step 2 — Validate check-in location

```
POST https://techxpertindia.in/api/validate_employee_attendance_location.php
Body: {
  "EmployeeID": 123,
  "Latitude": 28.613939,
  "Longitude": 77.209023,
  "Action": "checkin"
}
```

Expected: `error: false`, `data.allowed: true`

### Step 3 — Punch in

```
POST https://techxpertindia.in/api/punch_in_employee_attendance.php
Body: {
  "EmployeeID": 123,
  "Latitude": 28.613939,
  "Longitude": 77.209023,
  "imageData": "<base64>"
}
```

Expected: `error: false`, message `"Attendance punched in"`

### Step 4 — Validate check-out (no GPS needed)

```
POST https://techxpertindia.in/api/validate_employee_attendance_location.php
Body: { "EmployeeID": 123, "Action": "checkout" }
```

Expected: `error: false`, `data.canCheckout: true`

### Step 5 — Punch out

```
POST https://techxpertindia.in/api/punch_out_employee_attendance.php
Body: { "EmployeeID": 123, "imageData": "<base64>" }
```

Expected: `error: false`, message `"Attendance punched out"`

---

## Admin setup (HR panel)

Per employee: **Employees → Location** tab

1. Set **Latitude** and **Longitude** (map or manual)  
2. Set **Radius (meters)** e.g. `100`  
3. Enable **“Enable location boundary for attendance”** (applies to **check-in only**)  
4. Click **Save Location**

**Database migration** (run once on server if columns missing):

`admin/employees/sql/add_attendance_location_columns.sql`

---

## Notes for developers

1. `EmployeeID` = `employees.ID` (from login session / user profile).  
2. Always send JSON body, not form-data.  
3. On check-in, if `data.location_policy.boundaryEnabled` is `true`, you **must** send GPS on validate + punch APIs.  
4. On check-out, if `boundaryEnabled` is `true`, send GPS on validate + punch APIs (same rules as check-in).  
5. `imageData` must be raw base64 only (strip `data:image/jpeg;base64,` if present).  
6. Server timezone: **Asia/Kolkata** (attendance date is server date).

---

## Legacy endpoint (avoid for new app code)

**URL:** `https://techxpertindia.in/api/check_attendance_geofence.php`

Same payload as validate API. Prefer `validate_employee_attendance_location.php` for new mobile builds.

---

*Document version: May 2026 — TechXpert Employee Attendance with Geofence*
