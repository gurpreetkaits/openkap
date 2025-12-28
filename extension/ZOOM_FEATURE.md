# Zoom on Click Feature

## Overview

The **Zoom on Click** feature automatically zooms in on areas where you click during screen recording. This is perfect for creating tutorials, bug reports, and demos where you want to highlight specific UI elements or actions.

## How It Works

When enabled, the zoom feature:

1. **Tracks your mouse clicks** - Every time you click during recording, it zooms in
2. **Smooth animations** - Uses smooth easing transitions to zoom in/out
3. **Smart following** - Camera follows your cursor when you move near the edge
4. **Auto-unzoom** - Automatically zooms out after 2 seconds (configurable)
5. **Safe zone** - Allows small cursor movements without panning the view

## Implementation Details

Based on the **OBS zoom-to-mouse** plugin logic:

### Configuration Options

```javascript
{
  zoomLevel: 2.0,           // 2x zoom magnification
  zoomDuration: 300,        // 300ms animation duration
  holdDuration: 2000,       // Hold zoom for 2 seconds before auto-unzoom
  safeZoneRadius: 100,      // 100px safe zone (cursor can move without panning)
  followBorderDistance: 50, // Pan when cursor is 50px from edge
  followSpeed: 0.1,         // Smooth follow speed (0-1)
  autoUnzoom: true,         // Enable auto-unzoom feature
}
```

### Usage

1. **Enable the feature**: Toggle "Zoom on Click" ON in the recording panel
2. **Start recording**: Click the "Start Recording" button
3. **Click to zoom**: Click anywhere on screen to zoom in to that location
4. **Move around**: The camera smoothly follows your cursor
5. **Click again or wait**: Click again to zoom out, or wait 2 seconds for auto-unzoom

### Visual Indicator

When zoomed in, you'll see:
- Orange circular indicator around the zoom center
- Smooth fade-in/out animations
- The indicator follows the cursor position

## Technical Architecture

### Files

- **`zoom-handler.js`** - Main zoom logic and animation controller
- **`content.js`** - Integration with recording system
- **`manifest.json`** - Loads zoom handler script

### How It Works

1. **Mouse tracking**: Listens to `click` and `mousemove` events
2. **CSS transforms**: Applies `scale()` and `transform-origin` to document
3. **Animation loop**: Uses `requestAnimationFrame` for smooth 60fps animations
4. **State management**: Tracks zoom level, position, and timing

### Zoom Transform

The zoom effect uses CSS transforms:

```javascript
document.documentElement.style.transform = `scale(${zoomLevel})`;
document.documentElement.style.transformOrigin = `${clickX}px ${clickY}px`;
```

This creates a "camera zoom" effect by scaling the entire page from the click point.

## Customization

You can customize the zoom behavior by modifying the config in `zoom-handler.js`:

```javascript
this.config = {
  zoomLevel: 2.5,        // Zoom in 2.5x instead of 2x
  holdDuration: 3000,    // Hold for 3 seconds
  autoUnzoom: false,     // Never auto-unzoom (manual click to zoom out)
  // ... other options
};
```

## Benefits for Developers

Perfect for:
- **Bug reports** - Zoom in on error messages and UI glitches
- **Code reviews** - Highlight specific lines of code
- **Tutorials** - Draw attention to important UI elements
- **Demos** - Emphasize key features during presentations

## Future Enhancements

Potential improvements:
- [ ] Keyboard shortcut to toggle zoom (Alt+Z)
- [ ] Variable zoom levels (2x, 3x, 4x)
- [ ] Zoom rectangle visualization
- [ ] Post-recording zoom editing
- [ ] Export zoom markers for editing software

## Credits

Inspired by:
- [OBS zoom-to-mouse](https://github.com/BlankSourceCode/obs-zoom-to-mouse) - Original Lua implementation
- [screen-demo](https://github.com/njraladdin/screen-demo) - Open-source Screen.studio alternative
- [Screenity](https://github.com/alyssaxuu/screenity) - Chrome extension with zoom features

## License

MIT License - Same as ScreenSense project
