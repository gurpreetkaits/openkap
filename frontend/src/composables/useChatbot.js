import { ref, computed } from 'vue'
import chatService from '@/services/chatService'

// Module-level singleton state
const isOpen = ref(false)
const conversations = ref([])
const currentConversationId = ref(null)
const messages = ref([])
const isLoading = ref(false)
const isSending = ref(false)

const currentConversation = computed(() => {
  return conversations.value.find(c => c.id === currentConversationId.value) || null
})

function toggle() {
  isOpen.value = !isOpen.value
  if (isOpen.value && conversations.value.length === 0) {
    loadConversations()
  }
}

function open() {
  isOpen.value = true
  if (conversations.value.length === 0) {
    loadConversations()
  }
}

function close() {
  isOpen.value = false
}

async function loadConversations() {
  isLoading.value = true
  try {
    conversations.value = await chatService.getConversations()
  } catch (error) {
    console.error('Failed to load conversations:', error)
  } finally {
    isLoading.value = false
  }
}

async function loadMessages(conversationId) {
  currentConversationId.value = conversationId
  isLoading.value = true
  try {
    const data = await chatService.getMessages(conversationId)
    if (data) {
      messages.value = data.messages || []
    }
  } catch (error) {
    console.error('Failed to load messages:', error)
  } finally {
    isLoading.value = false
  }
}

async function sendMessage(text) {
  if (!text.trim() || isSending.value) return

  // Optimistically add user message
  const userMessage = {
    id: 'temp-' + Date.now(),
    role: 'user',
    content: text,
    created_at: new Date().toISOString()
  }
  messages.value.push(userMessage)

  isSending.value = true
  try {
    const result = await chatService.sendMessage(text, currentConversationId.value)
    if (result) {
      // Update conversation ID if this was a new conversation
      if (!currentConversationId.value) {
        currentConversationId.value = result.conversation_id
        // Reload conversations list to include the new one
        loadConversations()
      }

      // Add assistant message
      messages.value.push(result.message)
    }
  } catch (error) {
    console.error('Failed to send message:', error)
    // Add error message
    messages.value.push({
      id: 'error-' + Date.now(),
      role: 'assistant',
      content: 'Sorry, I encountered an error. Please try again.',
      created_at: new Date().toISOString()
    })
  } finally {
    isSending.value = false
  }
}

async function startNewConversation() {
  currentConversationId.value = null
  messages.value = []
}

async function deleteConversation(conversationId) {
  try {
    await chatService.deleteConversation(conversationId)
    conversations.value = conversations.value.filter(c => c.id !== conversationId)

    if (currentConversationId.value === conversationId) {
      currentConversationId.value = null
      messages.value = []
    }
    return true
  } catch (error) {
    console.error('Failed to delete conversation:', error)
    return false
  }
}

export function useChatbot() {
  return {
    isOpen,
    conversations,
    currentConversationId,
    currentConversation,
    messages,
    isLoading,
    isSending,
    toggle,
    open,
    close,
    loadConversations,
    loadMessages,
    sendMessage,
    startNewConversation,
    deleteConversation
  }
}
