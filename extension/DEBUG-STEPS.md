# Debug Steps - Extension Not Showing Floating Panel

## Immediate Steps to Fix

### 1. Reload the Extension
1. Go to `chrome://extensions`
2. Find "ScreenSense - Screen Recorder"
3. Click the **Reload** button (circular arrow icon)
4. You should see "Version: 1.0.0"

### 2. Close ALL Open Tabs
- Close all tabs that were open before reloading the extension
- Content scripts only inject when pages load
- Old tabs still have the old version

### 3. Open a Fresh Tab
1. Open a NEW tab
2. Navigate to a regular website (e.g., `https://google.com`)
3. **DO NOT** try on these pages (they won't work):
   - `chrome://` pages
   - `about:` pages
   - Extension pages
   - New tab page

### 4. Click the Extension Icon
1. Look for the ScreenSense icon in your toolbar
2. Click it
3. You should see the floating panel appear on the RIGHT SIDE of the page

## If It Still Doesn't Work

### Check Browser Console
1. Press `F12` to open DevTools
2. Go to the **Console** tab
3. Click the extension icon again
4. Look for:
   - ✅ `ScreenSense content script loaded`
   - ✅ `Showing floating panel`
   - ❌ Any red error messages

### Common Errors & Fixes

**Error: "FloatingPanel is not defined"**
- Reload the extension
- Close and reopen the tab

**Error: "Cannot access chrome.runtime"**
- The extension context was invalidated
- Reload the extension
- Refresh the page

**No console messages at all**
- Content script might not be injected
- Refresh the page
- Try a different website

## Manual Test

### Test 1: Check if content script loads
1. Open `https://google.com` in a new tab
2. Press `F12` → Console
3. Type: `window.__screensenseContentScriptLoaded`
4. Press Enter
5. Should show: `true`

### Test 2: Check if FloatingPanel class exists
1. In the same console, type: `window.FloatingPanel`
2. Press Enter
3. Should show: `class FloatingPanel { ... }`

### Test 3: Manually show the panel
1. In the console, type: `window.__screensenseShowPanel()`
2. Press Enter
3. The panel should appear!

## Still Not Working?

### Nuclear Option - Complete Reinstall
1. Go to `chrome://extensions`
2. **Remove** the ScreenSense extension
3. Close all Chrome tabs
4. Restart Chrome
5. Go back to `chrome://extensions`
6. Enable "Developer mode"
7. Click "Load unpacked"
8. Select: `/Users/gurpreetkait/code/ScreenBuddy-video-transcription/extension/`
9. Go to a fresh tab with `https://google.com`
10. Click the extension icon

## What Page Are You Testing On?

The extension CANNOT inject on:
- ❌ `chrome://extensions`
- ❌ `chrome://settings`
- ❌ `about:blank`
- ❌ Chrome Web Store
- ❌ Other extension pages

The extension WILL work on:
- ✅ `https://google.com`
- ✅ `https://youtube.com`
- ✅ `https://github.com`
- ✅ Your localhost website
- ✅ Any regular website

## Expected Behavior

### When clicking extension icon on ALLOWED page:
1. Console shows: `Extension icon clicked on tab: [number]`
2. Console shows: `ScreenSense content script loaded`
3. Console shows: `Showing floating panel`
4. Floating panel appears on RIGHT side with:
   - ScreenSense logo (orange)
   - Camera dropdown
   - Microphone dropdown
   - Toggle switches
   - Start Recording button

### When clicking extension icon on RESTRICTED page:
1. Console shows: `Cannot inject on restricted pages`
2. Opens a new tab with your ScreenSense website
3. This is EXPECTED behavior - it's the fallback

## Take Screenshots

If it's still not working, please check:
1. What page URL are you testing on?
2. What do you see in the browser console?
3. Does the extension icon show in the toolbar?
4. Any error messages?
