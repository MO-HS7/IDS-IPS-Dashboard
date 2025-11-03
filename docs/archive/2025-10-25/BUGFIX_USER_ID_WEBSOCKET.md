# Bug Fix: User ID Not Available - WebSocket Connection

**Date**: October 24, 2025  
**Status**: ✅ **FIXED**

---

## 🐛 Problem Description

### Error Message:
```
LiveMonitoring.vue:112 User ID not available
```

### Symptoms:
- WebSocket connection fails to establish
- Real-time packet updates don't work
- No live data shown in monitoring page
- Console shows "User ID not available"

---

## 🔍 Root Cause Analysis

### The Issue:
Code was trying to get user ID from `window.Laravel.user.id` which doesn't exist in Inertia.js applications.

### Wrong Code (Before):
```javascript
const connectWebSocket = () => {
    if (window.Echo) {
        const userId = window.Laravel?.user?.id; // ❌ Doesn't exist!
        if (!userId) {
            console.error('User ID not available');
            return;
        }
        // ...
    }
};
```

### Why This Failed:
- `window.Laravel` is used in traditional Laravel + Blade apps
- **Inertia.js** uses a different approach for sharing data
- User data is passed through **`page.props.auth.user`**, not `window.Laravel`

---

## ✅ Solution

### Fix: Use Inertia's `usePage()` Hook

Import `usePage` from Inertia and access user data from page props:

```javascript
// Import usePage from Inertia
import { Head, usePage } from '@inertiajs/vue3';

// Get page instance
const page = usePage();

// Access user ID correctly
const connectWebSocket = () => {
    if (window.Echo) {
        const userId = page.props.auth?.user?.id; // ✅ Correct!
        if (!userId) {
            console.error('User ID not available. User:', page.props.auth?.user);
            return;
        }
        // ...
    }
};
```

---

## 🔧 Files Modified

### resources/js/Pages/NetworkAnalysis/LiveMonitoring.vue

**Changes Made**:

1. **Added import**:
```javascript
import { Head, usePage } from '@inertiajs/vue3';
```

2. **Added page instance**:
```javascript
const page = usePage();
```

3. **Updated `connectWebSocket()`**:
```javascript
const userId = page.props.auth?.user?.id; // Changed from window.Laravel
```

4. **Updated `disconnectWebSocket()`**:
```javascript
const userId = page.props.auth?.user?.id; // Changed from window.Laravel
```

---

## 📊 How Inertia Passes User Data

### In HandleInertiaRequests Middleware:
```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'auth' => [
            'user' => $request->user() ? [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
            ] : null,
        ],
    ];
}
```

### In Vue Components:
```javascript
import { usePage } from '@inertiajs/vue3';

const page = usePage();

// Access user data
const userId = page.props.auth.user.id;        // ✅
const userName = page.props.auth.user.name;    // ✅
const userEmail = page.props.auth.user.email;  // ✅
const userRole = page.props.auth.user.role;    // ✅
```

---

## 🧪 Testing

### Verify the Fix:

1. **Clear cache**:
```bash
npm run build
```

2. **Clear browser cache** (Ctrl + Shift + Delete)

3. **Open Live Monitoring page**

4. **Check Console (F12)**:
   - ✅ No "User ID not available" error
   - ✅ WebSocket connects successfully
   - ✅ Channel name shown: `network.live.{userId}`

5. **Click "Go Live"**:
   - ✅ WebSocket connection established
   - ✅ Real-time updates work

---

## ⚠️ Common Mistakes with Inertia

### DON'T Use (Traditional Laravel):
```javascript
❌ window.Laravel.user
❌ window.Laravel.csrfToken
❌ window.Laravel.config
```

### DO Use (Inertia.js):
```javascript
✅ usePage().props.auth.user
✅ usePage().props.csrf_token
✅ usePage().props.{anySharedData}
```

---

## 📚 Additional Information

### About Google Fonts Error:

The other error in the console was:
```
GET https://fonts.gstatic.com/... net::ERR_NAME_NOT_RESOLVED
```

**This is NOT a code issue**. It means:
- ❌ No internet connection, or
- ❌ DNS resolver issue, or
- ❌ Firewall blocking Google Fonts

**Solutions**:
1. Check internet connection
2. Try different DNS (8.8.8.8)
3. Use local fonts instead of Google Fonts
4. Ignore if not critical (app will use fallback fonts)

---

## 🎯 Summary

| Issue | Cause | Solution |
|-------|-------|----------|
| User ID not available | Using `window.Laravel.user.id` | Use `usePage().props.auth.user.id` |
| WebSocket connection fails | Can't get user ID | Fixed by getting correct user ID |
| Google Fonts error | Network/DNS issue | Not a code problem |

---

## ✅ Verification Checklist

- [x] Import `usePage` from Inertia
- [x] Get user ID from `page.props.auth.user.id`
- [x] Frontend rebuilt with `npm run build`
- [x] Browser cache cleared
- [x] Console shows no "User ID not available" error
- [x] WebSocket connects successfully

---

**Status**: ✅ **RESOLVED**  
**Priority**: 🔴 **HIGH**  
**Impact**: WebSocket connection now works correctly  

---

*Remember: Always use Inertia's `usePage()` to access shared data, never `window.Laravel`!*
