<template>
  <Teleport to="body">
    <!-- Floating Chat Button -->
    <button
      v-if="!isOpen"
      @click="toggle"
      class="fixed bottom-6 right-6 w-14 h-14 bg-orange-500 hover:bg-orange-600 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center z-50 group"
    >
      <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
      </svg>
    </button>

    <!-- Chat Panel -->
    <Transition name="chat-panel">
      <div
        v-if="isOpen"
        class="fixed bottom-6 right-6 w-[400px] h-[600px] bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col z-50 overflow-hidden"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-orange-500 text-white flex-shrink-0">
          <div class="flex items-center gap-2">
            <button
              v-if="currentConversationId || messages.length > 0"
              @click="showConversationList"
              class="p-1 hover:bg-orange-600 rounded-lg transition-colors"
              title="Back to conversations"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
            </button>
            <h3 class="text-sm font-semibold truncate">
              {{ currentConversation ? currentConversation.title : 'ScreenSense Assistant' }}
            </h3>
          </div>
          <div class="flex items-center gap-1">
            <button
              @click="startNewConversation"
              class="p-1.5 hover:bg-orange-600 rounded-lg transition-colors"
              title="New chat"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
            </button>
            <button
              @click="close"
              class="p-1.5 hover:bg-orange-600 rounded-lg transition-colors"
              title="Close"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Conversation List View -->
        <div
          v-if="showingList"
          class="flex-1 overflow-y-auto"
        >
          <!-- Loading -->
          <div v-if="isLoading" class="flex items-center justify-center h-full">
            <div class="animate-spin rounded-full h-6 w-6 border-2 border-orange-500 border-t-transparent"></div>
          </div>

          <!-- Empty State -->
          <div v-else-if="conversations.length === 0" class="flex flex-col items-center justify-center h-full px-6 text-center">
            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <p class="text-sm text-gray-500 mb-1">No conversations yet</p>
            <p class="text-xs text-gray-400">Start a new chat to get help</p>
          </div>

          <!-- Conversation Items -->
          <div v-else class="divide-y divide-gray-100">
            <div
              v-for="conv in conversations"
              :key="conv.id"
              @click="loadMessages(conv.id)"
              class="px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors group"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 truncate">{{ conv.title }}</p>
                  <p class="text-xs text-gray-400 mt-0.5">{{ formatTime(conv.created_at) }}</p>
                </div>
                <button
                  @click.stop="handleDeleteConversation(conv.id)"
                  class="p-1 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all"
                  title="Delete conversation"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Messages View -->
        <div
          v-else
          class="flex-1 overflow-y-auto px-4 py-3 space-y-3"
          ref="messagesContainer"
        >
          <!-- Welcome message when empty -->
          <div v-if="messages.length === 0 && !isLoading" class="flex flex-col items-center justify-center h-full text-center px-4">
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-3">
              <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-900 mb-1">How can I help?</p>
            <p class="text-xs text-gray-400">Ask me anything about ScreenSense</p>
          </div>

          <!-- Loading messages -->
          <div v-if="isLoading" class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-6 w-6 border-2 border-orange-500 border-t-transparent"></div>
          </div>

          <!-- Message bubbles -->
          <div
            v-for="msg in messages"
            :key="msg.id"
            class="flex"
            :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
          >
            <div
              class="max-w-[85%] px-3 py-2 rounded-2xl text-sm leading-relaxed"
              :class="msg.role === 'user'
                ? 'bg-orange-500 text-white rounded-br-md'
                : 'bg-gray-100 text-gray-800 rounded-bl-md'"
            >
              <div v-if="msg.role === 'assistant'" class="chat-markdown" v-html="renderMarkdown(msg.content)"></div>
              <span v-else>{{ msg.content }}</span>
            </div>
          </div>

          <!-- Typing indicator -->
          <div v-if="isSending" class="flex justify-start">
            <div class="bg-gray-100 rounded-2xl rounded-bl-md px-4 py-3">
              <div class="flex gap-1">
                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Input Area -->
        <div v-if="!showingList" class="px-4 py-3 border-t border-gray-100 flex-shrink-0">
          <div class="flex items-end gap-2">
            <textarea
              ref="inputRef"
              v-model="inputText"
              @keydown.enter.exact.prevent="handleSend"
              placeholder="Type a message..."
              rows="1"
              class="flex-1 resize-none border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all max-h-24 overflow-y-auto"
              :disabled="isSending"
            ></textarea>
            <button
              @click="handleSend"
              :disabled="!inputText.trim() || isSending"
              class="p-2 bg-orange-500 text-white rounded-xl hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex-shrink-0"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script>
import { ref, computed, watch, nextTick } from 'vue'
import { useChatbot } from '@/composables/useChatbot'
import { sanitizeHtml } from '@/utils/sanitize'

export default {
  name: 'ChatbotWidget',
  setup() {
    const chatbot = useChatbot()
    const inputText = ref('')
    const inputRef = ref(null)
    const messagesContainer = ref(null)
    const showingList = ref(true)

    const showConversationList = () => {
      showingList.value = true
      chatbot.loadConversations()
    }

    const handleSend = () => {
      if (!inputText.value.trim() || chatbot.isSending.value) return
      chatbot.sendMessage(inputText.value)
      inputText.value = ''
      showingList.value = false
    }

    const handleDeleteConversation = async (id) => {
      await chatbot.deleteConversation(id)
    }

    const loadMessages = (conversationId) => {
      showingList.value = false
      chatbot.loadMessages(conversationId)
    }

    const startNewConversation = () => {
      chatbot.startNewConversation()
      showingList.value = false
      nextTick(() => inputRef.value?.focus())
    }

    const renderMarkdown = (text) => {
      if (!text) return ''
      // Simple markdown rendering
      let html = text
        // Code blocks
        .replace(/```(\w*)\n([\s\S]*?)```/g, '<pre class="bg-gray-800 text-gray-100 rounded-lg p-2 my-1 text-xs overflow-x-auto"><code>$2</code></pre>')
        // Inline code
        .replace(/`([^`]+)`/g, '<code class="bg-gray-200 text-orange-700 px-1 py-0.5 rounded text-xs">$1</code>')
        // Bold
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        // Italic
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        // Bullet points
        .replace(/^- (.+)$/gm, '<li class="ml-4 list-disc">$1</li>')
        // Numbered lists
        .replace(/^\d+\. (.+)$/gm, '<li class="ml-4 list-decimal">$1</li>')
        // Line breaks
        .replace(/\n/g, '<br>')
      return sanitizeHtml(html)
    }

    const formatTime = (dateString) => {
      const date = new Date(dateString)
      const now = new Date()
      const diffMs = now - date
      const diffMins = Math.floor(diffMs / 60000)
      const diffHours = Math.floor(diffMs / 3600000)
      const diffDays = Math.floor(diffMs / 86400000)

      if (diffMins < 1) return 'Just now'
      if (diffMins < 60) return `${diffMins}m ago`
      if (diffHours < 24) return `${diffHours}h ago`
      if (diffDays < 7) return `${diffDays}d ago`
      return date.toLocaleDateString()
    }

    // Auto-scroll to bottom when new messages arrive
    watch(
      () => chatbot.messages.value.length,
      () => {
        nextTick(() => {
          if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
          }
        })
      }
    )

    // When chat opens, focus input
    watch(
      () => chatbot.isOpen.value,
      (open) => {
        if (open) {
          // If there are conversations, show the list; otherwise show empty chat
          if (chatbot.conversations.value.length > 0 && !chatbot.currentConversationId.value) {
            showingList.value = true
          } else if (chatbot.currentConversationId.value) {
            showingList.value = false
          } else {
            showingList.value = false
          }
          nextTick(() => inputRef.value?.focus())
        }
      }
    )

    return {
      ...chatbot,
      inputText,
      inputRef,
      messagesContainer,
      showingList,
      showConversationList,
      handleSend,
      handleDeleteConversation,
      loadMessages,
      startNewConversation,
      renderMarkdown,
      formatTime
    }
  }
}
</script>

<style scoped>
.chat-panel-enter-active,
.chat-panel-leave-active {
  transition: all 0.2s ease;
}

.chat-panel-enter-from,
.chat-panel-leave-to {
  opacity: 0;
  transform: translateY(16px) scale(0.95);
}

.chat-markdown :deep(pre) {
  margin: 4px 0;
}

.chat-markdown :deep(li) {
  margin: 2px 0;
}

textarea {
  field-sizing: content;
}
</style>
