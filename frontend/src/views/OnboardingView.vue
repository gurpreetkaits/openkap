<template>
  <div class="min-h-screen bg-[#fafaf9] flex items-center justify-center px-4 py-12">
    <!-- Logo -->
    <div class="w-full max-w-lg">
      <div class="text-center mb-8">
        <img src="/logo.png" alt="OpenKap" class="mx-auto w-12 h-12 rounded-2xl shadow mb-4" />
        <div class="flex items-center justify-center gap-2 mb-1">
          <div
            v-for="i in 3"
            :key="i"
            class="h-1.5 rounded-full transition-all duration-300"
            :class="[
              i <= step ? 'bg-orange-500' : 'bg-stone-200',
              i === step ? 'w-8' : 'w-4'
            ]"
          />
        </div>
        <p class="text-xs text-stone-400 mt-2">Step {{ step }} of 3</p>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-stone-100 p-8">

        <!-- Step 1: How did you hear about us -->
        <Transition name="slide" mode="out-in">
          <div v-if="step === 1" key="step1">
            <h2 class="text-xl font-bold text-stone-900 mb-1">How did you hear about us?</h2>
            <p class="text-sm text-stone-500 mb-6">Helps us understand where to focus</p>

            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="option in heardFromOptions"
                :key="option.value"
                @click="form.heardFrom = option.value"
                class="flex items-center gap-2.5 px-4 py-3 rounded-xl border text-sm font-medium text-left transition-all"
                :class="form.heardFrom === option.value
                  ? 'border-orange-400 bg-orange-50 text-orange-700 shadow-sm'
                  : 'border-stone-200 text-stone-600 hover:border-stone-300 hover:bg-stone-50'"
              >
                <span class="text-lg leading-none">{{ option.emoji }}</span>
                {{ option.label }}
              </button>
            </div>

            <button
              @click="nextStep"
              :disabled="!form.heardFrom"
              class="mt-6 w-full py-3 rounded-xl bg-orange-500 text-white font-semibold text-sm transition-all hover:bg-orange-600 disabled:opacity-40 disabled:cursor-not-allowed"
            >
              Continue
            </button>
          </div>
        </Transition>

        <!-- Step 2: Organization name -->
        <Transition name="slide" mode="out-in">
          <div v-if="step === 2" key="step2">
            <h2 class="text-xl font-bold text-stone-900 mb-1">What's your organization?</h2>
            <p class="text-sm text-stone-500 mb-6">Search or type your company / team name</p>

            <div class="relative" ref="comboboxRef">
              <div
                class="flex items-center gap-2 border rounded-xl px-4 py-3 transition-all"
                :class="comboboxOpen ? 'border-orange-400 ring-2 ring-orange-100' : 'border-stone-200'"
              >
                <svg class="w-4 h-4 text-stone-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
                <input
                  ref="comboboxInput"
                  v-model="orgSearch"
                  @focus="comboboxOpen = true"
                  @input="comboboxOpen = true"
                  @keydown.escape="comboboxOpen = false"
                  @keydown.enter.prevent="selectOrg(filteredOrgs[highlightedIndex] || orgSearch)"
                  @keydown.arrow-down.prevent="highlightedIndex = Math.min(highlightedIndex + 1, filteredOrgs.length - 1)"
                  @keydown.arrow-up.prevent="highlightedIndex = Math.max(highlightedIndex - 1, 0)"
                  placeholder="e.g. Acme Corp, Google, your own name…"
                  class="flex-1 text-sm text-stone-800 outline-none placeholder:text-stone-400 bg-transparent"
                  autocomplete="off"
                />
                <button v-if="orgSearch" @click="orgSearch = ''; form.organizationName = ''" class="text-stone-300 hover:text-stone-500">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>

              <!-- Dropdown -->
              <div
                v-if="comboboxOpen && (filteredOrgs.length || orgSearch.length > 0)"
                class="absolute z-10 mt-1.5 w-full bg-white border border-stone-200 rounded-xl shadow-lg py-1.5 max-h-52 overflow-y-auto"
              >
                <button
                  v-for="(org, i) in filteredOrgs.slice(0, 8)"
                  :key="org"
                  @mousedown.prevent="selectOrg(org)"
                  class="w-full text-left px-4 py-2.5 text-sm transition-colors"
                  :class="i === highlightedIndex ? 'bg-orange-50 text-orange-700' : 'text-stone-700 hover:bg-stone-50'"
                >
                  {{ org }}
                </button>
                <button
                  v-if="orgSearch && !filteredOrgs.includes(orgSearch)"
                  @mousedown.prevent="selectOrg(orgSearch)"
                  class="w-full text-left px-4 py-2.5 text-sm text-stone-500 hover:bg-stone-50 border-t border-stone-100"
                >
                  Use "<strong class="text-stone-700">{{ orgSearch }}</strong>"
                </button>
              </div>
            </div>

            <p v-if="form.organizationName" class="mt-2 text-xs text-stone-400">
              Selected: <span class="font-medium text-stone-600">{{ form.organizationName }}</span>
            </p>

            <div class="flex gap-3 mt-6">
              <button @click="step--" class="px-5 py-3 rounded-xl border border-stone-200 text-stone-600 text-sm font-medium hover:bg-stone-50 transition-colors">
                Back
              </button>
              <button
                @click="nextStep"
                class="flex-1 py-3 rounded-xl bg-orange-500 text-white font-semibold text-sm transition-all hover:bg-orange-600"
              >
                Continue
              </button>
            </div>
          </div>
        </Transition>

        <!-- Step 3: Workspace name -->
        <Transition name="slide" mode="out-in">
          <div v-if="step === 3" key="step3">
            <h2 class="text-xl font-bold text-stone-900 mb-1">Name your workspace</h2>
            <p class="text-sm text-stone-500 mb-6">This is where your recordings will live. You can change it later.</p>

            <div>
              <input
                v-model="form.workspaceName"
                @keydown.enter="submit"
                type="text"
                placeholder="e.g. Acme Engineering, My Team…"
                maxlength="100"
                class="w-full border rounded-xl px-4 py-3 text-sm text-stone-800 placeholder:text-stone-400 outline-none transition-all focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                :class="workspaceError ? 'border-red-300' : 'border-stone-200'"
              />
              <p v-if="workspaceError" class="mt-1.5 text-xs text-red-500">{{ workspaceError }}</p>
            </div>

            <div v-if="error" class="mt-3 p-3 bg-red-50 border border-red-100 rounded-lg text-xs text-red-600">
              {{ error }}
            </div>

            <div class="flex gap-3 mt-6">
              <button @click="step--" class="px-5 py-3 rounded-xl border border-stone-200 text-stone-600 text-sm font-medium hover:bg-stone-50 transition-colors">
                Back
              </button>
              <button
                @click="submit"
                :disabled="loading || !form.workspaceName.trim()"
                class="flex-1 py-3 rounded-xl bg-orange-500 text-white font-semibold text-sm transition-all hover:bg-orange-600 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ loading ? 'Setting up…' : 'Get started' }}
              </button>
            </div>
          </div>
        </Transition>
      </div>

      <p class="text-center text-xs text-stone-400 mt-6">
        You can always update these in Settings
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/stores/auth'

const router = useRouter()
const auth = useAuth()

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || ''

const step = ref(1)
const loading = ref(false)
const error = ref('')
const workspaceError = ref('')

const form = ref({
  heardFrom: '',
  organizationName: '',
  workspaceName: '',
})

// Combobox state
const comboboxOpen = ref(false)
const orgSearch = ref('')
const highlightedIndex = ref(0)
const comboboxRef = ref(null)
const comboboxInput = ref(null)

const heardFromOptions = [
  { value: 'google', label: 'Google Search', emoji: '🔍' },
  { value: 'twitter', label: 'Twitter / X', emoji: '𝕏' },
  { value: 'linkedin', label: 'LinkedIn', emoji: '💼' },
  { value: 'friend', label: 'Friend / Colleague', emoji: '👥' },
  { value: 'reddit', label: 'Reddit', emoji: '🤖' },
  { value: 'producthunt', label: 'Product Hunt', emoji: '🚀' },
  { value: 'youtube', label: 'YouTube', emoji: '▶️' },
  { value: 'other', label: 'Other', emoji: '✨' },
]

const organizations = [
  'Google', 'Apple', 'Microsoft', 'Amazon', 'Meta', 'Netflix', 'Spotify', 'Airbnb',
  'Stripe', 'Shopify', 'Notion', 'Figma', 'Linear', 'Vercel', 'Atlassian', 'Slack',
  'Salesforce', 'HubSpot', 'Intercom', 'Zendesk', 'Twilio', 'Datadog', 'PagerDuty',
  'GitHub', 'GitLab', 'Bitbucket', 'Jira', 'Confluence', 'Asana', 'Trello', 'Monday.com',
  'ClickUp', 'Basecamp', 'Airtable', 'Coda', 'Loom', 'Zoom', 'Webex', 'Miro',
  'Dropbox', 'Box', 'DocuSign', 'Okta', 'Auth0', 'Cloudflare', 'Fastly', 'Nginx',
  'AWS', 'Azure', 'GCP', 'DigitalOcean', 'Heroku', 'Render', 'Fly.io', 'Railway',
  'Y Combinator', 'Sequoia', 'a16z', 'Accel', 'Tiger Global', 'General Catalyst',
  'Stanford University', 'MIT', 'Harvard', 'UC Berkeley', 'Carnegie Mellon',
  'University of Toronto', 'University of Waterloo', 'IIT Bombay', 'IIT Delhi',
  'Accenture', 'Deloitte', 'McKinsey', 'BCG', 'Bain', 'PwC', 'EY', 'KPMG',
  'Tesla', 'SpaceX', 'OpenAI', 'Anthropic', 'Mistral', 'Cohere', 'Hugging Face',
  'Uber', 'Lyft', 'DoorDash', 'Instacart', 'Postmates', 'Grubhub',
  'Twitter', 'Pinterest', 'Snapchat', 'TikTok', 'Reddit', 'Discord', 'Twitch',
  'Adobe', 'Canva', 'InVision', 'Sketch', 'Zeplin', 'Maze', 'UserTesting',
  'Segment', 'Mixpanel', 'Amplitude', 'FullStory', 'Hotjar', 'LogRocket',
  'Sentry', 'Rollbar', 'Bugsnag', 'New Relic', 'Dynatrace', 'Splunk',
  'MongoDB', 'Snowflake', 'Databricks', 'dbt Labs', 'Fivetran', 'Airbyte',
  'Plaid', 'Brex', 'Ramp', 'Rippling', 'Gusto', 'Carta', 'Equity Bee',
  'Freelancer', 'Solo / Indie', 'Side Project', 'Startup (< 10 people)',
]

const filteredOrgs = computed(() => {
  if (!orgSearch.value) return organizations.slice(0, 8)
  const q = orgSearch.value.toLowerCase()
  return organizations.filter(o => o.toLowerCase().includes(q))
})

function selectOrg(org) {
  if (!org) return
  form.value.organizationName = org
  orgSearch.value = org
  comboboxOpen.value = false
  highlightedIndex.value = 0
}

function handleClickOutside(e) {
  if (comboboxRef.value && !comboboxRef.value.contains(e.target)) {
    comboboxOpen.value = false
    // If user typed but didn't select, use what they typed
    if (orgSearch.value && !form.value.organizationName) {
      form.value.organizationName = orgSearch.value
    }
  }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside))

function nextStep() {
  if (step.value === 3) return
  step.value++
  if (step.value === 3 && form.value.organizationName) {
    // Pre-fill workspace name from org if blank
    if (!form.value.workspaceName) {
      form.value.workspaceName = form.value.organizationName
    }
  }
}

async function submit() {
  workspaceError.value = ''
  error.value = ''

  const name = form.value.workspaceName.trim()
  if (name.length < 2) {
    workspaceError.value = 'Workspace name must be at least 2 characters.'
    return
  }

  loading.value = true
  try {
    const token = localStorage.getItem('auth_token')
    const res = await fetch(`${API_BASE_URL}/api/onboarding/complete`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({
        heard_from: form.value.heardFrom,
        organization_name: form.value.organizationName || null,
        workspace_name: name,
      }),
    })

    const data = await res.json()

    if (!res.ok) {
      const msg = data?.message || Object.values(data?.errors || {})?.[0]?.[0] || 'Something went wrong.'
      error.value = msg
      return
    }

    // Update local user to mark as onboarded
    const savedUser = JSON.parse(localStorage.getItem('auth_user') || '{}')
    savedUser.is_new_user = false
    localStorage.setItem('auth_user', JSON.stringify(savedUser))

    router.push(`/workspace/${data.workspace.slug}`)
  } catch (e) {
    error.value = 'Network error. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.slide-enter-from {
  opacity: 0;
  transform: translateX(20px);
}
.slide-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}
</style>
