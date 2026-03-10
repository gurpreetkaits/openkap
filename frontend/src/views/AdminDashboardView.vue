<template>
  <div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
      <p class="text-sm text-gray-500 mt-1">Platform overview and statistics</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-2 border-orange-600 border-t-transparent"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
      <p class="text-sm text-red-700">{{ error }}</p>
      <button @click="loadStats" class="mt-3 text-sm font-medium text-red-600 hover:text-red-700">Try again</button>
    </div>

    <template v-else-if="stats">
      <!-- Overview Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        <StatCard label="Total Users" :value="stats.overview.total_users" icon="users" />
        <StatCard label="Active Users (30d)" :value="stats.overview.active_users_30d" icon="activity" />
        <StatCard label="New This Month" :value="stats.overview.new_users_this_month" icon="user-plus" />
        <StatCard label="Total Videos" :value="stats.overview.total_videos" icon="video" />
        <StatCard label="Videos This Month" :value="stats.overview.videos_this_month" icon="film" />
        <StatCard label="Storage Used" :value="stats.overview.total_storage_formatted" icon="database" />
      </div>

      <!-- Subscriptions & Processing -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Subscription Breakdown -->
        <div class="bg-white border border-gray-200 rounded-xl p-6">
          <h2 class="text-sm font-semibold text-gray-900 mb-4">Subscriptions</h2>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-gray-300"></div>
                <span class="text-sm text-gray-700">Free</span>
              </div>
              <span class="text-sm font-semibold text-gray-900">{{ stats.subscriptions.free }}</span>
            </div>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                <span class="text-sm text-gray-700">Pro</span>
              </div>
              <span class="text-sm font-semibold text-gray-900">{{ stats.subscriptions.pro }}</span>
            </div>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                <span class="text-sm text-gray-700">Teams</span>
              </div>
              <span class="text-sm font-semibold text-gray-900">{{ stats.subscriptions.teams }}</span>
            </div>
          </div>
          <!-- Subscription Bar -->
          <div class="mt-4 flex h-2 rounded-full overflow-hidden bg-gray-100">
            <div class="bg-gray-300" :style="{ width: subPercent('free') + '%' }"></div>
            <div class="bg-orange-500" :style="{ width: subPercent('pro') + '%' }"></div>
            <div class="bg-purple-500" :style="{ width: subPercent('teams') + '%' }"></div>
          </div>
        </div>

        <!-- Video Processing -->
        <div class="bg-white border border-gray-200 rounded-xl p-6">
          <h2 class="text-sm font-semibold text-gray-900 mb-4">Video Processing</h2>
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
              <p class="text-xs text-yellow-700 font-medium">Pending Conversion</p>
              <p class="text-xl font-bold text-yellow-900 mt-1">{{ stats.processing.pending_conversion }}</p>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
              <p class="text-xs text-blue-700 font-medium">Converting</p>
              <p class="text-xl font-bold text-blue-900 mt-1">{{ stats.processing.converting }}</p>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
              <p class="text-xs text-yellow-700 font-medium">Pending HLS</p>
              <p class="text-xl font-bold text-yellow-900 mt-1">{{ stats.processing.pending_hls }}</p>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
              <p class="text-xs text-blue-700 font-medium">HLS Processing</p>
              <p class="text-xl font-bold text-blue-900 mt-1">{{ stats.processing.hls_processing }}</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
              <p class="text-xs text-red-700 font-medium">Conversion Failed</p>
              <p class="text-xl font-bold text-red-900 mt-1">{{ stats.processing.conversion_failed }}</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
              <p class="text-xs text-red-700 font-medium">HLS Failed</p>
              <p class="text-xl font-bold text-red-900 mt-1">{{ stats.processing.hls_failed }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Growth Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white border border-gray-200 rounded-xl p-6">
          <h2 class="text-sm font-semibold text-gray-900 mb-4">User Growth (6 months)</h2>
          <BarChart :data="stats.growth.users" color="#ea580c" />
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-6">
          <h2 class="text-sm font-semibold text-gray-900 mb-4">Video Growth (6 months)</h2>
          <BarChart :data="stats.growth.videos" color="#8b5cf6" />
        </div>
      </div>

      <!-- Recent Signups -->
      <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Recent Signups</h2>
        <div v-if="stats.recent_signups.length === 0" class="text-sm text-gray-500 py-4 text-center">
          No recent signups
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100">
                <th class="text-left py-2 pr-4 text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                <th class="text-left py-2 pr-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="text-left py-2 pr-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                <th class="text-left py-2 text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in stats.recent_signups" :key="user.id" class="border-b border-gray-50 last:border-0">
                <td class="py-2.5 pr-4">
                  <div class="flex items-center gap-2">
                    <img v-if="user.avatar" :src="user.avatar" class="w-6 h-6 rounded-full object-cover" />
                    <div v-else class="w-6 h-6 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
                      <span class="text-[10px] font-bold text-white">{{ (user.name || 'U').charAt(0).toUpperCase() }}</span>
                    </div>
                    <span class="font-medium text-gray-900">{{ user.name }}</span>
                  </div>
                </td>
                <td class="py-2.5 pr-4 text-gray-600">{{ user.email }}</td>
                <td class="py-2.5 pr-4">
                  <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="planBadgeClass(user.plan_type)">
                    {{ user.plan_type }}
                  </span>
                </td>
                <td class="py-2.5 text-gray-500">{{ formatDate(user.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import adminService from '@/services/adminService'

// Stat Card Component
const StatCard = {
  props: ['label', 'value', 'icon'],
  template: `
    <div class="bg-white border border-gray-200 rounded-xl p-5">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ label }}</span>
        <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center">
          <svg v-if="icon === 'users'" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg v-else-if="icon === 'activity'" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
          <svg v-else-if="icon === 'user-plus'" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
          <svg v-else-if="icon === 'video'" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
          <svg v-else-if="icon === 'film'" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
          <svg v-else-if="icon === 'database'" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
        </div>
      </div>
      <p class="text-2xl font-bold text-gray-900">{{ typeof value === 'number' ? value.toLocaleString() : value }}</p>
    </div>
  `
}

// Bar Chart Component (inline SVG)
const BarChart = {
  props: ['data', 'color'],
  setup(props) {
    const maxCount = computed(() => {
      if (!props.data || props.data.length === 0) return 1
      return Math.max(...props.data.map(d => d.count), 1)
    })

    const formatMonth = (monthStr) => {
      const [year, month] = monthStr.split('-')
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
      return months[parseInt(month) - 1] || month
    }

    return { maxCount, formatMonth }
  },
  template: `
    <div v-if="!data || data.length === 0" class="text-sm text-gray-400 py-8 text-center">No data yet</div>
    <div v-else class="flex items-end gap-2 h-32">
      <div v-for="item in data" :key="item.month" class="flex-1 flex flex-col items-center gap-1">
        <span class="text-[10px] font-medium text-gray-600">{{ item.count }}</span>
        <div
          class="w-full rounded-t-md transition-all duration-300 min-h-[4px]"
          :style="{ height: Math.max((item.count / maxCount) * 100, 4) + '%', backgroundColor: color }"
        ></div>
        <span class="text-[10px] text-gray-400">{{ formatMonth(item.month) }}</span>
      </div>
    </div>
  `
}

export default {
  name: 'AdminDashboardView',
  components: { StatCard, BarChart },
  setup() {
    const stats = ref(null)
    const loading = ref(true)
    const error = ref(null)

    const loadStats = async () => {
      loading.value = true
      error.value = null
      try {
        stats.value = await adminService.getDashboardStats()
      } catch (e) {
        error.value = 'Failed to load dashboard statistics.'
      } finally {
        loading.value = false
      }
    }

    const subTotal = computed(() => {
      if (!stats.value) return 1
      const s = stats.value.subscriptions
      return (s.free + s.pro + s.teams) || 1
    })

    const subPercent = (plan) => {
      if (!stats.value) return 0
      return (stats.value.subscriptions[plan] / subTotal.value) * 100
    }

    const planBadgeClass = (plan) => {
      switch (plan) {
        case 'pro': return 'bg-orange-100 text-orange-700'
        case 'teams': return 'bg-purple-100 text-purple-700'
        default: return 'bg-gray-100 text-gray-600'
      }
    }

    const formatDate = (dateStr) => {
      const d = new Date(dateStr)
      return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    }

    onMounted(loadStats)

    return {
      stats,
      loading,
      error,
      loadStats,
      subPercent,
      planBadgeClass,
      formatDate
    }
  }
}
</script>
