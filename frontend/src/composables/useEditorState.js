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
  const activeTool = ref('style')
  const showFlow = ref(true)
  const items = ref([])
  const selectedItemId = ref(null)
  let nextItemId = 1

  // Overlay files
  const overlayFiles = ref([])

  // Style state — background, padding, roundness
  const styleBackground = ref({ type: 'none', color: '#000000', gradientFrom: '#1e293b', gradientTo: '#0f172a', gradientDirection: 'br', imageUrl: '' })
  const stylePadding = ref(80) // 0-120 px
  const styleRoundness = ref(11) // 0-32 px border radius on the video
  const styleShadow = ref(true)

  // Camera style state
  const cameraEnabled = ref(true) // show/hide camera in editor preview
  const cameraPosition = ref('bottom-left') // 'bottom-left', 'bottom-right', 'top-left', 'top-right'
  const cameraSize = ref(25) // percentage of video width
  const cameraRoundness = ref(18) // border radius
  const cameraShape = ref('portrait') // 'portrait', 'circle', 'square'

  // Preset backgrounds
  // Presets from screenshot editor
  const bgPresetColors = [
    '#ffffff','#f1f5f9','#e2e8f0','#f8fafc',
    '#1e293b','#0f172a','#111827','#000000',
    '#6366f1','#2563eb','#f43f5e','#e91e63',
    '#f59e0b','#ffd700','#10b981','#00bcd4',
  ]
  const bgPresetGradients = [
    { from: '#f2b8ff', to: '#e9e4fe', dir: 'b' },
    { from: '#3de5b3', to: '#fee899', dir: 'b' },
    { from: '#9fbdd3', to: '#ebe6e2', dir: 'b' },
    { from: '#f0e2cf', to: '#f4d5af', dir: 'b' },
    { from: '#fbe0ff', to: '#92b4e9', dir: 'b' },
    { from: '#01befc', to: '#fc7efc', dir: 'b' },
    { from: '#b9fbc0', to: '#a3c4f3', dir: 'b' },
    { from: '#adf285', to: '#5346bf', dir: 'b' },
    { from: '#f5e7ff', to: '#ff94be', dir: 'br' },
    { from: '#799aff', to: '#e8de90', dir: 'br' },
    { from: '#925a9e', to: '#ff9696', dir: 'br' },
    { from: '#b9fbc0', to: '#a3c4f3', dir: 'br' },
  ]
  const bgPresetImages = [
    'https://pika.style/backgrounds/1-macos.svg',
    'https://pika.style/backgrounds/5-macos.png',
    'https://pika.style/backgrounds/1-art.jpg',
    'https://pika.style/backgrounds/2-art.jpg',
    'https://pika.style/backgrounds/5-abstract.png',
    'https://pika.style/backgrounds/4-abstract.png',
    'https://pika.style/backgrounds/3-abstract.png',
    'https://pika.style/backgrounds/2-abstract.png',
    'https://pika.style/backgrounds/8-abstract.jpg',
    'https://pika.style/backgrounds/9-abstract.jpg',
    'https://pika.style/backgrounds/maitris/7.png',
    'https://pika.style/backgrounds/maitris/2.png',
  ]

  // Trim state
  const trimEnabled = ref(false)
  const trimStart = ref(0)
  const trimEnd = ref(0)

  // Merge state — multiple videos
  const mergeVideos = ref([]) // array of { id, title, duration, thumbnail_url }
  const mainVideoIndex = ref(0) // position of main video in concat sequence

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

  function addMergeVideo(video) {
    if (mergeVideos.value.some(v => v.id === video.id)) return
    mergeVideos.value.push({
      id: video.id,
      title: video.title,
      duration: video.duration || 10,
      thumbnail_url: video.thumbnail_url || video.thumbnail || null,
    })
  }

  function removeMergeVideo(id) {
    const idx = mergeVideos.value.findIndex(v => v.id === id)
    if (idx === -1) return
    mergeVideos.value.splice(idx, 1)
    // Adjust mainVideoIndex if items before it were removed
    if (mainVideoIndex.value > mergeVideos.value.length) {
      mainVideoIndex.value = mergeVideos.value.length
    }
  }

  function reorderVideoBlocks(newOrder) {
    // newOrder is array of block objects, each has { id, isMain }
    const mainIdx = newOrder.findIndex(b => b.isMain)
    mainVideoIndex.value = mainIdx >= 0 ? mainIdx : 0
    // Rebuild mergeVideos in new order (excluding the main video block)
    const reordered = newOrder.filter(b => !b.isMain).map(b => {
      return mergeVideos.value.find(v => v.id === b.id)
    }).filter(Boolean)
    mergeVideos.value = reordered
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
    showFlow,
    items,
    selectedItemId,
    overlayFiles,
    trimEnabled,
    trimStart,
    trimEnd,
    mergeVideos,
    mainVideoIndex,
    isApplying,
    applyProgress,
    processingMode,
    // Style state
    styleBackground,
    stylePadding,
    styleRoundness,
    styleShadow,
    cameraEnabled,
    cameraPosition,
    cameraSize,
    cameraRoundness,
    cameraShape,
    bgPresetColors,
    bgPresetGradients,
    bgPresetImages,
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
    addMergeVideo,
    removeMergeVideo,
    reorderVideoBlocks,
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
