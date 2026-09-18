# Ionic / Angular – App Auth API Integration Guide

How to call `appauthapis` from an **Ionic + Angular** app (Capacitor or Cordova).

**Base URL (live):**
```
https://techxpertindia.in/api/appauthapis/
```

---

## 1. Install HTTP (usually already included)

Ionic Angular projects include `@angular/common/http`. Ensure `HttpClientModule` is imported in `app.module.ts`:

```typescript
import { HttpClientModule } from '@angular/common/http';

@NgModule({
  imports: [
    HttpClientModule,
    // ...
  ],
})
export class AppModule {}
```

For **standalone** Ionic apps, provide HTTP in `main.ts`:

```typescript
import { provideHttpClient } from '@angular/common/http';

bootstrapApplication(AppComponent, {
  providers: [provideHttpClient()],
});
```

---

## 2. Environment config

**`src/environments/environment.ts`**

```typescript
export const environment = {
  production: false,
  apiBaseUrl: 'https://techxpertindia.in/api/appauthapis/',
};
```

**`src/environments/environment.prod.ts`**

```typescript
export const environment = {
  production: true,
  apiBaseUrl: 'https://techxpertindia.in/api/appauthapis/',
};
```

---

## 3. Auth service (recommended)

Create **`src/app/services/app-auth.service.ts`**

```typescript
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { environment } from '../../environments/environment';

export interface SendOtpRequest {
  phonenumber: string;
  full_name?: string;
}

export interface VerifyOtpRequest {
  phonenumber: string;
  otp: string;
  app_code: 'HOMECARE' | 'MYGATE' | 'PARKING';
  device_type?: string;
  device_name?: string;
  device_id?: string;
  firebase_token?: string;
}

export interface AuthLoginResponse {
  error: boolean;
  message?: string;
  token?: string;
  refresh_token?: string;
  expires_in?: number;
  user?: any;
  app?: any;
  role?: any;
  profile?: any;
}

export interface MeResponse {
  error: boolean;
  message?: string;
  user?: any;
  app?: any;
  role?: any;
  profile?: any;
}

@Injectable({ providedIn: 'root' })
export class AppAuthService {
  private baseUrl = environment.apiBaseUrl;

  private readonly TOKEN_KEY = 'app_auth_token';
  private readonly REFRESH_KEY = 'app_auth_refresh_token';
  private readonly APP_CODE_KEY = 'app_auth_app_code';

  constructor(private http: HttpClient) {}

  // ---------- OTP login ----------

  sendOtp(body: SendOtpRequest): Observable<any> {
    return this.http.post(`${this.baseUrl}send_otp.php`, body);
  }

  verifyOtp(body: VerifyOtpRequest): Observable<AuthLoginResponse> {
    return this.http.post<AuthLoginResponse>(`${this.baseUrl}verify_otp.php`, body).pipe(
      tap((res) => {
        if (!res.error && res.token) {
          this.saveSession(res);
        }
      })
    );
  }

  // ---------- Protected APIs ----------

  /** GET current user – me.php */
  getMe(): Observable<MeResponse> {
    return this.http.get<MeResponse>(`${this.baseUrl}me.php`, {
      headers: this.authHeaders(),
    });
  }

  /** List all apps user can access */
  getMyApps(): Observable<any> {
    return this.http.get(`${this.baseUrl}my_apps.php`, {
      headers: this.authHeaders(),
    });
  }

  /** Switch app without OTP */
  switchApp(appCode: string, deviceId?: string): Observable<AuthLoginResponse> {
    return this.http.post<AuthLoginResponse>(
      `${this.baseUrl}switch_app.php`,
      { app_code: appCode, device_id: deviceId },
      { headers: this.authHeaders() }
    ).pipe(
      tap((res) => {
        if (!res.error && res.token) {
          this.saveSession(res, appCode);
        }
      })
    );
  }

  refreshToken(deviceId?: string): Observable<AuthLoginResponse> {
    const refreshToken = this.getRefreshToken();
    const appCode = this.getAppCode();

    return this.http.post<AuthLoginResponse>(`${this.baseUrl}refresh_token.php`, {
      refresh_token: refreshToken,
      app_code: appCode,
      device_id: deviceId,
    }).pipe(
      tap((res) => {
        if (!res.error && res.token) {
          this.saveSession(res, appCode);
        }
      })
    );
  }

  logout(deviceId?: string): Observable<any> {
    const refreshToken = this.getRefreshToken();

    return this.http.post(
      `${this.baseUrl}logout.php`,
      { refresh_token: refreshToken, device_id: deviceId },
      { headers: this.authHeaders() }
    ).pipe(
      tap(() => this.clearSession())
    );
  }

  // ---------- Token helpers ----------

  private authHeaders(): HttpHeaders {
    const token = this.getToken();
    return new HttpHeaders({
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    });
  }

  saveSession(res: AuthLoginResponse, appCode?: string): void {
    if (res.token) {
      localStorage.setItem(this.TOKEN_KEY, res.token);
    }
    if (res.refresh_token) {
      localStorage.setItem(this.REFRESH_KEY, res.refresh_token);
    }
    if (appCode) {
      localStorage.setItem(this.APP_CODE_KEY, appCode);
    } else if (res.app?.app_code) {
      localStorage.setItem(this.APP_CODE_KEY, res.app.app_code);
    }
  }

  getToken(): string {
    return localStorage.getItem(this.TOKEN_KEY) || '';
  }

  getRefreshToken(): string {
    return localStorage.getItem(this.REFRESH_KEY) || '';
  }

  getAppCode(): string {
    return localStorage.getItem(this.APP_CODE_KEY) || 'HOMECARE';
  }

  isLoggedIn(): boolean {
    return !!this.getToken();
  }

  clearSession(): void {
    localStorage.removeItem(this.TOKEN_KEY);
    localStorage.removeItem(this.REFRESH_KEY);
    localStorage.removeItem(this.APP_CODE_KEY);
  }
}
```

---

## 4. Call `me.php` from a page

**`src/app/pages/profile/profile.page.ts`**

```typescript
import { Component, OnInit } from '@angular/core';
import { AppAuthService } from '../../services/app-auth.service';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.page.html',
})
export class ProfilePage implements OnInit {
  user: any;
  app: any;
  role: any;
  loading = true;
  error = '';

  constructor(private auth: AppAuthService) {}

  ngOnInit() {
    this.loadMe();
  }

  loadMe() {
    this.loading = true;
    this.error = '';

    this.auth.getMe().subscribe({
      next: (res) => {
        this.loading = false;
        if (res.error) {
          this.error = res.message || 'Failed to load profile';
          return;
        }
        this.user = res.user;
        this.app = res.app;
        this.role = res.role;
      },
      error: (err) => {
        this.loading = false;
        this.error = err?.error?.message || 'Network error';
      },
    });
  }
}
```

**`profile.page.html`**

```html
<ion-header>
  <ion-toolbar>
    <ion-title>My Profile</ion-title>
  </ion-toolbar>
</ion-header>

<ion-content class="ion-padding">
  <ion-spinner *ngIf="loading"></ion-spinner>
  <ion-text color="danger" *ngIf="error">{{ error }}</ion-text>

  <div *ngIf="user">
    <h2>{{ user.full_name || 'User' }}</h2>
    <p>Mobile: {{ user.mobile }}</p>
    <p>App: {{ app?.app_name }}</p>
    <p>Role: {{ role?.role_name }}</p>
  </div>
</ion-content>
```

---

## 5. Login page example (send OTP + verify)

**`login.page.ts`**

```typescript
import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AppAuthService } from '../../services/app-auth.service';
import { Device } from '@capacitor/device';

@Component({
  selector: 'app-login',
  templateUrl: './login.page.html',
})
export class LoginPage {
  phonenumber = '';
  otp = '';
  step: 'phone' | 'otp' = 'phone';
  loading = false;
  message = '';

  constructor(
    private auth: AppAuthService,
    private router: Router
  ) {}

  async sendOtp() {
    this.loading = true;
    this.message = '';

    this.auth.sendOtp({ phonenumber: this.phonenumber }).subscribe({
      next: (res) => {
        this.loading = false;
        if (res.error) {
          this.message = res.message;
          return;
        }
        this.step = 'otp';
        this.message = 'OTP sent on WhatsApp';
      },
      error: () => {
        this.loading = false;
        this.message = 'Failed to send OTP';
      },
    });
  }

  async verifyOtp() {
    this.loading = true;
    this.message = '';

    let deviceId = 'web-device';
    try {
      const info = await Device.getId();
      deviceId = info.identifier || deviceId;
    } catch (e) {}

    this.auth.verifyOtp({
      phonenumber: this.phonenumber,
      otp: this.otp,
      app_code: 'HOMECARE',
      device_type: 'android',
      device_name: 'Ionic App',
      device_id: deviceId,
    }).subscribe({
      next: (res) => {
        this.loading = false;
        if (res.error) {
          this.message = res.message || 'Invalid OTP';
          return;
        }
        // token saved automatically in service
        this.router.navigateByUrl('/profile');
      },
      error: () => {
        this.loading = false;
        this.message = 'Login failed';
      },
    });
  }
}
```

Install Capacitor Device (optional, for device_id):

```bash
npm install @capacitor/device
npx cap sync
```

---

## 6. HTTP interceptor (auto attach Bearer token)

Create **`src/app/interceptors/auth.interceptor.ts`**

```typescript
import { Injectable } from '@angular/core';
import {
  HttpEvent,
  HttpHandler,
  HttpInterceptor,
  HttpRequest,
} from '@angular/common/http';
import { Observable } from 'rxjs';
import { AppAuthService } from '../services/app-auth.service';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  constructor(private auth: AppAuthService) {}

  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    const token = this.auth.getToken();

    // Only attach token for appauthapis calls
    if (token && req.url.includes('/appauthapis/')) {
      const cloned = req.clone({
        setHeaders: {
          Authorization: `Bearer ${token}`,
        },
      });
      return next.handle(cloned);
    }

    return next.handle(req);
  }
}
```

Register in **`app.module.ts`:**

```typescript
import { HTTP_INTERCEPTORS } from '@angular/common/http';
import { AuthInterceptor } from './interceptors/auth.interceptor';

providers: [
  {
    provide: HTTP_INTERCEPTORS,
    useClass: AuthInterceptor,
    multi: true,
  },
],
```

With interceptor, you can call `me.php` without manually setting headers:

```typescript
this.http.get(`${baseUrl}me.php`).subscribe(...);
```

---

## 7. Auth guard (protect routes)

**`src/app/guards/auth.guard.ts`**

```typescript
import { Injectable } from '@angular/core';
import { CanActivate, Router } from '@angular/router';
import { AppAuthService } from '../services/app-auth.service';

@Injectable({ providedIn: 'root' })
export class AuthGuard implements CanActivate {
  constructor(private auth: AppAuthService, private router: Router) {}

  canActivate(): boolean {
    if (this.auth.isLoggedIn()) {
      return true;
    }
    this.router.navigateByUrl('/login');
    return false;
  }
}
```

**`app-routing.module.ts`**

```typescript
{
  path: 'profile',
  loadChildren: () => import('./pages/profile/profile.module').then(m => m.ProfilePageModule),
  canActivate: [AuthGuard],
},
```

---

## 8. Using `@capacitor/preferences` instead of localStorage

For better mobile storage:

```bash
npm install @capacitor/preferences
```

```typescript
import { Preferences } from '@capacitor/preferences';

async saveToken(token: string) {
  await Preferences.set({ key: 'app_auth_token', value: token });
}

async getToken(): Promise<string> {
  const { value } = await Preferences.get({ key: 'app_auth_token' });
  return value || '';
}
```

---

## 9. Quick reference – all endpoints from Ionic

| Action | Method | URL | Body | Auth header |
|--------|--------|-----|------|-------------|
| Send OTP | POST | `send_otp.php` | `{ phonenumber }` | No |
| Verify OTP | POST | `verify_otp.php` | `{ phonenumber, otp, app_code, device_id }` | No |
| **Me (profile)** | **GET** | **`me.php`** | **None** | **Yes – Bearer token** |
| My apps | GET | `my_apps.php` | None | Yes |
| Switch app | POST | `switch_app.php` | `{ app_code, device_id }` | Yes |
| Refresh | POST | `refresh_token.php` | `{ refresh_token, app_code }` | No |
| Logout | POST | `logout.php` | `{ refresh_token, device_id }` | Yes |

---

## 10. `me.php` – minimal Ionic call (copy-paste)

```typescript
import { HttpClient, HttpHeaders } from '@angular/common/http';

const token = localStorage.getItem('app_auth_token');

const headers = new HttpHeaders({
  Authorization: `Bearer ${token}`,
});

this.http.get('https://techxpertindia.in/api/appauthapis/me.php', { headers })
  .subscribe({
    next: (res: any) => {
      console.log('User:', res.user);
      console.log('App:', res.app);
      console.log('Role:', res.role);
    },
    error: (err) => {
      console.error(err.error?.message || 'Error');
    },
  });
```

---

## 11. Role-based screens in Ionic

```typescript
const roleCode = res.role?.role_code;

switch (roleCode) {
  case 'CUSTOMER':
    this.router.navigateByUrl('/customer-home');
    break;
  case 'TECHNICIAN':
    this.router.navigateByUrl('/technician-home');
    break;
  case 'VALET':
    this.router.navigateByUrl('/valet-home');
    break;
  case 'RESIDENT':
    this.router.navigateByUrl('/resident-home');
    break;
  default:
    this.router.navigateByUrl('/home');
}
```

---

## 12. Common Ionic errors

| Problem | Cause | Fix |
|---------|-------|-----|
| 401 on me.php | Token missing | Save token after verify_otp; add Authorization header |
| CORS error | Server header issue | API already sends `Access-Control-Allow-Origin: *` |
| 500 error | Old server code | Deploy latest `AppAuthService.php` fix |
| Empty token | Not saved after login | Call `saveSession()` in verifyOtp tap |
| Token expired | Session timeout | Call `refreshToken()` or login again |

---

## 13. Recommended app flow

```
LoginPage
  → sendOtp()
  → verifyOtp() → save token
  → getMe() → show user + role
  → navigate by role_code

HomePage (protected by AuthGuard)
  → getMyApps() → show app switcher
  → switchApp('PARKING') → new token
  → getMe() again

Logout
  → logout() → clearSession() → LoginPage
```

---

## Related docs

- Full API raw JSON / Postman: [MOBILE_APP_AUTH_API.md](./MOBILE_APP_AUTH_API.md)
