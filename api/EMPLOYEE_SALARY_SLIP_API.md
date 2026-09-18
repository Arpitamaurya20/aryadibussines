# Employee salary slip API (main `/api` folder)

Base: `http://localhost/Projects/techxpert/api/` (your domain + `/api/`)

## 1. List slips / get download link

**URL:** `get_employee_salary_slips.php`  
**Method:** `POST`  
**Content-Type:** `application/json`

### List all slips for employee

```json
{
  "EmployeeID": 42,
  "limit": 24,
  "company_id": 1
}
```

`company_id` optional (default: first active row in `quote_company_details`).

**Response:**

```json
{
  "error": false,
  "message": "Salary slips loaded",
  "employee_id": 42,
  "employee_name": "ROHIT KUSHWAHA",
  "default_company_id": 1,
  "data": [
    {
      "slip_id": 10,
      "period_label": "Apr 2026",
      "net_salary": 6980,
      "download_url": "http://localhost/Projects/techxpert/api/download_employee_salary_slip.php?token=..."
    }
  ]
}
```

### Single slip download URL

```json
{
  "EmployeeID": 42,
  "slip_id": 10,
  "company_id": 1
}
```

## 2. Download page (employee)

**URL:** `download_employee_salary_slip.php?token=...`  
Opens in browser → **Download salary slip (PDF)** (secure, 30 days).

## WhatsApp template (when HR clicks Send on payroll)

Pre-filled when HR sends from payroll (also used in email):

```
Dear {Employee Name},

Greetings from {Company Name}!

Your salary for *{Month Year}* has been processed and *approved*.

Download your salary slip for this month using the secure link below:
{download_url}

(This link is personal and valid for 30 days.)

If you have any concern or discrepancy, please contact the *HR department* at the earliest.

Thank you,
HR Team
{Company Name}
```

## Admin panel profile

**Path:** Admin → Profile (or View Employee) → **Salary** tab → **My salary slips** table with **Download PDF**.

No HRMS folder URLs for employees; links use `/api/download_employee_salary_slip.php`.
