<template>
  <div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6 flex items-start justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Support Inbox</h1>
        <p class="text-sm text-gray-500 mt-1">
          Conversations from users. All admins see this shared inbox.
        </p>
      </div>
      <button
        @click="loadConversations"
        :disabled="loadingList"
        class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-50 flex items-center gap-2"
      >
        <svg class="w-4 h-4" :class="{ 'animate-spin': loadingList }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Refresh
      </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
      <!-- Conversation list -->
      <div class="lg:col-span-4 bg-white border border-gray-200 rounded-xl overflow-hidden flex flex-col" style="height: calc(100vh - 180px); min-height: 500px">
        <div class="p-3 border-b border-gray-200">
          <div class="relative">
            <svg class="w-4 h-4 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="search"
              @input="onSearchInput"
              type="text"
              placeholder="Search by name or email"
              class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 outline-none"
            />
          </div>
        </div>

        <div class="flex-1 overflow-y-auto">
          <div v-if="loadingList && conversations.length === 0" class="flex items-center justify-center py-10">
            <div class="animate-spin rounded-full h-6 w-6 border-2 border-orange-500 border-t-transparent"></div>
          </div>

          <div v-else-if="conversations.length === 0" class="text-center text-gray-400 text-sm py-10">
            No conversations yet
          </div>

          <button
            v-for="conv in conversations"
            :key="conv.id"
            @click="selectConversation(conv)"
            class="w-full text-left px-3 py-3 border-b border-gray-100 hover:bg-gray-50 flex items-start gap-3"
            :class="{ 'bg-orange-50': selectedConversationId === conv.id }"
          >
            <div class="flex-shrink-0">
              <img
                v-if="conv.user?.avatar_url"
                :src="conv.user.avatar_url"
                :alt="conv.user.name"
                class="w-9 h-9 rounded-full object-cover"
              />
              <div
                v-else
                class="w-9 h-9 rounded-full bg-orange-500 text-white text-xs font-medium flex items-center justify-center"
              >
                {{ getInitials(conv.user?.name) }}
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <div class="font-medium text-sm text-gray-900 truncate">
                  {{ conv.user?.name || 'Unknown user' }}
                </div>
                <div v-if="conv.last_message_at" class="text-[11px] text-gray-400 flex-shrink-0">
                  {{ formatTime(conv.last_message_at) }}
                </div>
              </div>
              <div class="flex items-center justify-between gap-2 mt-0.5">
                <div class="text-xs text-gray-500 truncate">
                  <span v-if="conv.latest_message">
                    <span v-if="conv.latest_message.sender_type === 'admin'" class="text-gray-400">You: </span>
                    {{ truncate(conv.latest_message.body, 50) }}
                  </span>
                  <span v-else>No messages yet</span>
                </div>
                <span
                  v-if="conv.unread_count_admin > 0"
                  class="flex-shrink-0 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold flex items-center justify-center"
                >
                  {{ conv.unread_count_admin > 9 ? '9+' : conv.unread_count_admin }}
                </span>
              </div>
            </div>
          </button>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.lastPage > 1" class="border-t border-gray-200 p-2 flex items-center justify-center gap-2 text-xs">
          <button
            @click="changePage(pagination.page - 1)"
            :disabled="pagination.page <= 1"
            class="px-2 py-1 rounded border border-gray-300 disabled:opacity-40"
          >
            Prev
          </button>
          <span class="text-gray-500">Page {{ pagination.page }} / {{ pagination.lastPage }}</span>
          <button
            @click="changePage(pagination.page + 1)"
            :disabled="pagination.page >= pagination.lastPage"
            class="px-2 py-1 rounded border border-gray-300 disabled:opacity-40"
          >
            Next
          </button>
        </div>
      </div>

      <!-- Thread view -->
      <div class="lg:col-span-8 bg-white border border-gray-200 rounded-xl flex flex-col" style="height: calc(100vh - 180px); min-height: 500px">
        <div v-if="!selectedConversationId" class="flex-1 flex items-center justify-center text-center text-gray-400 px-6">
          <div>
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <div class="text-sm">Select a conversation to start replying</div>
          </div>
        </div>

        <template v-else>
          <!-- Thread header -->
          <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-3">
            <img
              v-if="selectedConversation?.user?.avatar_url"
              :src="selectedConversation.user.avatar_url"
              :alt="selectedConversation.user.name"
              class="w-10 h-10 rounded-full object-cover"
            />
            <div
              v-else
              class="w-10 h-10 rounded-full bg-orange-500 text-white text-sm font-medium flex items-center justify-center"
            >
              {{ getInitials(selectedConversation?.user?.name) }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-sm text-gray-900 truncate">
                {{ selectedConversation?.user?.name }}
              </div>
              <div class="text-xs text-gray-500 truncate">
                {{ selectedConversation?.user?.email }}
              </div>
            </div>
          </div>

          <!-- Messages -->
          <div ref="threadEl" class="flex-1 overflow-y-auto bg-gray-50 p-4 space-y-2">
            <div v-if="loadingThread" class="flex items-center justify-center py-6">
              <div class="animate-spin rounded-full h-6 w-6 border-2 border-orange-500 border-t-transparent"></div>
            </div>

            <template v-else>
              <div
                v-for="message in messages"
                :key="message.id"
                class="flex"
                :class="message.sender_type === 'admin' ? 'justify-end' : 'justify-start'"
              >
                <div
                  class="max-w-[70%] rounded-2xl px-3 py-2 text-sm"
                  :class="
                    message.sender_type === 'admin'
                      ? 'bg-orange-600 text-white rounded-br-sm'
                      : 'bg-white text-gray-800 border border-gray-200 rounded-bl-sm'
                  "
                >
                  <div
                    v-if="message.sender_type === 'admin'"
                    class="text-[11px] text-orange-100 mb-0.5"
                  >
                    {{ message.sender_name }}
                  </div>
                  <div class="whitespace-pre-wrap break-words">{{ message.body }}</div>
                  <div
                    class="text-[10px] mt-1"
                    :class="message.sender_type === 'admin' ? 'text-orange-100' : 'text-gray-400'"
                  >
                    {{ formatTime(message.created_at) }}
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Reply input -->
          <div class="border-t border-gray-200 p-3 flex items-end gap-2 bg-white">
            <textarea
              v-model="draft"
              placeholder="Type your reply…"
              rows="1"
              :disabled="sending"
              @keydown.enter.exact.prevent="sendReply"
              class="flex-1 resize-none rounded-lg border border-gray-300 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 px-3 py-2 text-sm outline-none max-h-32"
            />
            <button
              @click="sendReply"
              :disabled="!draft.trim() || sending"
              class="h-9 px-4 rounded-lg bg-orange-600 text-white disabled:opacity-50 disabled:cursor-not-allowed hover:bg-orange-700 transition text-sm font-medium"
            >
              {{ sending ? 'Sending…' : 'Send' }}
            </button>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import supportService from '@/services/supportService'

const POLL_INTERVAL_MS = 7000

const conversations = ref([])
const messages = ref([])
const selectedConversationId = ref(null)
const loadingList = ref(false)
const loadingThread = ref(false)
const sending = ref(false)
const draft = ref('')
const search = ref('')
const threadEl = ref(null)
const pagination = ref({ page: 1, lastPage: 1, total: 0, perPage: 25 })
let searchTimer = null
let pollTimer = null

const selectedConversation = computed(() =>
  conversations.value.find((c) => c.id === selectedConversationId.value),
)

function getInitials(name) {
  if (!name) return '?'
  return name
    .split(' ')
    .map((p) => p[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

function truncate(text, max) {
  return text.length > max ? text.slice(0, max) + '…' : text
}

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

async function scrollThreadToBottom() {
  await nextTick()
  if (threadEl.value) threadEl.value.scrollTop = threadEl.value.scrollHeight
}

async function loadConversations() {
  loadingList.value = true
  try {
    const data = await supportService.listAdminConversations({
      search: search.value || '',
      page: pagination.value.page,
      perPage: pagination.value.perPage,
    })
    if (!data) return
    conversations.value = data.data
    pagination.value.total = data.meta?.total ?? data.total ?? 0
    pagination.value.lastPage = data.meta?.last_page ?? data.last_page ?? 1
  } catch (e) {
    console.error('Failed to load conversations:', e)
  } finally {
    loadingList.value = false
  }
}

async function selectConversation(conv) {
  selectedConversationId.value = conv.id
  loadingThread.value = true
  try {
    const data = await supportService.getAdminConversation(conv.id)
    if (data) {
      messages.value = data.messages
      await supportService.markAdminConversationRead(conv.id)
      conv.unread_count_admin = 0
    }
  } catch (e) {
    console.error('Failed to open conversation:', e)
  } finally {
    loadingThread.value = false
    await scrollThreadToBottom()
  }
}

async function sendReply() {
  const body = draft.value.trim()
  if (!body || !selectedConversationId.value || sending.value) return
  sending.value = true
  try {
    const message = await supportService.replyAsAdmin(selectedConversationId.value, body)
    if (message) {
      messages.value.push(message)
      draft.value = ''
      await scrollThreadToBottom()
      const conv = conversations.value.find((c) => c.id === selectedConversationId.value)
      if (conv) {
        conv.last_message_at = message.created_at
        conv.latest_message = {
          id: message.id,
          body: message.body,
          sender_type: 'admin',
          created_at: message.created_at,
        }
      }
    }
  } catch (e) {
    console.error('Failed to send reply:', e)
  } finally {
    sending.value = false
  }
}

function onSearchInput() {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    pagination.value.page = 1
    loadConversations()
  }, 400)
}

function changePage(newPage) {
  if (newPage < 1 || newPage > pagination.value.lastPage) return
  pagination.value.page = newPage
  loadConversations()
}

async function refreshOpenThread() {
  // Light refresh: re-fetch the open thread + list so unread counts stay live.
  await loadConversations()
  if (selectedConversationId.value) {
    try {
      const data = await supportService.getAdminConversation(selectedConversationId.value)
      if (data) {
        const previousLast = messages.value.length ? messages.value[messages.value.length - 1].id : 0
        messages.value = data.messages
        const newLast = messages.value.length ? messages.value[messages.value.length - 1].id : 0
        if (newLast !== previousLast) {
          await supportService.markAdminConversationRead(selectedConversationId.value)
          await scrollThreadToBottom()
        }
      }
    } catch (e) {
      // Silent
    }
  }
}

onMounted(async () => {
  await loadConversations()
  pollTimer = setInterval(refreshOpenThread, POLL_INTERVAL_MS)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
  if (searchTimer) clearTimeout(searchTimer)
})
</script>
