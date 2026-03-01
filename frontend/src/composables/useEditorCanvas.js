import { ref } from 'vue'
import { useEditorState } from './useEditorState'

export function useEditorCanvas() {
  const state = useEditorState()
  const {
    canvasEl, videoWrapper, videoEl, items, selectedItemId, selectedItem,
    activeTool, duration, isPlaying, videoReady, currentTime,
  } = state

  // Interaction state
  const isDrawing = ref(false)
  const drawStart = ref(null)
  const drawCurrent = ref(null)
  const isDragging = ref(false)
  const dragOffset = ref({ x: 0, y: 0 })
  const isResizing = ref(false)
  const resizeHandle = ref(null)

  let animFrame = null

  function resizeCanvas() {
    const canvas = canvasEl.value
    const wrapper = videoWrapper.value
    if (!canvas || !wrapper) return
    canvas.width = wrapper.clientWidth
    canvas.height = wrapper.clientHeight
  }

  function startRenderLoop() {
    const loop = () => {
      renderCanvas()
      animFrame = requestAnimationFrame(loop)
    }
    loop()
  }

  function stopRenderLoop() {
    if (animFrame) {
      cancelAnimationFrame(animFrame)
      animFrame = null
    }
  }

  // --- Canvas coordinate helpers ---

  function getCanvasPercent(e) {
    const canvas = canvasEl.value
    if (!canvas) return { x: 0, y: 0 }
    const rect = canvas.getBoundingClientRect()
    return {
      x: ((e.clientX - rect.left) / rect.width) * 100,
      y: ((e.clientY - rect.top) / rect.height) * 100,
    }
  }

  function getTouchPercent(e) {
    const t = e.touches[0] || e.changedTouches[0]
    if (!t) return { x: 0, y: 0 }
    const canvas = canvasEl.value
    if (!canvas) return { x: 0, y: 0 }
    const rect = canvas.getBoundingClientRect()
    return {
      x: ((t.clientX - rect.left) / rect.width) * 100,
      y: ((t.clientY - rect.top) / rect.height) * 100,
    }
  }

  // --- Hit testing ---

  function hitTest(pos) {
    for (let i = items.value.length - 1; i >= 0; i--) {
      const item = items.value[i]
      if (item.type === 'text') {
        // Use computed bounding box from renderCanvas if available
        if (item._bbox) {
          const b = item._bbox
          if (pos.x >= b.x && pos.x <= b.x + b.width && pos.y >= b.y && pos.y <= b.y + b.height) {
            return item
          }
        }
      } else {
        if (pos.x >= item.x && pos.x <= item.x + item.width && pos.y >= item.y && pos.y <= item.y + item.height) {
          return item
        }
      }
    }
    return null
  }

  function hitTestResize(pos, item) {
    if (!item || item.type === 'text') return null
    const hs = 2
    for (const c of [
      { name: 'se', x: item.x + item.width, y: item.y + item.height },
      { name: 'sw', x: item.x, y: item.y + item.height },
      { name: 'ne', x: item.x + item.width, y: item.y },
      { name: 'nw', x: item.x, y: item.y },
    ]) {
      if (Math.abs(pos.x - c.x) < hs && Math.abs(pos.y - c.y) < hs) return c.name
    }
    return null
  }

  // --- Mouse handlers ---

  function onCanvasMouseDown(e) {
    const pos = getCanvasPercent(e)

    if (selectedItem.value) {
      const handle = hitTestResize(pos, selectedItem.value)
      if (handle) {
        isResizing.value = true
        resizeHandle.value = handle
        return
      }
    }

    const hit = hitTest(pos)
    if (hit) {
      selectedItemId.value = hit.id
      isDragging.value = true
      dragOffset.value = { x: pos.x - hit.x, y: pos.y - hit.y }
      return
    }

    if (activeTool.value === 'text') {
      const newItem = {
        id: state.getNextId(),
        type: 'text',
        text: 'Text',
        x: round1(pos.x),
        y: round1(pos.y),
        font_size: 32,
        font_color: '#ffffff',
        background_color: '#000000',
        has_background: true,
        start_time: 0,
        end_time: duration.value,
        entireVideo: true,
      }
      items.value.push(newItem)
      selectedItemId.value = newItem.id
      return
    }

    if (activeTool.value === 'blur') {
      isDrawing.value = true
      drawStart.value = pos
      drawCurrent.value = pos
      selectedItemId.value = null
    }
  }

  function onCanvasMouseMove(e) {
    const pos = getCanvasPercent(e)
    if (isDrawing.value) {
      drawCurrent.value = pos
      return
    }
    if (isDragging.value && selectedItem.value) {
      const item = selectedItem.value
      if (item.type === 'text') {
        item.x = clamp(pos.x - dragOffset.value.x, 0, 95)
        item.y = clamp(pos.y - dragOffset.value.y, 0, 95)
      } else {
        item.x = clamp(pos.x - dragOffset.value.x, 0, 100 - item.width)
        item.y = clamp(pos.y - dragOffset.value.y, 0, 100 - item.height)
      }
      return
    }
    if (isResizing.value && selectedItem.value && resizeHandle.value) {
      applyResize(pos)
    }
  }

  function onCanvasMouseUp() {
    if (isDrawing.value && drawStart.value && drawCurrent.value) {
      const x = Math.min(drawStart.value.x, drawCurrent.value.x)
      const y = Math.min(drawStart.value.y, drawCurrent.value.y)
      const w = Math.abs(drawCurrent.value.x - drawStart.value.x)
      const h = Math.abs(drawCurrent.value.y - drawStart.value.y)
      if (w > 1 && h > 1) {
        const newItem = {
          id: state.getNextId(),
          type: 'blur',
          x: round1(x),
          y: round1(y),
          width: round1(w),
          height: round1(h),
          start_time: 0,
          end_time: duration.value,
          entireVideo: true,
        }
        items.value.push(newItem)
        selectedItemId.value = newItem.id
      }
    }
    isDrawing.value = false
    isDragging.value = false
    isResizing.value = false
    drawStart.value = null
    drawCurrent.value = null
    resizeHandle.value = null
  }

  // --- Touch handlers ---

  function onTouchStart(e) {
    const pos = getTouchPercent(e)
    if (selectedItem.value) {
      const h = hitTestResize(pos, selectedItem.value)
      if (h) {
        isResizing.value = true
        resizeHandle.value = h
        return
      }
    }
    const hit = hitTest(pos)
    if (hit) {
      selectedItemId.value = hit.id
      isDragging.value = true
      dragOffset.value = { x: pos.x - hit.x, y: pos.y - hit.y }
      return
    }
    if (activeTool.value === 'text') {
      const newItem = {
        id: state.getNextId(),
        type: 'text',
        text: 'Text',
        x: round1(pos.x),
        y: round1(pos.y),
        font_size: 32,
        font_color: '#ffffff',
        background_color: '#000000',
        has_background: true,
        start_time: 0,
        end_time: duration.value,
        entireVideo: true,
      }
      items.value.push(newItem)
      selectedItemId.value = newItem.id
      return
    }
    if (activeTool.value === 'blur') {
      isDrawing.value = true
      drawStart.value = pos
      drawCurrent.value = pos
      selectedItemId.value = null
    }
  }

  function onTouchMove(e) {
    const pos = getTouchPercent(e)
    if (isDrawing.value) {
      drawCurrent.value = pos
      return
    }
    if (isDragging.value && selectedItem.value) {
      const item = selectedItem.value
      if (item.type === 'text') {
        item.x = clamp(pos.x - dragOffset.value.x, 0, 95)
        item.y = clamp(pos.y - dragOffset.value.y, 0, 95)
      } else {
        item.x = clamp(pos.x - dragOffset.value.x, 0, 100 - item.width)
        item.y = clamp(pos.y - dragOffset.value.y, 0, 100 - item.height)
      }
      return
    }
    if (isResizing.value && selectedItem.value && resizeHandle.value) {
      applyResize(pos)
    }
  }

  function onTouchEnd() {
    onCanvasMouseUp()
  }

  // --- Resize helper ---

  function applyResize(pos) {
    const item = selectedItem.value
    const h = resizeHandle.value
    if (h.includes('e')) item.width = Math.max(2, Math.min(100 - item.x, pos.x - item.x))
    if (h.includes('w')) {
      const nx = clamp(pos.x, 0, item.x + item.width - 2)
      item.width += item.x - nx
      item.x = nx
    }
    if (h.includes('s')) item.height = Math.max(2, Math.min(100 - item.y, pos.y - item.y))
    if (h.includes('n')) {
      const ny = clamp(pos.y, 0, item.y + item.height - 2)
      item.height += item.y - ny
      item.y = ny
    }
  }

  // --- Canvas rendering ---

  function renderCanvas() {
    const canvas = canvasEl.value
    if (!canvas) return
    const ctx = canvas.getContext('2d')
    const w = canvas.width
    const h = canvas.height
    if (w === 0 || h === 0) {
      resizeCanvas()
      return
    }

    ctx.clearRect(0, 0, w, h)

    for (const item of items.value) {
      const isSelected = item.id === selectedItemId.value

      if (item.type === 'text') {
        const px = (item.x / 100) * w
        const py = (item.y / 100) * h
        const fontSize = Math.max(10, (item.font_size / 100) * h * 0.8)

        ctx.font = `bold ${fontSize}px sans-serif`
        const metrics = ctx.measureText(item.text || 'Text')
        const tw = metrics.width + 16
        const th = fontSize + 12
        const bx = px - 4
        const by = py - fontSize - 2

        // Store computed bounding box in percentage coordinates for hit-testing
        item._bbox = {
          x: (bx / w) * 100,
          y: (by / h) * 100,
          width: (tw / w) * 100,
          height: (th / h) * 100,
        }

        if (item.has_background && item.background_color) {
          ctx.fillStyle = item.background_color + '80'
          ctx.fillRect(bx, by, tw, th)
        }

        ctx.fillStyle = item.font_color || '#ffffff'
        ctx.fillText(item.text || 'Text', px + 4, py)

        ctx.strokeStyle = isSelected ? '#f97316' : '#a855f7'
        ctx.lineWidth = isSelected ? 2 : 1
        ctx.setLineDash(isSelected ? [] : [4, 4])
        ctx.strokeRect(bx, by, tw, th)
        ctx.setLineDash([])
      } else {
        const px = (item.x / 100) * w
        const py = (item.y / 100) * h
        const pw = (item.width / 100) * w
        const ph = (item.height / 100) * h

        if (item.type === 'blur') {
          ctx.fillStyle = isSelected ? 'rgba(59,130,246,0.25)' : 'rgba(59,130,246,0.15)'
          ctx.fillRect(px, py, pw, ph)
          ctx.strokeStyle = isSelected ? '#f97316' : '#3b82f6'
          ctx.lineWidth = isSelected ? 2 : 1
          ctx.setLineDash(isSelected ? [] : [4, 4])
          ctx.strokeRect(px, py, pw, ph)
          ctx.setLineDash([])
        } else {
          ctx.fillStyle = isSelected ? 'rgba(34,197,94,0.15)' : 'rgba(34,197,94,0.08)'
          ctx.fillRect(px, py, pw, ph)
          ctx.strokeStyle = isSelected ? '#f97316' : '#22c55e'
          ctx.lineWidth = isSelected ? 2 : 1
          ctx.strokeRect(px, py, pw, ph)
          ctx.fillStyle = '#22c55e'
          ctx.font = '11px sans-serif'
          ctx.fillText(item.fileName || 'Overlay', px + 4, py + 14)
        }

        if (isSelected) {
          ctx.fillStyle = '#f97316'
          for (const [cx, cy] of [[px, py], [px + pw, py], [px, py + ph], [px + pw, py + ph]]) {
            ctx.fillRect(cx - 3, cy - 3, 6, 6)
          }
        }
      }
    }

    // Drawing preview
    if (isDrawing.value && drawStart.value && drawCurrent.value) {
      const sx = (Math.min(drawStart.value.x, drawCurrent.value.x) / 100) * w
      const sy = (Math.min(drawStart.value.y, drawCurrent.value.y) / 100) * h
      const sw = (Math.abs(drawCurrent.value.x - drawStart.value.x) / 100) * w
      const sh = (Math.abs(drawCurrent.value.y - drawStart.value.y) / 100) * h
      ctx.fillStyle = 'rgba(59,130,246,0.15)'
      ctx.fillRect(sx, sy, sw, sh)
      ctx.strokeStyle = '#3b82f6'
      ctx.lineWidth = 2
      ctx.setLineDash([6, 3])
      ctx.strokeRect(sx, sy, sw, sh)
      ctx.setLineDash([])
    }
  }

  function clamp(v, min, max) {
    return Math.max(min, Math.min(max, v))
  }

  function round1(v) {
    return Math.round(v * 10) / 10
  }

  return {
    // Interaction state
    isDrawing,
    drawStart,
    drawCurrent,
    isDragging,
    isResizing,
    // Methods
    resizeCanvas,
    startRenderLoop,
    stopRenderLoop,
    renderCanvas,
    onCanvasMouseDown,
    onCanvasMouseMove,
    onCanvasMouseUp,
    onTouchStart,
    onTouchMove,
    onTouchEnd,
  }
}
