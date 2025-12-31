# Testing Guide for Redesigned Extension

## Loading the Extension

### Chrome / Edge / Brave

1. Open your browser and navigate to:
   - **Chrome**: `chrome://extensions`
   - **Edge**: `edge://extensions`
   - **Brave**: `brave://extensions`

2. Enable **Developer mode** (toggle in top-right corner)

3. Click **Load unpacked**

4. Navigate to and select the `extension` folder:
   ```
   /Users/gurpreetkait/code/ScreenBuddy-video-transcription/extension/
   ```

5. The extension should now appear in your extensions list

## Quick Test

### Test 1: Basic Panel Display

1. Navigate to any regular website (e.g., `https://google.com`)
2. Click the ScreenSense extension icon in your toolbar
3. **Expected Result**: A floating panel should appear on the right side of the page

### Test 2: Device Selection

1. Open the floating panel (click extension icon)
2. Check the camera dropdown
3. Check the microphone dropdown
4. **Expected Result**: You should see your available cameras and microphones listed

### Test 3: Keyboard Shortcut

1. On any webpage, press `Ctrl+Shift+R` (or `Cmd+Shift+R` on Mac)
2. **Expected Result**: Floating panel should appear

### Test 4: Recording Flow

1. Open floating panel
2. Select camera and microphone devices
3. Enable camera toggle
4. Click "Start Recording"
5. **Expected Result**:
   - Panel switches to recording controls
   - System screen-share dialog appears
   - After selecting screen, recording starts
   - Camera preview should appear in bottom-right corner

### Test 5: Recording Controls

1. Start a recording (follow Test 4)
2. Try clicking **Pause** button
3. Try clicking **Resume** button
4. Try clicking **Stop** button
5. **Expected Result**: Each button should work as expected

### Test 6: Camera Preview Dragging

1. Start a recording with camera enabled
2. Try dragging the camera preview to a different position
3. **Expected Result**: Camera preview should be draggable

## Common Issues & Solutions

### Issue: Panel doesn't appear
**Solution**:
- Check browser console for errors (F12 → Console tab)
- Refresh the page and try again
- Reload the extension in `chrome://extensions`

### Issue: No devices shown in dropdowns
**Solution**:
- Grant camera/microphone permissions when prompted
- Check browser settings → Privacy → Camera/Microphone permissions

### Issue: Recording doesn't start
**Solution**:
- Make sure you grant screen-sharing permission
- Check if you're on a restricted page (chrome://, about:, etc.)
- Try on a regular website first

### Issue: Keyboard shortcut doesn't work
**Solution**:
- Check `chrome://extensions/shortcuts` to verify the shortcut is set
- Some pages may block extension shortcuts

## Developer Console

Open the developer console (F12) to see debug messages:
- `ScreenSense content script loaded` - Content script injected successfully
- `Showing floating panel` - Panel being displayed
- `Devices enumerated` - Device list retrieved
- `Starting recording with options` - Recording initiated

## What to Look For

### Visual Check
- ✅ Panel has dark gradient background
- ✅ Orange accent color (#ea580c)
- ✅ Smooth animations
- ✅ Professional, polished look
- ✅ Camera preview has orange border
- ✅ REC indicator is visible and pulsing

### Functionality Check
- ✅ Device selectors are populated
- ✅ Toggles work smoothly
- ✅ Start button triggers recording
- ✅ Recording controls work (pause/resume/stop/delete)
- ✅ Timer updates every second
- ✅ Camera preview is draggable
- ✅ Panel closes when clicking X

### UX Check
- ✅ Panel doesn't cover important content
- ✅ Panel is easily accessible
- ✅ Controls are intuitive
- ✅ Feedback is clear (timer, pulsing dot, etc.)

## Known Limitations

1. **Restricted Pages**: Extension cannot inject on `chrome://`, `about:`, or `chrome-extension://` pages
   - These will fallback to opening the record page in a new tab

2. **First-Time Permissions**: Browser will ask for camera/microphone permissions on first use

3. **Screen Sharing**: System dialog will appear for each recording session

## Next Features to Test (When Implemented)

- [ ] Countdown timer (3-2-1 before recording)
- [ ] Drawing tools overlay
- [ ] GIF export option
- [ ] Enhanced system audio capture

## Feedback

If you find any issues, please note:
1. What you were trying to do
2. What happened
3. What you expected to happen
4. Browser and OS version
5. Any console errors (F12 → Console)
