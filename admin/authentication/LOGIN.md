# Admin login page

Local URL:

[http://localhost/Projects/aryadibussines/admin/authentication/login.php](http://localhost/Projects/aryadibussines/admin/authentication/login.php)

The form posts to `auth_controller/action_login.php`. That script hashes the password with **MD5** and looks up a matching row in the `users` table (`UserName` + `Password` + `IsActive = 1`).

Your phpMyAdmin screenshot shows **`SELECT * FROM users` returned 0 rows**. Login fails until at least one user exists.

---

## Local admin credentials

Use these on the login page after you run the SQL below.

| Field | Value |
|---|---|
| Username | `admin` |
| Password | `Admin@123` |
| User type | `Admin` |

The stored password is the MD5 of `Admin@123`:

```text
0e7517141fb53f21ee439b355b5a1d0a
```

Change this password after first login. Do not use this pair on a public server.

---

## Create the user (phpMyAdmin)

1. Open database **`aryadibussiness`**.
2. Go to the **SQL** tab.
3. Paste and run:

```sql
INSERT INTO `users` (
  `UserID`,
  `UserName`,
  `Password`,
  `UserType`,
  `EmployeeID`,
  `CorporateID`,
  `BranchID`,
  `CorporateUserID`,
  `CreatedDate`,
  `CreatedTime`,
  `IsActive`,
  `AuthToken`,
  `TokenExpiry`
) VALUES (
  1,
  'admin',
  '0e7517141fb53f21ee439b355b5a1d0a',
  'Admin',
  -1,
  -1,
  -1,
  0,
  CURDATE(),
  CURTIME(),
  1,
  NULL,
  NULL
);
```

4. Confirm with:

```sql
SELECT UserID, UserName, UserType, IsActive FROM users;
```

5. Sign in at the login URL with **admin** / **Admin@123**.

If `UserID` 1 already exists, use the next free ID instead of `1`.

---

## If you see "Invalid Credentials"

The login page is working. It did not find a matching row in **`aryadibussiness.users`**.

Do this next:

1. Confirm WAMP **Apache** and **MySQL** are running.
2. In phpMyAdmin, select **`aryadibussiness`** (two s letters). Do not use `aryadibussines`.
3. Open table **`users`**. If it is empty, the insert was never run.
4. Easiest fix on localhost: open this URL once:

   [http://localhost/Projects/aryadibussines/admin/authentication/create_admin_user.php](http://localhost/Projects/aryadibussines/admin/authentication/create_admin_user.php)

   It creates or resets user `admin` with password `Admin@123`.
5. Login with exactly:

   - Username: `admin`
   - Password: `Admin@123`

6. Delete `create_admin_user.php` after it succeeds.

---

## Add another user later

Replace the username and generate a new MD5 hash (PHP example):

```php
echo md5('YourNewPassword');
```

Then insert another `users` row with that hash. Keep `IsActive = 1`.

Common `UserType` values used by this portal:

- `Admin` — internal admin dashboard
- `Employee` — employee login (`EmployeeID` must match `employees.ID`)
- `Corporate Admin` / `Corporate User` / `Corporate Branch User` — client portal users

---

## Files involved

| File | Role |
|---|---|
| `admin/authentication/login.php` | Login UI |
| `admin/authentication/auth_controller/action_login.php` | MD5 hash + POST handler |
| `admin/authentication/auth_controller/authentication_controller.php` | Query `users`, start session |
| `admin/classes/dbh.class.php` | Database `aryadibussiness` |

After a successful login, the app redirects to `admin/dashboard/analytics_dashboard.php`.
