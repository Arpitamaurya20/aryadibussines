# Multi-App Mobile Auth API – Full Usage Guide

Complete reference for testing with **Postman**, **cURL**, or any mobile app HTTP client.

---

## Base URL

| Environment | Base URL |
|-------------|----------|
| Live | `https://techxpertindia.in/api/appauthapis/` |
| Local | `http://localhost/projects/techxpert/api/appauthapis/` |

All endpoints return **JSON**.

---

## Common rules

1. **POST APIs** → set header: `Content-Type: application/json`
2. **Protected APIs** (`me.php`, `my_apps.php`, `switch_app.php`) → send login token in header
3. **Token header** (use either one):
   - `Authorization: Bearer YOUR_JWT_TOKEN`
   - `X-Access-Token: YOUR_JWT_TOKEN`
4. Save these from `verify_otp.php` response:
   - `token` → use on protected APIs
   - `refresh_token` → use on `refresh_token.php` and `logout.php`
5. **`app_code` values:** `HOMECARE` | `MYGATE` | `PARKING`

---

## Complete login flow (step by step)

```
Step 1  send_otp.php       → OTP sent on WhatsApp
Step 2  verify_otp.php     → get token + refresh_token
Step 3  me.php             → get logged-in user details
Step 4  my_apps.php        → list all apps user can open
Step 5  switch_app.php      → change app without OTP (optional)
Step 6  refresh_token.php   → get new token when expired (optional)
Step 7  logout.php          → logout
```

---

# 1. Send OTP

**URL:** `POST /send_otp.php`  
**Auth required:** No

### Headers
```
Content-Type: application/json
```

### Raw JSON body
```json
{
  "phonenumber": "8948975967",
  "full_name": "Rahul Sharma"
}
```

| Field | Required | Description |
|-------|----------|-------------|
| phonenumber | Yes | 10-digit mobile |
| full_name | No | Used only when creating new user |

### cURL
```bash
curl --location 'https://techxpertindia.in/api/appauthapis/send_otp.php' \
--header 'Content-Type: application/json' \
--data '{
  "phonenumber": "8948975967",
  "full_name": "Rahul Sharma"
}'
```

### Success response
```json
{
  "error": false,
  "message": "OTP sent successfully on WhatsApp.",
  "otp_expires_in_minutes": 10
}
```

### Error response
```json
{
  "error": true,
  "message": "Invalid mobile number. Enter 10-digit Indian mobile."
}
```

---

# 2. Verify OTP & Login

**URL:** `POST /verify_otp.php`  
**Auth required:** No

### Headers
```
Content-Type: application/json
```

### Raw JSON body
```json
{
  "phonenumber": "8948975967",
  "otp": "183381",
  "app_code": "HOMECARE",
  "device_type": "android",
  "device_name": "Samsung S24",
  "device_id": "unique-device-uuid",
  "firebase_token": "fcm-token-optional"
}
```

| Field | Required | Description |
|-------|----------|-------------|
| phonenumber | Yes | Same number used in send_otp |
| otp | Yes | OTP received on WhatsApp |
| app_code | Yes | HOMECARE / MYGATE / PARKING |
| device_type | No | android / ios |
| device_name | No | Phone model name |
| device_id | No | Unique device ID (recommended) |
| firebase_token | No | FCM push token |

### cURL
```bash
curl --location 'https://techxpertindia.in/api/appauthapis/verify_otp.php' \
--header 'Content-Type: application/json' \
--data '{
  "phonenumber": "8948975967",
  "otp": "183381",
  "app_code": "HOMECARE",
  "device_type": "android",
  "device_name": "Samsung S24",
  "device_id": "unique-device-uuid",
  "firebase_token": "fcm-token-optional"
}'
```

### Success response
```json
{
  "error": false,
  "message": "Login successful.",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9....",
  "refresh_token": "a1b2c3d4e5f6....",
  "expires_in": 86400,
  "user": {
    "user_id": 1,
    "full_name": "Rahul Sharma",
    "mobile": "8948975967",
    "email": null,
    "profile_image": null,
    "is_mobile_verified": "Yes"
  },
  "app": {
    "app_id": 1,
    "app_code": "HOMECARE",
    "app_name": "HomeCare",
    "app_logo": null
  },
  "role": {
    "role_id": 1,
    "role_code": "CUSTOMER",
    "role_name": "Customer"
  },
  "profile": null
}
```

**Important:** Copy `token` and `refresh_token` from this response. Use `token` for all protected APIs below.

### Error response examples
```json
{
  "error": true,
  "message": "Invalid or expired OTP."
}
```

```json
{
  "error": true,
  "message": "Invalid app_code. Use HOMECARE, MYGATE, or PARKING."
}
```

---

# 3. Get Current User (me.php)

**URL:** `GET` or `POST /me.php`  
**Auth required:** Yes (Bearer token from verify_otp)

### Headers
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9....
Content-Type: application/json
```

### Raw JSON body
**Not required.** You can call with empty body or no body.

Optional (if using POST with body):
```json
{}
```

### Postman setup
1. Method: `GET` or `POST`
2. URL: `https://techxpertindia.in/api/appauthapis/me.php`
3. Tab **Authorization** → Type: `Bearer Token` → paste your `token`
4. Or tab **Headers** → add:
   - Key: `Authorization`
   - Value: `Bearer YOUR_TOKEN_HERE`

### Ionic / Angular
See **[IONIC_APP_AUTH_API.md](./IONIC_APP_AUTH_API.md)** for full Ionic service, interceptor, and `me.php` code examples.

### cURL (GET)
```bash
curl --location 'https://techxpertindia.in/api/appauthapis/me.php' \
--header 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9....'
```

### cURL (POST)
```bash
curl --location --request POST 'https://techxpertindia.in/api/appauthapis/me.php' \
--header 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9....' \
--header 'Content-Type: application/json' \
--data '{}'
```

### Alternative header (if Authorization does not work on server)
```bash
curl --location 'https://techxpertindia.in/api/appauthapis/me.php' \
--header 'X-Access-Token: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9....'
```

### Success response
```json
{
  "error": false,
  "user": {
    "user_id": 1,
    "full_name": "Rahul Sharma",
    "mobile": "8948975967",
    "email": null,
    "profile_image": null,
    "is_mobile_verified": "Yes",
    "last_login": "2026-05-27 15:30:00"
  },
  "app": {
    "app_id": 1,
    "app_code": "HOMECARE",
    "app_name": "HomeCare",
    "app_logo": null
  },
  "role": {
    "role_id": 1,
    "role_code": "CUSTOMER",
    "role_name": "Customer"
  },
  "profile": null
}
```

`profile` will contain HomeCare/Parking/MyGate profile row when available, for example:
```json
"profile": {
  "ID": 1,
  "UserID": 1,
  "Address": "MG Road",
  "City": "Jaipur",
  "State": "Rajasthan",
  "ServicePreference": "AC Repair",
  "IsActive": 1
}
```

### Error response
```json
{
  "error": true,
  "message": "Authorization required. Send Bearer token in Authorization header."
}
```

```json
{
  "error": true,
  "message": "Invalid or expired token. Please login again."
}
```

---

# 4. List My Apps (my_apps.php)

**URL:** `GET` or `POST /my_apps.php`  
**Auth required:** Yes

Shows all apps the user can access and their role in each app.

### Headers
```
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

### Raw JSON body
Not required. Optional empty body for POST:
```json
{}
```

### cURL
```bash
curl --location 'https://techxpertindia.in/api/appauthapis/my_apps.php' \
--header 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9....'
```

### Success response
```json
{
  "error": false,
  "apps": [
    {
      "app_id": 1,
      "app_code": "HOMECARE",
      "app_name": "HomeCare",
      "app_logo": null,
      "role_id": 1,
      "role_code": "CUSTOMER",
      "role_name": "Customer",
      "is_default_app": true
    },
    {
      "app_id": 3,
      "app_code": "PARKING",
      "app_name": "Parking",
      "app_logo": null,
      "role_id": 7,
      "role_code": "CUSTOMER",
      "role_name": "Customer",
      "is_default_app": false
    }
  ]
}
```

---

# 5. Switch App (switch_app.php)

**URL:** `POST /switch_app.php`  
**Auth required:** Yes

Use when user is already logged in and wants to open another app (Parking, MyGate, etc.) **without OTP**.

### Headers
```
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

### Raw JSON body
```json
{
  "app_code": "PARKING",
  "device_type": "android",
  "device_name": "Samsung S24",
  "device_id": "unique-device-uuid",
  "firebase_token": "fcm-token-optional"
}
```

| Field | Required | Description |
|-------|----------|-------------|
| app_code | Yes | Target app: HOMECARE / MYGATE / PARKING |
| device_id | No | Same device ID used at login |
| device_type | No | android / ios |
| device_name | No | Phone model |
| firebase_token | No | FCM token |

### cURL
```bash
curl --location 'https://techxpertindia.in/api/appauthapis/switch_app.php' \
--header 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9....' \
--header 'Content-Type: application/json' \
--data '{
  "app_code": "PARKING",
  "device_id": "unique-device-uuid"
}'
```

### Success response
```json
{
  "error": false,
  "message": "Switched application.",
  "token": "NEW_JWT_TOKEN_FOR_PARKING",
  "refresh_token": "NEW_REFRESH_TOKEN",
  "expires_in": 86400,
  "app": {
    "app_id": 3,
    "app_code": "PARKING",
    "app_name": "Parking"
  },
  "role": {
    "role_id": 7,
    "role_code": "CUSTOMER",
    "role_name": "Customer"
  },
  "profile": null
}
```

**Important:** After switch, replace stored `token` and `refresh_token` with the new values from this response.

---

# 6. Refresh Token (refresh_token.php)

**URL:** `POST /refresh_token.php`  
**Auth required:** No (uses refresh_token in body)

Call when access token expires and you want a new one without OTP.

### Headers
```
Content-Type: application/json
```

### Raw JSON body
```json
{
  "refresh_token": "a1b2c3d4e5f6....",
  "app_code": "HOMECARE",
  "device_id": "unique-device-uuid"
}
```

| Field | Required | Description |
|-------|----------|-------------|
| refresh_token | Yes | From verify_otp or switch_app response |
| app_code | Yes | App you want token for |
| device_id | No | Should match login device if provided |

### cURL
```bash
curl --location 'https://techxpertindia.in/api/appauthapis/refresh_token.php' \
--header 'Content-Type: application/json' \
--data '{
  "refresh_token": "a1b2c3d4e5f6....",
  "app_code": "HOMECARE",
  "device_id": "unique-device-uuid"
}'
```

### Success response
```json
{
  "error": false,
  "message": "Token refreshed.",
  "token": "NEW_JWT_TOKEN",
  "refresh_token": "NEW_REFRESH_TOKEN",
  "expires_in": 86400,
  "app": {
    "app_id": 1,
    "app_code": "HOMECARE",
    "app_name": "HomeCare"
  },
  "role": {
    "role_id": 1,
    "role_code": "CUSTOMER",
    "role_name": "Customer"
  }
}
```

---

# 7. Logout (logout.php)

**URL:** `POST /logout.php`  
**Auth required:** Optional (send both token and refresh_token for full logout)

### Headers
```
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

### Raw JSON body
```json
{
  "refresh_token": "a1b2c3d4e5f6....",
  "device_id": "unique-device-uuid"
}
```

| Field | Required | Description |
|-------|----------|-------------|
| refresh_token | No | Invalidates refresh token |
| device_id | No | Invalidates all tokens for this device |

### cURL
```bash
curl --location 'https://techxpertindia.in/api/appauthapis/logout.php' \
--header 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9....' \
--header 'Content-Type: application/json' \
--data '{
  "refresh_token": "a1b2c3d4e5f6....",
  "device_id": "unique-device-uuid"
}'
```

### Success response
```json
{
  "error": false,
  "message": "Logged out successfully."
}
```

---

# Postman quick setup

## Collection variables
Create these variables in Postman:

| Variable | Example value |
|----------|---------------|
| base_url | `https://techxpertindia.in/api/appauthapis` |
| token | (paste from verify_otp response) |
| refresh_token | (paste from verify_otp response) |
| phonenumber | `8948975967` |

## Test order in Postman

### Request 1 – Send OTP
- **POST** `{{base_url}}/send_otp.php`
- Body → raw → JSON:
```json
{
  "phonenumber": "{{phonenumber}}"
}
```

### Request 2 – Verify OTP
- **POST** `{{base_url}}/verify_otp.php`
- Body:
```json
{
  "phonenumber": "{{phonenumber}}",
  "otp": "123456",
  "app_code": "HOMECARE",
  "device_type": "android",
  "device_id": "postman-test-device"
}
```
- In **Tests** tab, save token automatically:
```javascript
var json = pm.response.json();
if (json.token) {
  pm.collectionVariables.set("token", json.token);
}
if (json.refresh_token) {
  pm.collectionVariables.set("refresh_token", json.refresh_token);
}
```

### Request 3 – Me
- **GET** `{{base_url}}/me.php`
- Authorization → Bearer Token → `{{token}}`

### Request 4 – My Apps
- **GET** `{{base_url}}/my_apps.php`
- Authorization → Bearer Token → `{{token}}`

### Request 5 – Switch App
- **POST** `{{base_url}}/switch_app.php`
- Authorization → Bearer Token → `{{token}}`
- Body:
```json
{
  "app_code": "PARKING",
  "device_id": "postman-test-device"
}
```

### Request 6 – Refresh Token
- **POST** `{{base_url}}/refresh_token.php`
- Body:
```json
{
  "refresh_token": "{{refresh_token}}",
  "app_code": "HOMECARE",
  "device_id": "postman-test-device"
}
```

### Request 7 – Logout
- **POST** `{{base_url}}/logout.php`
- Authorization → Bearer Token → `{{token}}`
- Body:
```json
{
  "refresh_token": "{{refresh_token}}",
  "device_id": "postman-test-device"
}
```

---

# Mobile app usage (Flutter / React Native / Kotlin)

## After login – store locally
```
token
refresh_token
user_id
app_code
role_code
```

## Call protected API example
```
GET https://techxpertindia.in/api/appauthapis/me.php
Headers:
  Authorization: Bearer {saved_token}
```

## If API returns 401
1. Call `refresh_token.php` with saved `refresh_token`
2. Save new `token` and `refresh_token`
3. Retry original API
4. If refresh also fails → go back to OTP login

## Role-based UI example
```
if (role_code == "CUSTOMER") {
  show customer screens
}
if (role_code == "TECHNICIAN") {
  show technician screens
}
if (role_code == "VALET") {
  show valet screens
}
```

---

# Default roles on first login

| app_code | Auto-assigned role |
|----------|-------------------|
| HOMECARE | CUSTOMER |
| PARKING | CUSTOMER |
| MYGATE | RESIDENT |

Staff roles (TECHNICIAN, VALET, SECURITY_GUARD, etc.) must be assigned in database table `user_apps` by admin.

---

# Common errors

| HTTP | Message | Fix |
|------|---------|-----|
| 401 | Authorization required | Add `Authorization: Bearer token` header |
| 401 | Invalid or expired token | Login again or use refresh_token |
| 405 | Method Not Allowed | Use POST for send_otp, verify_otp, switch_app, refresh, logout |
| 500 | Internal Server Error | Check server PHP error log; ensure DB tables exist |

---

# Full example with your number

### 1) Send OTP
```json
POST https://techxpertindia.in/api/appauthapis/send_otp.php

{
  "phonenumber": "8948975967"
}
```

### 2) Verify OTP
```json
POST https://techxpertindia.in/api/appauthapis/verify_otp.php

{
  "phonenumber": "8948975967",
  "otp": "183381",
  "app_code": "HOMECARE",
  "device_type": "android",
  "device_name": "Samsung S24",
  "device_id": "unique-device-uuid",
  "firebase_token": "fcm-token-optional"
}
```

### 3) Me (replace TOKEN)
```
GET https://techxpertindia.in/api/appauthapis/me.php
Authorization: Bearer TOKEN
```

### 4) My Apps
```
GET https://techxpertindia.in/api/appauthapis/my_apps.php
Authorization: Bearer TOKEN
```

### 5) Switch to Parking
```json
POST https://techxpertindia.in/api/appauthapis/switch_app.php
Authorization: Bearer TOKEN

{
  "app_code": "PARKING",
  "device_id": "unique-device-uuid"
}
```
