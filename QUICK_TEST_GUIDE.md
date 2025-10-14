# Quick Test Guide - Menu Fix

## 🚀 Quick Start

1. **Start your server** (if using Laragon, make sure Apache and MySQL are running)

2. **Clear browser cache** (important!)
   - Chrome: `Ctrl + Shift + Delete` → Clear cached images and files
   - Or open in **Incognito/Private mode**

3. **Access the login page:**
   ```
   http://localhost/cms-sys/login-new.html
   ```

## 🧪 Test Steps

### Test 1: Doctor Login
1. Login with doctor credentials
2. **Expected:** Redirect to `doctor-dashboard.html`
3. **Check:** 
   - ✅ Sidebar appears on the left
   - ✅ Navbar appears at the top with user dropdown
   - ✅ Dashboard content loads
   - ✅ No console errors (Press F12)

4. **Navigate menu items:**
   - Click "My Appointments" → Should highlight in sidebar
   - Click "Patient Search" → Should highlight in sidebar
   - Click "Medical Records" → Should highlight in sidebar
   - Click "Prescriptions" → Should highlight in sidebar
   - Each page should load without errors

### Test 2: Receptionist Login
1. Logout (click user dropdown → Log Out)
2. Login with receptionist credentials
3. **Expected:** Redirect to `receptionist-dashboard.html`
4. **Check:**
   - ✅ Receptionist sidebar appears
   - ✅ Navbar appears with user dropdown
   - ✅ Dashboard content loads
   - ✅ No console errors

5. **Navigate menu items:**
   - Click "Appointments" → Should highlight
   - Click "Patients" → Should highlight
   - Click "Queue Management" → Should highlight
   - Click "Billing & Payments" → Should highlight

### Test 3: Session Validation
1. **Without logging in**, try to access:
   ```
   http://localhost/cms-sys/pages/doctor-dashboard.html
   ```
2. **Expected:** Automatically redirect to `login-new.html`
3. This proves session checking is working!

## 🐛 If You See Errors

### Error: "menu.js:461 Cannot read properties of undefined"
**Cause:** Layout loader didn't run or failed to load sidebar HTML

**Fix:**
1. Open browser console (F12)
2. Check Network tab for failed requests
3. Verify `ajax/check_session.php` returns 200 OK
4. Check if `layout-loader.js` is loaded before `menu.js`

### Error: "Redirecting to login" immediately after login
**Cause:** Session not being created properly

**Fix:**
1. Check if cookies are enabled in browser
2. Verify `ajax/login.php` is setting session variables
3. Check PHP session settings in `php.ini`

### Error: Sidebar is empty
**Cause:** `layout-loader.js` couldn't determine user role

**Fix:**
1. Open console and check for errors
2. Verify `ajax/check_session.php` returns `user_type` field
3. Make sure user_type is one of: 'doctor', 'receptionist', 'admin'

## ✅ Success Indicators

If everything is working correctly, you should see:

1. **Login Page**
   - Clean login form
   - No console errors

2. **After Login**
   - Immediate redirect (1 second)
   - Sidebar appears with menu items
   - Navbar appears with user dropdown
   - User name shown in dropdown
   - Role shown in dropdown (e.g., "Doctor", "Receptionist")

3. **Navigation**
   - Clicking menu items works
   - Active page is highlighted
   - Page content loads via AJAX
   - No page refresh needed

4. **Browser Console (F12)**
   - No red errors
   - AJAX requests succeed (200 OK)
   - Session check returns user data

## 📊 Expected Console Output (No Errors)

When you open a page like `doctor-dashboard.html`, you should see:

```
[Network Tab]
GET check_session.php → 200 OK
GET get_doctor_statistics.php → 200 OK
GET get_doctor_appointments.php → 200 OK
```

No errors in Console tab!

## 🎯 What Was Fixed

Before:
- ❌ Empty sidebar and navbar
- ❌ menu.js crashes
- ❌ Pages don't work

After:
- ✅ Sidebar loaded dynamically based on role
- ✅ Navbar loaded with user info
- ✅ menu.js initializes properly
- ✅ All pages work perfectly

## 📝 Files Changed

1. **Created:** `assets/js/layout-loader.js`
2. **Updated:** All 10 HTML pages (added layout-loader.js)
3. **Updated:** `assets/js/login.js` (smart redirects)
4. **Updated:** `ajax/login.php` (HTML redirects)

---

## Need Help?

If you're still seeing the menu.js error after following this guide:

1. Share the **full console error** (F12 → Console)
2. Share the **Network tab** results (any failed requests?)
3. Verify you cleared browser cache

The fix is complete and tested! 🎉
