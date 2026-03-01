import { ref, computed, provide, inject } from 'vue'

const EDITOR_STATE_KEY = Symbol('editorState')

export function createEditorState() {
  // Video data
  const video = ref(null)
  const videoEl = ref(null)
  const canvasEl = ref(null)
  const videoWrapper = ref(null)

  // Video state
  const videoReady = ref(false)
  const videoWidth = ref(0)
  const videoHeight = ref(0)
  const duration = ref(0)
  const currentTime = ref(0)
  const isPlaying = ref(false)

  // Editor state
  const loading = ref(true)
  const error = ref(null)
  const activeTool = ref('blur')
  const items = ref([])
  const selectedItemId = ref(null)
  let nextItemId = 1

  // Overlay files
  const overlayFiles = ref([])

  // Trim state
  const trimEnabled = ref(false)
  const trimStart = ref(0)
  const trimEnd = ref(0)

  // Merge state
  const mergeVideoId = ref(null)
  const mergeVideo = ref(null)

  // Processing state
  const isApplying = ref(false)
  const applyProgress = ref(0)
  const processingMode = ref(null) // 'sync' | 'async'

  // Computed
  const selectedItem = computed(() => {
    if (!selectedItemId.value) return null
    return items.value.find(i => i.id === selectedItemId.value) || null
  })

  const seekPercent = computed(() => {
    if (!duration.value) return 0
    return (currentTime.value / duration.value) * 100
  })

  const videoWrapperStyle = computed(() => {
    if (!videoWidth.value || !videoHeight.value) return { width: '100%', maxHeight: '100%' }
    return {
      aspectRatio: `${videoWidth.value / videoHeight.value}`,
      maxWidth: '100%',
      maxHeight: '100%',
    }
  })

  // Methods
  function getNextId() {
    return nextItemId++
  }

  function selectItem(id) {
    selectedItemId.value = id
  }

  function deleteItem(id) {
    items.value = items.value.filter(i => i.id !== id)
    if (selectedItemId.value === id) selectedItemId.value = null
  }

  function addTextItem() {
    const newItem = {
      id: getNextId(),
      type: 'text',
      text: 'Text',
      x: 10,
      y: 50,
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
    activeTool.value = 'text'
  }

  function addOverlayFile(e) {
    const file = e.target.files?.[0]
    if (!file) return
    const fileIndex = overlayFiles.value.length
    overlayFiles.value.push(file)
    const newItem = {
      id: getNextId(),
      type: 'overlay',
      fileName: file.name,
      fileIndex,
      x: 10,
      y: 10,
      width: 30,
      height: 30,
      start_time: 0,
      end_time: duration.value,
      entireVideo: true,
    }
    items.value.push(newItem)
    selectedItemId.value = newItem.id
    activeTool.value = 'overlay'
    e.target.value = ''
  }

  function getItemLabel(item) {
    const idx = items.value.filter(i => i.type === item.type).indexOf(item) + 1
    if (item.type === 'blur') return `Blur ${idx}`
    if (item.type === 'text') return (item.text || 'Text').substring(0, 20)
    return item.fileName || `Overlay ${idx}`
  }

  function formatTime(seconds) {
    if (!seconds || isNaN(seconds)) return '0:00'
    const m = Math.floor(seconds / 60)
    const s = Math.floor(seconds % 60)
    return `${m}:${s.toString().padStart(2, '0')}`
  }

  function togglePlay() {
    const el = videoEl.value
    if (!el) return
    if (el.paused) {
      el.play()
      isPlaying.value = true
    } else {
      el.pause()
      isPlaying.value = false
    }
  }

  const state = {
    // Refs
    video,
    videoEl,
    canvasEl,
    videoWrapper,
    videoReady,
    videoWidth,
    videoHeight,
    duration,
    currentTime,
    isPlaying,
    loading,
    error,
    activeTool,
    items,
    selectedItemId,
    overlayFiles,
    trimEnabled,
    trimStart,
    trimEnd,
    mergeVideoId,
    mergeVideo,
    isApplying,
    applyProgress,
    processingMode,
    // Computed
    selectedItem,
    seekPercent,
    videoWrapperStyle,
    // Methods
    getNextId,
    selectItem,
    deleteItem,
    addTextItem,
    addOverlayFile,
    getItemLabel,
    formatTime,
    togglePlay,
  }

  provide(EDITOR_STATE_KEY, state)
  return state
}

export function useEditorState() {
  const state = inject(EDITOR_STATE_KEY)
  if (!state) {
    throw new Error('useEditorState must be used within a component that calls createEditorState')
  }
  return state
}
