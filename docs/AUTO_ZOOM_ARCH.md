# Auto-Zoom & Pan Architecture - ScreenSense Design Doc

## 1. Overview
The goal is to replicate the "Screen Studio" effect where the camera smoothy follows the mouse cursor and zooms in on clicks or typing, making the video feel professional and mobile-friendly.

**Core Philosophy:** 
We do **not** burn the zoom into the video permanently by default. Instead, we use a **non-destructive, metadata-driven approach**. The zoom is applied dynamically by the React Video Player. This allows:
1.  Instant availability (no 10-minute rendering wait time).
2.  User customization (User can toggle "Auto Zoom" off if it makes them dizzy).
3.  "Burn-in" only happens on final Export/Download.

---

## 2. Data Capture (The "Sidecar" File)

We need to record a separate JSON file (`events.json`) alongside the `.webm` video.

### 2.1. The Data Schema
We track mouse movements, clicks, and scroll events with their timestamps relative to the recording start.

```json
{
  "recording_id": "vid_12345",
  "resolution": { "width": 1920, "height": 1080 },
  "events": [
    {
      "t": 0.543,       // Timestamp in seconds
      "type": "move",   // Event type: move | click | scroll | focus
      "x": 450,         // X coordinate
      "y": 200          // Y coordinate
    },
    {
      "t": 1.200,
      "type": "click",
      "x": 452,
      "y": 202,
      "target": "submit-button" // Optional: DOM element ID/Class
    }
  ]
}
```

### 2.2. Extension Implementation
In the Chrome Extension `content.js`:

```javascript
let events = [];
const startTime = Date.now();

// 1. Throttle mouse moves (record 10-30 times per second, not 60)
document.addEventListener('mousemove', throttle((e) => {
    events.push({
        t: (Date.now() - startTime) / 1000,
        type: 'move',
        x: e.clientX,
        y: e.clientY
    });
}, 50));

// 2. Capture Clicks (High Priority - triggers Zoom)
document.addEventListener('click', (e) => {
    events.push({
        t: (Date.now() - startTime) / 1000,
        type: 'click',
        x: e.clientX,
        y: e.clientY
    });
});

// 3. Upload Metadata
// When recording stops, upload this JSON blob to:
// POST /api/videos/{id}/events
```

---

## 3. Dynamic Playback (The "Magic")

We create a smart React component that reads the video time and transforms the view.

### 3.1. The Math
We define a **Virtual Camera** that targets the mouse position but with **Spring Physics** (using `react-spring` or generic Lerp) to avoid jitter.

**Algorithm:**
1.  Get current video time `currentTime`.
2.  Find the mouse position `(targetX, targetY)` at that time.
3.  Calculate Zoom Factor:
    *   Default: `1.0x` (Full screen)
    *   During "Action" (Click/Typing): `1.5x` or `2.0x`
4.  Apply CSS Transform to the `<video>` element:
    ```css
    transform: scale(1.5) translate(-200px, -100px);
    transition: transform 0.5s cubic-bezier(0.25, 1, 0.5, 1);
    ```

### 3.2. React Component Strategy
```tsx
const SmartPlayer = ({ videoUrl, events }) => {
  const videoRef = useRef(null);
  const [transform, setTransform] = useState({ scale: 1, x: 0, y: 0 });

  useAnimationFrame(() => {
    const time = videoRef.current.currentTime;
    // ... calculate interpolated X/Y based on time ...
    // ... apply smoothing ...
    setTransform(calculatedTransform);
  });

  return (
    <div className="overflow-hidden w-full h-full relative border-radius-macbook">
      <video 
        ref={videoRef}
        src={videoUrl}
        style={{
          transform: `scale(${transform.scale}) translate(${transform.x}px, ${transform.y}px)`,
          transformOrigin: '0 0' 
        }}
      />
    </div>
  );
};
```

---

## 4. Exporting (Burning it in)

If the user wants to download the MP4 with the zoom effects permanently applied, we use FFmpeg on the server.

**FFmpeg Command:**
We generate a complex `zoompan` filter string based on the JSON events.

```bash
ffmpeg -i input.webm \
  -vf "zoompan=z='if(between(on,30,60),1.5,1.0)':x='if(between(on,30,60),400,iw/2)':y='...':d=1" \
  output_zoomed.mp4
```
*Note: Generating this filter string programmatically is complex but feasible for linear events.*

---

## 5. Roadmap

1.  **Step 1 (Extension):** Add Mouse Event listeners to `content.js` and upload `events.json`.
2.  **Step 2 (Backend):** Create API endpoint `POST /videos/{id}/events` and store in `video_events` table (or JSON storage).
3.  **Step 3 (Frontend):** Build the `SmartPlayer` component with a toggle switch.
