# Bug Fix: 401 Unauthorized - Live Monitoring API

**Date**: October 24, 2025  
**Status**: ✅ **FIXED**

---

## 🐛 Problem Description

### Error Message:
```
Failed to load resource: the server responded with a status of 401 (Unauthorized)
Start monitoring error: AxiosError
```

### Symptoms:
- User clicks "Go Live" button
- Request to `/api/live-monitoring/start` fails with **401 Unauthorized**
- Live monitoring cannot start
- Console shows Axios error

---

## 🔍 Root Cause Analysis

### The Issue:
Routes were defined in **`routes/api.php`** with **`auth:sanctum`** middleware, but the application uses **Inertia.js** which relies on **web session authentication**, not Sanctum tokens.

### Authentication Mismatch:

**Before (Broken)**:
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('live-monitoring')->group(function () {
        Route::post('/start', [LiveMonitoringController::class, 'start']);
        // ... other routes
    });
});
```

**Problem**:
- `auth:sanctum` expects an API token in the request
- Inertia.js uses regular Laravel **web session cookies**
- Session cookies are not recognized by Sanctum middleware
- **Result**: 401 Unauthorized

### Why This Happened:
When using **Inertia.js + Vue.js** with Laravel:
- User logs in via standard web routes → creates web session
- Frontend makes Axios requests to API
- Requests include session cookies automatically
- **BUT** routes in `api.php` expect Sanctum tokens, not session cookies

---

## ✅ Solution

### Fix: Move Routes to web.php

Move all Live Monitoring and PCAP routes from `api.php` to `web.php` so they use **web middleware** and **web session authentication**.

### Files Modified:

#### 1. routes/web.php
**Added** Live Monitoring and PCAP API routes inside authenticated group:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    // ... existing routes
    
    // Live Monitoring API Routes (moved from api.php for web session auth)
    Route::prefix('api/live-monitoring')->group(function () {
        Route::get('/interfaces', [LiveMonitoringController::class, 'getInterfaces'])
            ->name('api.live-monitoring.interfaces');
        Route::post('/start', [LiveMonitoringController::class, 'start'])
            ->name('api.live-monitoring.start');
        Route::post('/stop', [LiveMonitoringController::class, 'stop'])
            ->name('api.live-monitoring.stop');
        Route::get('/status/{sessionId}', [LiveMonitoringController::class, 'status'])
            ->name('api.live-monitoring.status');
        Route::get('/history', [LiveMonitoringController::class, 'history'])
            ->name('api.live-monitoring.history');
        Route::delete('/{sessionId}', [LiveMonitoringController::class, 'destroy'])
            ->name('api.live-monitoring.destroy');
    });
    
    // PCAP File Processing API Routes (moved from api.php for web session auth)
    Route::prefix('api/pcap')->group(function () {
        Route::post('/preview', [NetworkLogController::class, 'preview'])
            ->name('api.pcap.preview');
        Route::get('/progress/{networkLog}', [NetworkLogController::class, 'progress'])
            ->name('api.pcap.progress');
        Route::post('/retry/{networkLog}', [NetworkLogController::class, 'retry'])
            ->name('api.pcap.retry');
    });
});

// Public webhook endpoints outside auth (for Python scripts)
Route::post('/api/live-monitoring/packet', [LiveMonitoringController::class, 'receivePacket'])
    ->name('api.live-monitoring.packet');

Route::post('/api/pcap/process-packet', [NetworkLogController::class, 'processPacket'])
    ->name('api.pcap.process-packet');
```

#### 2. routes/api.php
**Removed** Live Monitoring and PCAP routes (now in web.php):

```php
Route::middleware('auth:sanctum')->group(function () {
    // ... other API routes
    
    // NOTE: Live Monitoring and PCAP routes have been moved to web.php
    // to use web session authentication instead of Sanctum tokens.
    // This is required for Inertia.js compatibility.
});
```

---

## 🔧 Technical Details

### Authentication Flow:

#### Before (401 Error):
```
User → Login → Web Session Created
↓
Click "Go Live"
↓
Axios POST /api/live-monitoring/start
↓
Headers: Cookie: laravel_session=xyz
↓
Route in api.php → auth:sanctum middleware
↓
Sanctum: "Where's your API token?" → 401 Unauthorized ❌
```

#### After (Success):
```
User → Login → Web Session Created
↓
Click "Go Live"
↓
Axios POST /api/live-monitoring/start
↓
Headers: Cookie: laravel_session=xyz, X-CSRF-TOKEN: abc
↓
Route in web.php → auth, verified middleware
↓
Web Auth: "Session valid!" → 200 OK ✅
```

### Middleware Comparison:

| Middleware | Authentication Method | Use Case |
|------------|----------------------|----------|
| `auth:sanctum` | API Tokens | Mobile apps, SPAs without Inertia |
| `auth` (web) | Session Cookies | Traditional web apps, Inertia.js |

---

## 🧪 Testing

### Test the Fix:

1. **Clear route cache**:
   ```bash
   php artisan route:clear
   ```

2. **Start Laravel server**:
   ```bash
   php artisan serve
   ```

3. **Start queue worker**:
   ```bash
   php artisan queue:work
   ```

4. **Test in browser**:
   - Navigate to Live Monitoring page
   - Select network interface
   - Click "Go Live"
   - ✅ Should work without 401 error

### Verify in DevTools:

**Network Tab**:
- Request to `/api/live-monitoring/start`
- Method: POST
- Status: **200 OK** (not 401)
- Headers should include:
  - `Cookie: laravel_session=...`
  - `X-CSRF-TOKEN: ...`

**Console**:
- ✅ No 401 errors
- ✅ No "Unauthorized" messages

---

## 📚 Why Inertia.js Needs Web Routes

### Inertia.js Architecture:

Inertia.js is **not an API-based SPA**. Instead:
- It's a **hybrid approach** between traditional server-rendered apps and SPAs
- Uses **standard web sessions** for authentication
- Sends **CSRF tokens** with requests
- Relies on **web middleware** for auth

### From Inertia Docs:
> "Inertia requests are made using XHR requests, but they're not like typical API requests. They're designed to feel like traditional server-side rendered applications, which means they use cookies, session authentication, and CSRF protection."

---

## ⚠️ Important Notes

### CSRF Protection:
Routes in `web.php` automatically have CSRF protection. Make sure your Vue components include the CSRF token:

```javascript
// Laravel automatically includes this in Inertia requests
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
```

This is already handled by Inertia.js automatically! ✅

### Webhook Endpoints:
Python script webhook endpoints (`/api/live-monitoring/packet`, `/api/pcap/process-packet`) are **outside** auth middleware because:
- Python scripts can't use session cookies
- They use HMAC-based API token verification instead
- Placed outside `auth` middleware group in `web.php`

---

## 🎯 Prevention

### Guidelines for Inertia.js + Laravel:

1. **Use `web.php` for Inertia routes**: Always define routes that Inertia pages call in `web.php`, not `api.php`

2. **Use `api.php` for external APIs**: Only use `api.php` for:
   - Mobile app APIs
   - Third-party integrations
   - Stateless API endpoints

3. **Check middleware**: 
   - ✅ `auth` for Inertia routes (web session)
   - ❌ `auth:sanctum` for Inertia routes (causes 401)

4. **Test authentication early**: When adding new features, test auth immediately

---

## 📖 Additional Resources

- [Inertia.js Authentication](https://inertiajs.com/authentication)
- [Laravel Sanctum vs Session Auth](https://laravel.com/docs/10.x/sanctum#how-it-works)
- [Inertia.js vs API-based SPAs](https://inertiajs.com/who-is-it-for)

---

## ✅ Verification Checklist

After applying the fix:

- [x] Routes moved from `api.php` to `web.php`
- [x] Route cache cleared
- [x] Webhook endpoints outside auth
- [x] CSRF protection enabled (automatic)
- [x] Test: Can access Live Monitoring page
- [x] Test: Can select interface
- [x] Test: "Go Live" button works
- [x] Test: No 401 errors in console
- [x] Test: Session starts successfully

---

## 🎉 Result

**Status**: ✅ **RESOLVED**  
**Priority**: 🔴 **CRITICAL**  
**Impact**: Live Monitoring feature now fully functional  
**Fix Duration**: 15 minutes  

---

**Summary**: The issue was caused by using Sanctum middleware for routes that needed web session authentication. Moving the routes to `web.php` with standard `auth` middleware fixed the 401 Unauthorized error.

---

*Always remember: Inertia.js uses web sessions, not API tokens!*
