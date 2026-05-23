<template>
  <div class="animate-fade-in space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-orange-600 mb-1">
          <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
          Analytics
          <span v-if="analytics && analytics.plan" class="text-gray-400 font-medium normal-case tracking-normal">· {{ analytics.plan }} plan</span>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Know who's watching, and what's working.</h1>
        <p class="text-sm text-gray-500 mt-1 max-w-2xl">Track viewers, watch time, drop-off, and replies — turn every shared video into a feedback loop.</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <div class="inline-flex bg-white border border-gray-200 rounded-lg overflow-hidden text-sm">
          <button
            v-for="opt in periodOptions"
            :key="opt.value"
            @click="setPeriod(opt.value)"
            :class="[
              'px-3 py-1.5 transition-colors',
              days === opt.value ? 'bg-gray-900 text-white font-medium' : 'text-gray-500 hover:bg-gray-50'
            ]"
          >{{ opt.label }}</button>
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
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-4">
      <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
        <div v-for="i in 6" :key="i" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 animate-pulse">
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

    <!-- Paywall (free users) -->
    <div v-else-if="analytics && analytics.paywall" class="flex justify-center pt-4 sm:pt-12">
      <div class="relative w-full max-w-md bg-white rounded-2xl border border-gray-200 shadow-sm p-8 text-center overflow-hidden">

        <!-- Soft gradient backdrop -->
        <div class="absolute -top-20 -right-16 w-56 h-56 rounded-full bg-orange-100/60 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-12 w-56 h-56 rounded-full bg-amber-100/40 blur-3xl pointer-events-none"></div>

        <!-- Faux chart preview, blurred -->
        <div class="relative h-24 mb-6 select-none" aria-hidden="true">
          <svg viewBox="0 0 320 96" class="w-full h-full opacity-60" style="filter: blur(2px);">
            <defs>
              <linearGradient id="paywallG" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#f97316" stop-opacity="0.35"/>
                <stop offset="100%" stop-color="#f97316" stop-opacity="0"/>
              </linearGradient>
            </defs>
            <path d="M 0 70 C 40 65, 80 50, 120 55 S 200 30, 240 22 S 300 10, 320 18 L 320 96 L 0 96 Z" fill="url(#paywallG)"/>
            <path d="M 0 70 C 40 65, 80 50, 120 55 S 200 30, 240 22 S 300 10, 320 18"
              fill="none" stroke="#f97316" stroke-width="2.5" stroke-linecap="round"/>
          </svg>
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="w-12 h-12 rounded-full bg-white shadow-md ring-1 ring-gray-200 flex items-center justify-center">
              <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="relative">
          <div class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-orange-600 bg-orange-50 border border-orange-100 rounded-full px-2.5 py-1 mb-3">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 1.5l2.598 5.262 5.804.844-4.2 4.094.99 5.78L10 14.747l-5.192 2.733.99-5.78-4.2-4.094 5.804-.844L10 1.5z"/></svg>
            Pro feature
          </div>

          <h2 class="text-xl font-bold text-gray-900 mb-2 leading-tight">
            Curious who's actually watching?
          </h2>
          <p class="text-sm text-gray-500 leading-relaxed mb-6">
            Analytics is a Pro thing. Upgrade and we'll show you who opened your videos, where they dropped off, and what made people hit reply.
          </p>

          <ul class="text-left text-sm text-gray-700 space-y-2.5 mb-7">
            <li class="flex items-start gap-2.5">
              <svg class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <span>See exactly who watched, how far they got, and from where</span>
            </li>
            <li class="flex items-start gap-2.5">
              <svg class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <span>Spot the moments where viewers drop off</span>
            </li>
            <li class="flex items-start gap-2.5">
              <svg class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <span>Track replies, reactions, and which videos actually drive action</span>
            </li>
            <li class="flex items-start gap-2.5">
              <svg class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <span>Real-time viewer feed so you can follow up while it's warm</span>
            </li>
          </ul>

          <router-link
            :to="{ name: 'Subscription' }"
            class="inline-flex w-full items-center justify-center gap-2 px-5 py-3 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm"
          >
            Upgrade to Pro
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
          </router-link>

          <p class="text-[11px] text-gray-400 mt-3">No commitment — cancel anytime.</p>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else-if="isEmpty" class="bg-white rounded-xl border border-gray-100 shadow-sm p-16 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-50 mb-4">
        <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
      </div>
      <h3 class="text-base font-semibold text-gray-900 mb-1">No analytics yet</h3>
      <p class="text-sm text-gray-500 max-w-xs mx-auto">Share your videos and analytics will appear here once viewers start watching.</p>
    </div>

    <!-- Content -->
    <template v-else-if="analytics">

      <!-- KPI grid -->
      <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
        <KpiCard
          label="Total views"
          :value="formatNumber(analytics.overview.total_views)"
          :delta="analytics.overview.total_views_delta"
          icon-bg="bg-orange-100" icon-color="text-orange-500" :icon="iconEye"
        />
        <KpiCard
          label="Unique viewers"
          :value="formatNumber(analytics.overview.unique_viewers)"
          :delta="analytics.overview.unique_viewers_delta"
          icon-bg="bg-blue-100" icon-color="text-blue-500" :icon="iconUsers"
        />
        <KpiCard
          label="Watch time"
          :value="formatHours(analytics.overview.watch_time_seconds)"
          :delta="analytics.overview.watch_time_delta"
          icon-bg="bg-green-100" icon-color="text-green-600" :icon="iconClock"
        />
        <KpiCard
          label="Engagement"
          :value="`${analytics.overview.engagement_rate}%`"
          :delta="analytics.overview.engagement_rate_delta"
          delta-unit="pts"
          icon-bg="bg-purple-100" icon-color="text-purple-500" :icon="iconBolt"
        />
        <KpiCard
          label="Reactions"
          :value="formatNumber(analytics.overview.reactions)"
          :delta="analytics.overview.reactions_delta"
          icon-bg="bg-pink-100" icon-color="text-pink-500" :icon="iconHeart"
        />
        <KpiCard
          label="Replies"
          :value="formatNumber(analytics.overview.replies)"
          :delta="analytics.overview.replies_delta"
          icon-bg="bg-amber-100" icon-color="text-amber-500" :icon="iconChat"
        />
      </div>

      <!-- Views over time -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900">Views over time</h2>
            <p class="text-xs text-gray-400 mt-0.5">Solid = this period · Dashed = previous</p>
          </div>
          <div class="flex items-center gap-4 text-xs">
            <span class="flex items-center gap-1.5 text-gray-600"><span class="w-3 h-0.5 bg-orange-500 rounded"></span>This period</span>
            <span class="flex items-center gap-1.5 text-gray-400"><span class="w-3 h-0.5 border-t-2 border-dashed border-gray-300"></span>Previous</span>
          </div>
        </div>

        <svg :viewBox="`0 0 ${chartWidth} ${chartHeight}`" class="w-full" style="height:220px;">
          <defs>
            <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#f97316" stop-opacity="0.2"/>
              <stop offset="100%" stop-color="#f97316" stop-opacity="0"/>
            </linearGradient>
          </defs>
          <g>
            <line v-for="(tick, i) in yTicks" :key="`yg-${i}`"
              :x1="chartPadding.left" :y1="yScale(tick)"
              :x2="chartWidth - chartPadding.right" :y2="yScale(tick)"
              stroke="#f3f4f6" stroke-width="1"/>
          </g>
          <g>
            <text v-for="(tick, i) in yTicks" :key="`yl-${i}`"
              :x="chartPadding.left - 8" :y="yScale(tick) + 4"
              text-anchor="end" fill="#9ca3af" font-size="10">{{ formatNumber(tick) }}</text>
          </g>
          <g>
            <text v-for="(p, i) in xAxisLabels" :key="`xl-${i}`"
              :x="xScale(p.index)" :y="chartHeight - chartPadding.bottom + 14"
              text-anchor="middle" fill="#9ca3af" font-size="10">{{ p.label }}</text>
          </g>

          <!-- Previous period dashed line -->
          <path v-if="previousLinePath" :d="previousLinePath" fill="none"
            stroke="#d1d5db" stroke-width="2" stroke-dasharray="4 4"/>

          <!-- Current period area + line -->
          <path v-if="areaPath" :d="areaPath" fill="url(#areaGradient)"/>
          <path v-if="linePath" :d="linePath" fill="none"
            stroke="#f97316" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>

          <!-- Last point highlight -->
          <circle v-if="lastPoint" :cx="lastPoint.x" :cy="lastPoint.y" r="4"
            fill="#f97316" stroke="white" stroke-width="2"/>
        </svg>
      </div>

      <!-- Retention + Funnel -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 lg:col-span-2">
          <div class="flex items-center justify-between mb-1">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900">Audience retention</h2>
            <span class="text-xs text-gray-400">Avg across videos</span>
          </div>
          <p class="text-xs text-gray-500 mb-4">% of viewers reaching each moment. Spot drop-off points so you can trim or rerecord.</p>

          <svg v-if="retentionPath" viewBox="0 0 700 200" class="w-full" style="height:200px;">
            <defs>
              <linearGradient id="retGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#a855f7" stop-opacity="0.25"/>
                <stop offset="100%" stop-color="#a855f7" stop-opacity="0"/>
              </linearGradient>
            </defs>
            <g stroke="#f3f4f6" stroke-width="1">
              <line x1="40" y1="30"  x2="690" y2="30"/>
              <line x1="40" y1="80"  x2="690" y2="80"/>
              <line x1="40" y1="130" x2="690" y2="130"/>
              <line x1="40" y1="170" x2="690" y2="170"/>
            </g>
            <g fill="#9ca3af" font-size="10" text-anchor="end">
              <text x="34" y="34">100%</text>
              <text x="34" y="84">75%</text>
              <text x="34" y="134">50%</text>
              <text x="34" y="174">25%</text>
            </g>
            <path :d="retentionAreaPath" fill="url(#retGradient)"/>
            <path :d="retentionPath" fill="none" stroke="#a855f7" stroke-width="2.5"/>
            <g v-if="biggestDropX != null">
              <line :x1="biggestDropX" y1="30" :x2="biggestDropX" y2="170"
                stroke="#ef4444" stroke-width="1" stroke-dasharray="3 3" opacity="0.6"/>
              <g :transform="`translate(${Math.min(biggestDropX + 5, 540)}, 30)`">
                <rect width="140" height="22" rx="6" fill="#fef2f2" stroke="#fecaca"/>
                <text x="8" y="15" fill="#b91c1c" font-size="11" font-weight="600">
                  ⚠ {{ analytics.retention.biggest_drop.drop_pct }}% drop at {{ formatTime(analytics.retention.biggest_drop.at_seconds) }}
                </text>
              </g>
            </g>
          </svg>
          <div v-else class="text-sm text-gray-400 italic py-10 text-center">Not enough viewing data yet.</div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 mb-1">Viewer funnel</h2>
          <p class="text-xs text-gray-500 mb-4">Where viewers drop out.</p>

          <div class="space-y-3" v-if="analytics.funnel && analytics.funnel.length">
            <div v-for="(step, i) in analytics.funnel" :key="i">
              <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>{{ step.label }}</span>
                <span class="font-semibold text-gray-700">{{ formatNumber(step.count) }} <span v-if="i > 0" class="text-gray-400 font-normal">· {{ step.percent }}%</span></span>
              </div>
              <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                <div :class="funnelBarClass(i)" :style="{ width: `${Math.max(2, Math.min(step.percent, 100))}%` }"></div>
              </div>
            </div>
          </div>
          <div v-else class="text-sm text-gray-400 italic">No funnel data yet.</div>
        </div>
      </div>

      <!-- Top videos + Audience -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 lg:col-span-2">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 mb-4">Top performing videos</h2>

          <div v-if="!analytics.top_videos || !analytics.top_videos.length" class="text-sm text-gray-400 italic py-6">No videos in this period yet.</div>
          <table v-else class="w-full">
            <thead>
              <tr class="text-[11px] uppercase tracking-wider text-gray-400">
                <th class="text-left pb-2 font-semibold">Video</th>
                <th class="text-right pb-2 font-semibold">Views</th>
                <th class="text-right pb-2 font-semibold">Avg watch</th>
                <th class="text-right pb-2 font-semibold">Engagement</th>
                <th class="text-right pb-2 font-semibold">Replies</th>
              </tr>
            </thead>
            <tbody class="text-sm">
              <tr v-for="(video, idx) in analytics.top_videos" :key="video.id" class="border-t border-gray-100">
                <td class="py-3">
                  <div class="flex items-center gap-3 min-w-0">
                    <span class="text-xs font-semibold text-gray-300 w-4 text-center flex-shrink-0">{{ idx + 1 }}</span>
                    <router-link
                      :to="{ name: 'VideoPlayer', params: { id: video.id } }"
                      class="font-medium text-gray-900 hover:text-orange-600 truncate min-w-0"
                    >{{ video.title }}</router-link>
                  </div>
                </td>
                <td class="text-right font-semibold text-gray-900">{{ formatNumber(video.views_count) }}</td>
                <td class="text-right text-gray-600">{{ formatTime(video.avg_watch_seconds) }}</td>
                <td class="text-right">
                  <span class="inline-flex items-center text-xs font-semibold rounded px-1.5 py-0.5"
                    :class="engagementClass(video.engagement_rate)">
                    {{ video.engagement_rate }}%
                  </span>
                </td>
                <td class="text-right text-gray-700">{{ formatNumber(video.replies) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 mb-4">Where viewers are</h2>
          <div v-if="!analytics.top_countries.length" class="text-sm text-gray-400 italic">No location data yet.</div>
          <div v-else class="space-y-3">
            <div v-for="c in analytics.top_countries" :key="c.country_code">
              <div class="flex justify-between text-sm mb-1">
                <span class="flex items-center gap-2 min-w-0 truncate">{{ countryFlag(c.country_code) }} {{ c.country }}</span>
                <span class="text-gray-500 text-xs flex-shrink-0 ml-2">{{ formatNumber(c.views) }} · {{ c.percentage }}%</span>
              </div>
              <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-orange-500" :style="{ width: `${Math.min(c.percentage, 100)}%` }"></div>
              </div>
            </div>
          </div>

          <hr v-if="analytics.devices.length" class="my-4 border-gray-100"/>
          <h3 v-if="analytics.devices.length" class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-3">Devices</h3>
          <div v-if="analytics.devices.length" class="flex items-end gap-2 h-20">
            <div v-for="d in deviceBars" :key="d.device" class="flex-1 flex flex-col items-center justify-end gap-1">
              <div class="w-full rounded-t" :class="d.color" :style="{ height: `${Math.max(4, d.percentage)}%` }"></div>
              <span class="text-[10px] text-gray-500 capitalize">{{ d.device }}</span>
              <span class="text-[10px] text-gray-400">{{ d.percentage }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent activity + Referrers -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 lg:col-span-2">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900">Recent activity</h2>
            <span class="text-xs text-gray-400">Real-time</span>
          </div>
          <div v-if="!analytics.recent_activity.length" class="text-sm text-gray-400 italic">No activity yet.</div>
          <div v-else class="divide-y divide-gray-100 text-sm">
            <div v-for="(a, i) in analytics.recent_activity" :key="i" class="flex items-center gap-3 py-2.5">
              <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-semibold text-white"
                :class="activityAvatarClass(a)">
                {{ activityAvatarLabel(a) }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-gray-900 truncate">
                  <span class="font-semibold">{{ a.actor_name || 'Anonymous viewer' }}</span>
                  {{ activityVerb(a) }}
                  <span class="font-medium">{{ a.video_title }}</span>
                </div>
                <div class="text-xs text-gray-400 truncate">
                  <span v-if="a.country_code">{{ countryFlag(a.country_code) }} {{ a.country }}</span>
                  <span v-if="a.progress_pct != null"> · finished {{ a.progress_pct }}%</span>
                  <span v-if="a.content"> · "{{ a.content }}"</span>
                </div>
              </div>
              <span class="text-xs text-gray-400 flex-shrink-0">{{ timeAgo(a.at) }}</span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 mb-4">How they got there</h2>
          <div v-if="!analytics.referrers.length" class="text-sm text-gray-400 italic">No referrer data yet.</div>
          <ul v-else class="space-y-3 text-sm">
            <li v-for="r in analytics.referrers" :key="r.source" class="flex items-center justify-between">
              <span class="flex items-center gap-2 capitalize">
                <span class="w-2 h-2 rounded-full" :class="referrerDotClass(r.source)"></span>
                {{ referrerLabel(r.source) }}
              </span>
              <span class="text-gray-500 text-xs">{{ formatNumber(r.views) }} · {{ r.percentage }}%</span>
            </li>
          </ul>
        </div>
      </div>

    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h, defineComponent } from 'vue'

const KpiCard = defineComponent({
  name: 'KpiCard',
  props: {
    label: String,
    value: [String, Number],
    delta: Number,
    deltaUnit: { type: String, default: '%' },
    iconBg: String,
    iconColor: String,
    icon: Function,
  },
  setup(props) {
    return () => h('div', { class: 'bg-white rounded-xl border border-gray-100 shadow-sm p-4' }, [
      h('div', { class: 'flex items-center justify-between mb-2' }, [
        h('span', { class: 'text-[11px] font-semibold uppercase tracking-wider text-gray-500' }, props.label),
        h('div', { class: `w-7 h-7 rounded-lg flex items-center justify-center ${props.iconBg} ${props.iconColor}` }, props.icon ? [props.icon()] : []),
      ]),
      h('div', { class: 'text-2xl font-bold text-gray-900' }, props.value),
      h('div', { class: 'mt-1 flex items-center gap-1.5' }, [
        props.delta != null
          ? h('span', {
              class: [
                'text-[11px] font-semibold rounded px-1.5 py-0.5',
                props.delta > 0 ? 'text-emerald-700 bg-emerald-50' : (props.delta < 0 ? 'text-red-700 bg-red-50' : 'text-gray-500 bg-gray-50'),
              ],
            }, `${props.delta > 0 ? '↑' : (props.delta < 0 ? '↓' : '·')} ${Math.abs(props.delta)}${props.deltaUnit}`)
          : null,
        h('span', { class: 'text-[11px] text-gray-400' }, 'vs previous'),
      ]),
    ])
  },
})

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || ''

const loading = ref(true)
const isEmpty = ref(false)
const analytics = ref(null)
const days = ref(30)

const periodOptions = [
  { label: '7d', value: 7 },
  { label: '30d', value: 30 },
  { label: '90d', value: 90 },
  { label: '12m', value: 365 },
]

// Chart geometry
const chartWidth = 800
const chartHeight = 240
const chartPadding = { top: 16, right: 16, bottom: 28, left: 42 }

const seriesCurrent = computed(() => (analytics.value?.views_over_time || []).map(p => p.views))
const seriesPrevious = computed(() => (analytics.value?.views_over_time || []).map(p => p.previous_views))

const yMax = computed(() => {
  const all = [...seriesCurrent.value, ...seriesPrevious.value, 1]
  return Math.max(...all)
})

const yTicks = computed(() => {
  const max = yMax.value
  const step = Math.max(1, Math.ceil(max / 4))
  return [0, step, step * 2, step * 3, step * 4]
})

const xAxisLabels = computed(() => {
  const points = analytics.value?.views_over_time || []
  if (!points.length) return []
  const stride = Math.max(1, Math.floor(points.length / 6))
  const labels = []
  for (let i = 0; i < points.length; i += stride) {
    const d = new Date(points[i].date)
    labels.push({ index: i, label: `${d.getMonth() + 1}/${d.getDate()}` })
  }
  return labels
})

function xScale(index) {
  const n = Math.max(1, (analytics.value?.views_over_time?.length || 1) - 1)
  const range = chartWidth - chartPadding.left - chartPadding.right
  return chartPadding.left + (index / n) * range
}
function yScale(value) {
  const range = chartHeight - chartPadding.top - chartPadding.bottom
  return chartHeight - chartPadding.bottom - (value / yMax.value) * range
}

function buildSmoothPath(values) {
  if (!values || values.length < 2) return ''
  const pts = values.map((v, i) => ({ x: xScale(i), y: yScale(v) }))
  let d = `M ${pts[0].x} ${pts[0].y}`
  for (let i = 1; i < pts.length; i++) {
    const prev = pts[i - 1]
    const curr = pts[i]
    const cpx = (prev.x + curr.x) / 2
    d += ` C ${cpx} ${prev.y} ${cpx} ${curr.y} ${curr.x} ${curr.y}`
  }
  return d
}

const linePath = computed(() => buildSmoothPath(seriesCurrent.value))
const previousLinePath = computed(() => buildSmoothPath(seriesPrevious.value))
const areaPath = computed(() => {
  const values = seriesCurrent.value
  if (!values || values.length < 2) return ''
  const pts = values.map((v, i) => ({ x: xScale(i), y: yScale(v) }))
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
const lastPoint = computed(() => {
  const values = seriesCurrent.value
  if (!values || !values.length) return null
  const last = values.length - 1
  return { x: xScale(last), y: yScale(values[last]) }
})

// Retention
const retentionBuckets = computed(() => analytics.value?.retention?.buckets || [])
const retentionPath = computed(() => {
  const buckets = retentionBuckets.value
  if (!buckets.length) return ''
  const w = 650, h = 140, ox = 40, oy = 30
  const pts = buckets.map((b, i) => ({
    x: ox + (i / (buckets.length - 1)) * w,
    y: oy + (1 - b.percent_remaining / 100) * h,
  }))
  return pts.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ')
})
const retentionAreaPath = computed(() => {
  const buckets = retentionBuckets.value
  if (!buckets.length) return ''
  const w = 650, h = 140, ox = 40, oy = 30, bottom = oy + h
  const pts = buckets.map((b, i) => ({
    x: ox + (i / (buckets.length - 1)) * w,
    y: oy + (1 - b.percent_remaining / 100) * h,
  }))
  let d = `M ${pts[0].x} ${bottom}`
  pts.forEach(p => { d += ` L ${p.x} ${p.y}` })
  d += ` L ${pts[pts.length - 1].x} ${bottom} Z`
  return d
})
const biggestDropX = computed(() => {
  const drop = analytics.value?.retention?.biggest_drop
  const buckets = retentionBuckets.value
  if (!drop || !buckets.length) return null
  const maxT = buckets[buckets.length - 1].time_seconds || 1
  const w = 650, ox = 40
  return ox + (drop.at_seconds / maxT) * w
})

// Devices — ensure ordered desktop/mobile/tablet
const deviceBars = computed(() => {
  const map = { desktop: 'bg-orange-500', mobile: 'bg-orange-300', tablet: 'bg-orange-200' }
  return (analytics.value?.devices || [])
    .filter(d => map[d.device])
    .map(d => ({ ...d, color: map[d.device] }))
})

// Period change
function setPeriod(value) {
  if (days.value === value) return
  days.value = value
  fetchAnalytics()
}

async function fetchAnalytics() {
  loading.value = true
  isEmpty.value = false
  try {
    const token = localStorage.getItem('auth_token')
    const response = await fetch(`${API_BASE_URL}/api/analytics?days=${days.value}`, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
    })
    if (!response.ok) {
      isEmpty.value = true
      return
    }
    const data = await response.json()
    analytics.value = data
    const ov = data.overview || {}
    const hasData =
      (ov.total_views || 0) > 0 ||
      (data.views_over_time || []).some(p => p.views > 0 || p.previous_views > 0) ||
      (data.top_videos || []).length > 0
    isEmpty.value = !hasData && (ov.total_videos || 0) === 0
  } catch (e) {
    isEmpty.value = true
  } finally {
    loading.value = false
  }
}

// Formatters / helpers
function formatNumber(n) {
  if (n == null) return '0'
  n = Number(n)
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K'
  return String(Math.round(n))
}
function formatHours(seconds) {
  if (!seconds) return '0m'
  const hours = seconds / 3600
  if (hours >= 1) return `${hours.toFixed(hours < 10 ? 1 : 0)}h`
  return `${Math.round(seconds / 60)}m`
}
function formatTime(seconds) {
  if (!seconds) return '0:00'
  const m = Math.floor(seconds / 60)
  const s = Math.round(seconds % 60)
  return `${m}:${String(s).padStart(2, '0')}`
}
function timeAgo(iso) {
  if (!iso) return ''
  const diff = (Date.now() - new Date(iso).getTime()) / 1000
  if (diff < 60) return 'just now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  return `${Math.floor(diff / 86400)}d ago`
}
function countryFlag(code) {
  if (!code || code.length !== 2) return '🌍'
  return String.fromCodePoint(...[...code.toUpperCase()].map(c => 127397 + c.charCodeAt(0)))
}
function engagementClass(pct) {
  if (pct >= 70) return 'text-emerald-700 bg-emerald-50'
  if (pct >= 40) return 'text-amber-700 bg-amber-50'
  return 'text-red-700 bg-red-50'
}
function funnelBarClass(i) {
  return ['h-full bg-gray-900', 'h-full bg-orange-500', 'h-full bg-orange-400', 'h-full bg-orange-300', 'h-full bg-emerald-500'][i] || 'h-full bg-gray-300'
}
function activityVerb(a) {
  if (a.type === 'view') return a.completed ? 'finished' : 'watched'
  if (a.type === 'comment') return 'commented on'
  if (a.type === 'reaction') return `reacted ${a.emoji} to`
  return ''
}
function activityAvatarLabel(a) {
  if (a.type === 'reaction') return a.emoji
  const name = (a.actor_name || '?').trim()
  return name.split(/\s+/).map(p => p[0]).slice(0, 2).join('').toUpperCase() || '?'
}
function activityAvatarClass(a) {
  if (a.type === 'comment') return 'bg-gradient-to-br from-amber-400 to-orange-500'
  if (a.type === 'reaction') return 'bg-gradient-to-br from-pink-400 to-rose-500 text-base'
  return 'bg-gradient-to-br from-blue-400 to-indigo-500'
}
function referrerLabel(source) {
  return ({ direct: 'Direct link', email: 'Email', slack: 'Slack', docs: 'Notion / Docs', chat: 'Chat apps', social: 'Social', embed: 'Embedded', other: 'Other' })[source] || source
}
function referrerDotClass(source) {
  return ({ direct: 'bg-orange-500', email: 'bg-blue-500', slack: 'bg-violet-500', docs: 'bg-emerald-500', chat: 'bg-cyan-500', social: 'bg-pink-500', embed: 'bg-pink-500', other: 'bg-gray-400' })[source] || 'bg-gray-400'
}

// Icons (functional render fns to keep template lean)
const iconEye = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z' }),
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' }),
])
const iconUsers = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' }),
])
const iconClock = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' }),
])
const iconBolt = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 10V3L4 14h7v7l9-11h-7z' }),
])
const iconHeart = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' }),
])
const iconChat = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z' }),
])

onMounted(() => {
  fetchAnalytics()
})
</script>

