<template>
  <div>
    <!-- Floating launcher -->
    <button
      v-show="!isOpen"
      @click="openWidget"
      class="fixed bottom-6 right-6 z-40 h-14 w-14 rounded-full bg-orange-600 text-white shadow-lg hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition flex items-center justify-center"
      aria-label="Open support chat"
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
      </svg>
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1 rounded-full bg-red-500 text-white text-[11px] font-semibold flex items-center justify-center"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Chat panel -->
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-4"
    >
      <div
        v-if="isOpen"
        class="fixed bottom-6 right-6 z-40 w-[360px] max-w-[calc(100vw-2rem)] h-[540px] max-h-[calc(100vh-3rem)] bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col overflow-hidden"
      >
        <!-- Header -->
        <div class="flex items-center px-4 py-3 bg-orange-600 text-white">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-2 12H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2z" />
          </svg>
          <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold leading-tight">Help & Support</div>
            <div class="text-[11px] text-orange-100">We usually reply within a few hours</div>
          </div>
          <button
            @click="closeWidget"
            class="ml-2 p-1 rounded hover:bg-orange-700 focus:outline-none"
            aria-label="Close"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Messages -->
        <div ref="messagesEl" class="flex-1 overflow-y-auto bg-gray-50 px-3 py-3 space-y-2">
          <div v-if="loading" class="flex items-center justify-center py-6">
            <div class="animate-spin rounded-full h-6 w-6 border-2 border-orange-500 border-t-transparent"></div>
          </div>

          <template v-else>
            <!-- Welcome + FAQ when empty -->
            <div v-if="messages.length === 0" class="space-y-3">
              <div class="text-sm text-gray-700">
                Hi {{ firstName }} 👋 — how can we help? Pick a topic or just type your message.
              </div>
              <div class="space-y-2">
                <button
                  v-for="item in faqSuggestions"
                  :key="item.id"
                  @click="sendFaqMessage(item)"
                  class="w-full text-left px-3 py-2 rounded-lg bg-white border border-gray-200 hover:border-orange-400 hover:bg-orange-50 text-sm text-gray-700 flex items-center"
                >
                  <svg class="w-4 h-4 mr-2 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  {{ item.question }}
                </button>
              </div>
            </div>

            <!-- Message bubbles -->
            <div
              v-for="message in messages"
              :key="message.id"
              class="flex"
              :class="message.sender_type === 'user' ? 'justify-end' : 'justify-start'"
            >
              <div
                class="max-w-[80%] rounded-2xl px-3 py-2 text-sm"
                :class="
                  message.sender_type === 'user'
                    ? 'bg-orange-600 text-white rounded-br-sm'
                    : 'bg-white text-gray-800 border border-gray-200 rounded-bl-sm'
                "
              >
                <div
                  v-if="message.sender_type === 'admin'"
                  class="text-[11px] font-medium text-gray-500 mb-0.5"
                >
                  {{ message.sender_name || 'Support' }}
                </div>
                <div class="whitespace-pre-wrap break-words">{{ message.body }}</div>
                <div
                  class="text-[10px] mt-1"
                  :class="message.sender_type === 'user' ? 'text-orange-100' : 'text-gray-400'"
                >
                  {{ formatTime(message.created_at) }}
                </div>
              </div>
            </div>

            <div v-if="sending" class="flex justify-end">
              <div class="bg-orange-100 text-orange-700 rounded-2xl px-3 py-2 text-xs">Sending…</div>
            </div>
          </template>
        </div>

        <!-- Input -->
        <div class="border-t border-gray-200 p-2 flex items-end gap-2 bg-white">
          <textarea
            v-model="draft"
            placeholder="Type a message…"
            rows="1"
            :disabled="sending"
            @keydown.enter.exact.prevent="handleSend"
            class="flex-1 resize-none rounded-lg border border-gray-300 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 px-3 py-2 text-sm outline-none max-h-32"
          />
          <button
            @click="handleSend"
            :disabled="!draft.trim() || sending"
            class="h-9 w-9 flex items-center justify-center rounded-lg bg-orange-600 text-white disabled:opacity-50 disabled:cursor-not-allowed hover:bg-orange-700 transition"
            aria-label="Send message"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useAuth } from '@/stores/auth'
import supportService from '@/services/supportService'

const FAQ_SUGGESTIONS = [
  {
    id: 'getting-started',
    question: 'How do I get started?',
    message: "Hi! I'd like help getting started with OpenKap.",
  },
  {
    id: 'recording-issue',
    question: 'I have a recording issue',
    message: "I'm having trouble with recording. Can you help?",
  },
  {
    id: 'billing',
    question: 'I have a billing question',
    message: 'I have a question about my subscription or billing.',
  },
  {
    id: 'feature-request',
    question: 'Suggest a feature',
    message: "I'd like to suggest a feature or improvement.",
  },
]

const POLL_INTERVAL_MS = 7000

const { user } = useAuth()

const isOpen = ref(false)
const loading = ref(false)
const sending = ref(false)
const conversation = ref(null)
const messages = ref([])
const draft = ref('')
const messagesEl = ref(null)
const unreadCount = ref(0)
let pollTimer = null

const firstName = computed(() => user.value?.name?.split(' ')[0] || 'there')
const faqSuggestions = computed(() => FAQ_SUGGESTIONS)

function formatTime(iso) {
  if (!iso) return ''
  const date = new Date(iso)
  const diffMs = Date.now() - date.getTime()
  const diffMin = Math.floor(diffMs / 60000)
  if (diffMin < 1) return 'just now'
  if (diffMin < 60) return `${diffMin}m ago`
  const diffH = Math.floor(diffMin / 60)
  if (diffH < 24) return `${diffH}h ago`
  const diffD = Math.floor(diffH / 24)
  if (diffD < 7) return `${diffD}d ago`
  return date.toLocaleDateString()
}

async function scrollToBottom() {
  await nextTick()
  if (messagesEl.value) {
    messagesEl.value.scrollTop = messagesEl.value.scrollHeight
  }
}

async function loadConversation() {
  try {
    const data = await supportService.getMyConversation()
    if (!data) return
    conversation.value = data.conversation
    messages.value = data.messages
    unreadCount.value = data.conversation.unread_count_user
  } catch (e) {
    console.error('Failed to load support conversation:', e)
  }
}

async function refreshConversation() {
  try {
    const data = await supportService.getMyConversation()
    if (!data) return
    const previousLast = messages.value.length ? messages.value[messages.value.length - 1].id : 0
    conversation.value = data.conversation
    messages.value = data.messages
    const newLast = messages.value.length ? messages.value[messages.value.length - 1].id : 0
    if (isOpen.value && newLast !== previousLast) {
      await supportService.markMyConversationRead()
      unreadCount.value = 0
      await scrollToBottom()
    } else {
      unreadCount.value = data.conversation.unread_count_user
    }
  } catch (e) {
    // Silent — next poll will retry
  }
}

function startPolling() {
  stopPolling()
  pollTimer = setInterval(refreshConversation, POLL_INTERVAL_MS)
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

async function openWidget() {
  isOpen.value = true
  if (!conversation.value) {
    loading.value = true
    await loadConversation()
    loading.value = false
  }
  await scrollToBottom()
  if (conversation.value && unreadCount.value > 0) {
    await supportService.markMyConversationRead()
    unreadCount.value = 0
  }
}

function closeWidget() {
  isOpen.value = false
}

async function handleSend() {
  const text = draft.value.trim()
  if (!text || sending.value) return
  sending.value = true
  try {
    const message = await supportService.sendMessage(text)
    if (message) {
      messages.value.push(message)
      draft.value = ''
      await scrollToBottom()
    }
  } catch (e) {
    console.error('Failed to send message:', e)
  } finally {
    sending.value = false
  }
}

async function sendFaqMessage(item) {
  draft.value = item.message
  await handleSend()
}

onMounted(async () => {
  await loadConversation()
  startPolling()
})

onUnmounted(() => {
  stopPolling()
})
</script>
