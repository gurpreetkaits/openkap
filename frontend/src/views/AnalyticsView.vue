<template>
  <div class="animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between mb-5">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Analytics</h1>
        <p class="text-sm text-gray-500 mt-0.5">Insights across your videos</p>
      </div>
      <button
        @click="fetchAnalytics"
        :disabled="loading"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
      >
        <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        Refresh
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 animate-pulse">
          <div class="h-3 bg-gray-100 rounded w-24 mb-3"></div>
          <div class="h-7 bg-gray-100 rounded w-16 mb-1"></div>
          <div class="h-3 bg-gray-100 rounded w-20"></div>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 animate-pulse">
        <div class="h-3 bg-gray-100 rounded w-32 mb-4"></div>
        <div class="h-48 bg-gray-50 rounded-lg"></div>
      </div>
    </div>

    <!-- Coming Soon / Empty State -->
    <div v-else-if="isEmpty" class="bg-white rounded-xl border border-gray-100 shadow-sm p-16 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-50 mb-4">
        <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
      </div>
      <h3 class="text-base font-semibold text-gray-900 mb-1">No analytics yet</h3>
      <p class="text-sm text-gray-500 max-w-xs mx-auto">Share your videos and analytics will appear here once viewers start watching.</p>
    </div>

    <!-- Analytics Content -->
    <div v-else class="space-y-5">

      <!-- Overview Stats -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Views -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 relative overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-br from-orange-50/60 to-transparent pointer-events-none"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Views</span>
              <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
              </div>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ formatNumber(analytics.overview.total_views) }}</div>
            <div class="text-xs text-gray-400 mt-0.5">All time</div>
          </div>
        </div>

        <!-- Unique Viewers -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 relative overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-50/60 to-transparent pointer-events-none"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Unique Viewers</span>
              <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </div>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ formatNumber(analytics.overview.unique_viewers) }}</div>
            <div class="text-xs text-gray-400 mt-0.5">Distinct viewers</div>
          </div>
        </div>

        <!-- Avg Watch Time -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 relative overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-br from-green-50/60 to-transparent pointer-events-none"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Avg Watch Time</span>
              <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ formatDuration(analytics.overview.avg_watch_time) }}</div>
            <div class="text-xs text-gray-400 mt-0.5">Per view</div>
          </div>
        </div>

        <!-- Total Videos -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 relative overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-br from-purple-50/60 to-transparent pointer-events-none"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Videos</span>
              <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.845v6.31a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
              </div>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ formatNumber(analytics.overview.total_videos) }}</div>
            <div class="text-xs text-gray-400 mt-0.5">Published</div>
          </div>
        </div>
      </div>

      <!-- Views Over Time Chart -->
      <div v-if="analytics.views_over_time && analytics.views_over_time.length > 0" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Views Over Time</h2>
          <span class="text-xs text-gray-400">Last 30 days</span>
        </div>
        <div class="border-t border-gray-100 pt-4">
          <!-- SVG Chart -->
          <div class="relative" ref="chartContainer">
            <svg
              ref="chartSvg"
              :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
              class="w-full"
              :style="{ height: '200px' }"
              @mousemove="onChartMouseMove"
              @mouseleave="onChartMouseLeave"
            >
              <defs>
                <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#f97316" stop-opacity="0.2"/>
                  <stop offset="100%" stop-color="#f97316" stop-opacity="0"/>
                </linearGradient>
              </defs>

              <!-- Grid lines -->
              <g>
                <line
                  v-for="(tick, i) in yTicks"
                  :key="`ygrid-${i}`"
                  :x1="chartPadding.left"
                  :y1="yScale(tick)"
                  :x2="chartWidth - chartPadding.right"
                  :y2="yScale(tick)"
                  stroke="#f3f4f6"
                  stroke-width="1"
                />
              </g>

              <!-- Y axis labels -->
              <g>
                <text
                  v-for="(tick, i) in yTicks"
                  :key="`ylabel-${i}`"
                  :x="chartPadding.left - 8"
                  :y="yScale(tick) + 4"
                  text-anchor="end"
                  fill="#9ca3af"
                  font-size="10"
                >{{ formatNumber(tick) }}</text>
              </g>

              <!-- X axis labels (every 5 days) -->
              <g>
                <text
                  v-for="(point, i) in xAxisLabels"
                  :key="`xlabel-${i}`"
                  :x="xScale(point.index)"
                  :y="chartHeight - chartPadding.bottom + 14"
                  text-anchor="middle"
                  fill="#9ca3af"
                  font-size="10"
                >{{ point.label }}</text>
              </g>

              <!-- Area fill -->
              <path
                v-if="areaPath"
                :d="areaPath"
                fill="url(#areaGradient)"
              />

              <!-- Line -->
              <path
                v-if="linePath"
                :d="linePath"
                fill="none"
                stroke="#f97316"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />

              <!-- Data dots -->
              <circle
                v-for="(point, i) in chartPoints"
                :key="`dot-${i}`"
                :cx="point.x"
                :cy="point.y"
                r="3"
                fill="#f97316"
                stroke="white"
                stroke-width="1.5"
              />

              <!-- Tooltip vertical line -->
              <g v-if="tooltip.visible">
                <line
                  :x1="tooltip.x"
                  :y1="chartPadding.top"
                  :x2="tooltip.x"
                  :y2="chartHeight - chartPadding.bottom"
                  stroke="#e5e7eb"
                  stroke-width="1"
                  stroke-dasharray="4 2"
                />
                <circle
                  :cx="tooltip.x"
                  :cy="tooltip.y"
                  r="5"
                  fill="#f97316"
                  stroke="white"
                  stroke-width="2"
                />
              </g>
            </svg>

            <!-- Tooltip bubble -->
            <div
              v-if="tooltip.visible"
              class="absolute pointer-events-none bg-gray-900 text-white text-xs rounded-lg px-2.5 py-1.5 shadow-lg whitespace-nowrap"
              :style="{
                left: `${tooltip.bubbleLeft}px`,
                top: `${tooltip.bubbleTop}px`,
                transform: 'translateX(-50%)'
              }"
            >
              <div class="font-semibold">{{ tooltip.views }} views</div>
              <div class="text-gray-400">{{ tooltip.date }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom grid: Countries + Top Videos -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        <!-- Top Countries -->
        <div v-if="analytics.top_countries && analytics.top_countries.length > 0" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
          <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4 pb-3 border-b border-gray-100">Top Countries</h2>
          <div class="space-y-3">
            <div
              v-for="country in analytics.top_countries"
              :key="country.country_code"
              class="group"
            >
              <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-2 min-w-0">
                  <span class="text-base leading-none flex-shrink-0">{{ countryFlag(country.country_code) }}</span>
                  <span class="text-sm text-gray-700 font-medium truncate">{{ country.country }}</span>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                  <span class="text-xs text-gray-500">{{ formatNumber(country.views) }}</span>
                  <span class="text-xs font-semibold text-gray-400 w-10 text-right">{{ country.percentage.toFixed(1) }}%</span>
                </div>
              </div>
              <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div
                  class="h-full bg-orange-500 rounded-full transition-all duration-500"
                  :style="{ width: `${Math.min(country.percentage, 100)}%` }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Videos -->
        <div v-if="analytics.top_videos && analytics.top_videos.length > 0" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
          <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4 pb-3 border-b border-gray-100">Top Videos</h2>
          <div class="space-y-3">
            <div
              v-for="(video, index) in analytics.top_videos"
              :key="video.id"
              class="flex items-center gap-3 group"
            >
              <!-- Rank -->
              <span class="text-xs font-semibold text-gray-300 w-4 flex-shrink-0 text-center">{{ index + 1 }}</span>

              <!-- Thumbnail -->
              <div class="w-12 h-8 rounded-md overflow-hidden bg-gray-100 flex-shrink-0">
                <img
                  v-if="video.thumbnail"
                  :src="video.thumbnail"
                  :alt="video.title"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.845v6.31a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                  </svg>
                </div>
              </div>

              <!-- Title + bar -->
              <div class="flex-1 min-w-0">
                <div class="text-xs font-medium text-gray-700 truncate mb-1">{{ video.title }}</div>
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-1 bg-gray-100 rounded-full overflow-hidden">
                    <div
                      class="h-full bg-orange-400 rounded-full"
                      :style="{ width: `${topVideoBarWidth(video.views_count)}%` }"
                    ></div>
                  </div>
                  <span class="text-xs text-gray-400 flex-shrink-0 w-12 text-right">{{ formatNumber(video.views_count) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Views -->
      <div v-if="analytics.recent_views && analytics.recent_views.length > 0" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4 pb-3 border-b border-gray-100">Recent Views</h2>
        <div class="space-y-2">
          <div
            v-for="(view, i) in analytics.recent_views"
            :key="i"
            class="flex items-center gap-3 py-1.5"
          >
            <span class="text-base leading-none flex-shrink-0">{{ countryFlag(view.country_code) }}</span>
            <span class="text-xs text-gray-500 w-24 flex-shrink-0 truncate">{{ view.country }}</span>
            <span class="flex-1 text-xs text-gray-700 font-medium truncate min-w-0">{{ view.video_title }}</span>
            <span class="text-xs text-gray-400 flex-shrink-0">{{ timeAgo(view.viewed_at) }}</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888'

// State
const loading = ref(true)
const isEmpty = ref(false)
const analytics = ref({
  overview: { total_views: 0, unique_viewers: 0, avg_watch_time: 0, total_videos: 0 },
  views_over_time: [],
  top_countries: [],
  top_videos: [],
  recent_views: []
})

// Chart state
const chartContainer = ref(null)
const chartSvg = ref(null)
const chartWidth = 700
const chartHeight = 220
const chartPadding = { top: 16, right: 16, bottom: 28, left: 42 }

const tooltip = ref({
  visible: false,
  x: 0,
  y: 0,
  bubbleLeft: 0,
  bubbleTop: 0,
  views: 0,
  date: ''
})

// Chart computed
const chartPoints = computed(() => {
  const data = analytics.value.views_over_time
  if (!data || data.length === 0) return []
  return data.map((d, i) => ({
    x: xScale(i),
    y: yScale(d.views),
    views: d.views,
    date: d.date
  }))
})

const yMax = computed(() => {
  const data = analytics.value.views_over_time
  if (!data || data.length === 0) return 10
  return Math.max(...data.map(d => d.views), 1)
})

const yTicks = computed(() => {
  const max = yMax.value
  const count = 4
  const step = Math.ceil(max / count)
  return Array.from({ length: count + 1 }, (_, i) => i * step)
})

const xAxisLabels = computed(() => {
  const data = analytics.value.views_over_time
  if (!data || data.length === 0) return []
  const labels = []
  for (let i = 0; i < data.length; i += 5) {
    const d = new Date(data[i].date)
    labels.push({
      index: i,
      label: `${d.getMonth() + 1}/${d.getDate()}`
    })
  }
  return labels
})

const linePath = computed(() => {
  const pts = chartPoints.value
  if (pts.length < 2) return ''
  let d = `M ${pts[0].x} ${pts[0].y}`
  for (let i = 1; i < pts.length; i++) {
    const prev = pts[i - 1]
    const curr = pts[i]
    const cpx = (prev.x + curr.x) / 2
    d += ` C ${cpx} ${prev.y} ${cpx} ${curr.y} ${curr.x} ${curr.y}`
  }
  return d
})

const areaPath = computed(() => {
  const pts = chartPoints.value
  if (pts.length < 2) return ''
  const bottom = chartHeight - chartPadding.bottom
  let d = `M ${pts[0].x} ${bottom} L ${pts[0].x} ${pts[0].y}`
  for (let i = 1; i < pts.length; i++) {
    const prev = pts[i - 1]
    const curr = pts[i]
    const cpx = (prev.x + curr.x) / 2
    d += ` C ${cpx} ${prev.y} ${cpx} ${curr.y} ${curr.x} ${curr.y}`
  }
  d += ` L ${pts[pts.length - 1].x} ${bottom} Z`
  return d
})

function xScale(index) {
  const data = analytics.value.views_over_time
  const n = data.length - 1 || 1
  const range = chartWidth - chartPadding.left - chartPadding.right
  return chartPadding.left + (index / n) * range
}

function yScale(value) {
  const max = yMax.value
  const range = chartHeight - chartPadding.top - chartPadding.bottom
  return chartHeight - chartPadding.bottom - (value / max) * range
}

// Chart interactions
function onChartMouseMove(event) {
  const svgEl = chartSvg.value
  if (!svgEl) return
  const rect = svgEl.getBoundingClientRect()
  const scaleX = chartWidth / rect.width
  const mouseX = (event.clientX - rect.left) * scaleX

  const pts = chartPoints.value
  if (!pts.length) return

  // Find nearest point
  let nearest = pts[0]
  let minDist = Math.abs(pts[0].x - mouseX)
  for (const pt of pts) {
    const dist = Math.abs(pt.x - mouseX)
    if (dist < minDist) {
      minDist = dist
      nearest = pt
    }
  }

  const scaleY = rect.height / chartHeight
  const bubbleX = nearest.x / scaleX
  const bubbleY = (nearest.y * scaleY) - 48

  tooltip.value = {
    visible: true,
    x: nearest.x,
    y: nearest.y,
    bubbleLeft: bubbleX,
    bubbleTop: Math.max(0, bubbleY),
    views: nearest.views,
    date: formatDate(nearest.date)
  }
}

function onChartMouseLeave() {
  tooltip.value.visible = false
}

// Top video bar widths relative to max
const topVideoMax = computed(() => {
  const videos = analytics.value.top_videos
  if (!videos || videos.length === 0) return 1
  return Math.max(...videos.map(v => v.views_count), 1)
})

function topVideoBarWidth(views) {
  return Math.round((views / topVideoMax.value) * 100)
}

// Fetch analytics
async function fetchAnalytics() {
  loading.value = true
  isEmpty.value = false
  try {
    const token = localStorage.getItem('auth_token')
    const response = await fetch(`${API_BASE_URL}/api/analytics`, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
        Accept: 'application/json'
      }
    })
    if (!response.ok) {
      isEmpty.value = true
      return
    }
    const data = await response.json()
    analytics.value = {
      overview: data.overview || { total_views: 0, unique_viewers: 0, avg_watch_time: 0, total_videos: 0 },
      views_over_time: data.views_over_time || [],
      top_countries: data.top_countries || [],
      top_videos: data.top_videos || [],
      recent_views: data.recent_views || []
    }
    // Show empty state if all zeroes and no data
    const ov = analytics.value.overview
    const hasData =
      ov.total_views > 0 ||
      analytics.value.views_over_time.some(d => d.views > 0) ||
      analytics.value.top_videos.length > 0
    isEmpty.value = !hasData
  } catch (err) {
    isEmpty.value = true
  } finally {
    loading.value = false
  }
}

// Formatters
function formatNumber(n) {
  if (n == null) return '0'
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K'
  return String(n)
}

function formatDuration(seconds) {
  if (!seconds) return '0s'
  const m = Math.floor(seconds / 60)
  const s = Math.round(seconds % 60)
  if (m === 0) return `${s}s`
  return `${m}m ${s}s`
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

function timeAgo(dateStr) {
  if (!dateStr) return ''
  const now = new Date()
  const then = new Date(dateStr)
  const diffMs = now - then
  const diffMin = Math.floor(diffMs / 60_000)
  if (diffMin < 1) return 'just now'
  if (diffMin < 60) return `${diffMin}m ago`
  const diffHr = Math.floor(diffMin / 60)
  if (diffHr < 24) return `${diffHr}h ago`
  const diffDays = Math.floor(diffHr / 24)
  if (diffDays < 30) return `${diffDays}d ago`
  return formatDate(dateStr)
}

function countryFlag(code) {
  if (!code || code.length !== 2) return '🌍'
  const codePoints = [...code.toUpperCase()].map(c => 127397 + c.charCodeAt(0))
  return String.fromCodePoint(...codePoints)
}

onMounted(() => {
  fetchAnalytics()
})
</script>
