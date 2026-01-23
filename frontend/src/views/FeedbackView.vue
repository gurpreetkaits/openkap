<template>
  <div class="animate-fade-in py-4 px-4 lg:px-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div>
        <h1 class="text-lg font-semibold text-gray-900">Feedback</h1>
        <p class="text-xs text-gray-500">Help us improve ScreenSense</p>
      </div>
      <button
        @click="showModal = true"
        class="px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-1.5"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="bg-white rounded-lg border border-gray-200 p-8 text-center">
      <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-orange-500 border-t-transparent"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="feedbackList.length === 0" class="bg-white rounded-lg border border-gray-200 p-8 text-center">
      <svg class="w-10 h-10 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
      </svg>
      <p class="mt-2 text-sm text-gray-500">No feedback yet</p>
      <button @click="showModal = true" class="mt-3 px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium rounded-lg">
        Send Feedback
      </button>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell w-24">Date</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-20">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr
            v-for="item in feedbackList"
            :key="item.id"
            @click="viewFeedback(item)"
            class="hover:bg-gray-50 cursor-pointer"
          >
            <td class="px-3 py-2">
              <div class="flex items-center gap-1.5">
                <span class="text-sm text-gray-900 truncate">{{ item.title }}</span>
                <svg v-if="hasImages(item.description)" class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span v-if="item.reply" class="w-1.5 h-1.5 bg-green-500 rounded-full flex-shrink-0"></span>
              </div>
            </td>
            <td class="px-3 py-2 text-xs text-gray-500 hidden sm:table-cell">{{ formatDate(item.created_at) }}</td>
            <td class="px-3 py-2">
              <span :class="statusClass(item.status)" class="px-1.5 py-0.5 text-xs font-medium rounded capitalize">
                {{ item.status }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.lastPage > 1" class="px-3 py-2 border-t border-gray-100 flex items-center justify-between text-xs">
        <span class="text-gray-500">{{ pagination.from }}-{{ pagination.to }} of {{ pagination.total }}</span>
        <div class="flex gap-1">
          <button
            @click="goToPage(pagination.currentPage - 1)"
            :disabled="pagination.currentPage === 1"
            class="px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Prev
          </button>
          <button
            @click="goToPage(pagination.currentPage + 1)"
            :disabled="pagination.currentPage === pagination.lastPage"
            class="px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Rate limit note -->
    <p class="text-xs text-gray-400 mt-3 text-center">Limited to 3 submissions per hour</p>

    <!-- New Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="closeModal"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-xl p-5 z-10">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-base font-semibold text-gray-900">New Feedback</h2>
          <button @click="closeModal" class="p-1.5 hover:bg-gray-100 rounded-lg">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <form @submit.prevent="submitFeedback" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
            <input
              v-model="form.title"
              type="text"
              placeholder="Brief summary of your feedback"
              maxlength="255"
              required
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none"
              :disabled="submitting"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Description
              <span class="text-xs text-gray-400 font-normal ml-1">You can paste images</span>
            </label>
            <div
              ref="descriptionEditor"
              contenteditable="true"
              @input="onDescriptionInput"
              @paste="onPaste"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none min-h-[200px] max-h-[400px] overflow-y-auto prose prose-sm"
              :class="{ 'opacity-50 pointer-events-none': submitting }"
              data-placeholder="Describe your feedback, bug report, or feature request. You can paste screenshots directly..."
            ></div>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg" :disabled="submitting">
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submitting || !isFormValid"
              class="px-4 py-2 bg-orange-500 hover:bg-orange-600 disabled:bg-gray-300 text-white text-sm font-medium rounded-lg flex items-center gap-1.5"
            >
              <svg v-if="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ submitting ? 'Sending...' : 'Send Feedback' }}
            </button>
          </div>
        </form>
        </div>
      </div>
    </Teleport>

    <!-- View Modal -->
    <Teleport to="body">
      <div v-if="selectedFeedback" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="selectedFeedback = null"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-xl max-h-[90vh] flex flex-col z-10">
        <div class="flex items-start justify-between p-4 border-b border-gray-100">
          <div class="flex-1 min-w-0 pr-2">
            <h2 class="text-base font-semibold text-gray-900">{{ selectedFeedback.title }}</h2>
            <p class="text-xs text-gray-500 mt-0.5">{{ formatDate(selectedFeedback.created_at) }}</p>
          </div>
          <div class="flex items-center gap-2">
            <span :class="statusClass(selectedFeedback.status)" class="px-2 py-0.5 text-xs font-medium rounded-full capitalize">
              {{ selectedFeedback.status }}
            </span>
            <button @click="selectedFeedback = null" class="p-1.5 hover:bg-gray-100 rounded-lg">
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-3">
          <!-- User's feedback -->
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs font-medium text-gray-500 mb-2">Your feedback</p>
            <div class="text-sm text-gray-700 prose prose-sm max-w-none" v-html="selectedFeedback.description"></div>
          </div>

          <!-- Admin Reply -->
          <div v-if="selectedFeedback.reply" class="bg-green-50 border border-green-100 rounded-lg p-3">
            <div class="flex items-center gap-1.5 mb-2">
              <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
              </svg>
              <span class="text-xs font-medium text-green-700">Reply from ScreenSense</span>
              <span v-if="selectedFeedback.replied_at" class="text-xs text-green-600">{{ formatDate(selectedFeedback.replied_at) }}</span>
            </div>
            <p class="text-sm text-green-800 whitespace-pre-wrap">{{ selectedFeedback.reply }}</p>
          </div>
          <div v-else class="text-center py-3 text-xs text-gray-400">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Awaiting response
          </div>
        </div>

        <div class="p-4 border-t border-gray-100">
          <button @click="selectedFeedback = null" class="w-full px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg">
            Close
          </button>
        </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useAuth } from '@/stores/auth'
import toast from '@/services/toastService'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || ''
const auth = useAuth()

const form = ref({ title: '', description: '' })
const loading = ref(true)
const submitting = ref(false)
const showModal = ref(false)
const selectedFeedback = ref(null)
const feedbackList = ref([])
const descriptionEditor = ref(null)
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  from: 0,
  to: 0
})

const isFormValid = computed(() => form.value.title.trim() && form.value.description.trim())

const hasImages = (html) => html && html.includes('<img')

// Handle description input from contenteditable
const onDescriptionInput = (e) => {
  form.value.description = e.target.innerHTML
}

// Handle paste - convert images to base64
const onPaste = async (e) => {
  const items = e.clipboardData?.items
  if (!items) return

  for (const item of items) {
    if (item.type.startsWith('image/')) {
      e.preventDefault()
      const file = item.getAsFile()
      if (file) {
        const base64 = await fileToBase64(file)
        insertImageAtCursor(base64)
      }
      return
    }
  }
}

const fileToBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(reader.result)
    reader.onerror = reject
    reader.readAsDataURL(file)
  })
}

const insertImageAtCursor = (base64) => {
  const img = document.createElement('img')
  img.src = base64
  img.style.maxWidth = '100%'
  img.style.borderRadius = '4px'
  img.style.margin = '8px 0'

  const selection = window.getSelection()
  if (selection.rangeCount > 0) {
    const range = selection.getRangeAt(0)
    range.deleteContents()
    range.insertNode(img)
    range.setStartAfter(img)
    range.collapse(true)
    selection.removeAllRanges()
    selection.addRange(range)
  } else {
    descriptionEditor.value?.appendChild(img)
  }

  form.value.description = descriptionEditor.value?.innerHTML || ''
}

// Reset editor when modal opens
watch(showModal, (val) => {
  if (val) {
    nextTick(() => {
      if (descriptionEditor.value) {
        descriptionEditor.value.innerHTML = ''
        form.value.description = ''
      }
    })
  }
})

const fetchFeedback = async (page = 1) => {
  loading.value = true
  try {
    const response = await fetch(`${API_BASE_URL}/api/feedback?page=${page}`, {
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
      },
    })
    if (response.ok) {
      const data = await response.json()
      feedbackList.value = data.data
      pagination.value = {
        currentPage: data.meta.current_page,
        lastPage: data.meta.last_page,
        total: data.meta.total,
        from: data.meta.from || 0,
        to: data.meta.to || 0
      }
    }
  } catch (e) {
    console.error('Failed to fetch feedback:', e)
  } finally {
    loading.value = false
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= pagination.value.lastPage) {
    fetchFeedback(page)
  }
}

const submitFeedback = async () => {
  if (!isFormValid.value || submitting.value) return
  submitting.value = true

  try {
    const response = await fetch(`${API_BASE_URL}/api/feedback`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        title: form.value.title.trim(),
        description: form.value.description,
      }),
    })

    if (response.ok) {
      form.value = { title: '', description: '' }
      if (descriptionEditor.value) descriptionEditor.value.innerHTML = ''
      showModal.value = false
      toast.success('Feedback sent!')
      fetchFeedback(1)
    } else {
      const data = await response.json()
      if (response.status === 429) {
        toast.error(data.message || 'Too many submissions.')
      } else if (response.status === 422) {
        toast.error(Object.values(data.errors || {})[0]?.[0] || 'Invalid input.')
      } else {
        toast.error('Failed to submit.')
      }
    }
  } catch (e) {
    toast.error('Failed to submit.')
  } finally {
    submitting.value = false
  }
}

const closeModal = () => {
  if (!submitting.value) {
    showModal.value = false
    form.value = { title: '', description: '' }
  }
}

const viewFeedback = (item) => selectedFeedback.value = item

const statusClass = (status) => ({
  'pending': 'bg-yellow-100 text-yellow-700',
  'reviewed': 'bg-blue-100 text-blue-700',
  'resolved': 'bg-green-100 text-green-700'
}[status] || 'bg-gray-100 text-gray-700')

const formatDate = (str) => new Date(str).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })

onMounted(() => fetchFeedback())
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

[contenteditable]:empty:before {
  content: attr(data-placeholder);
  color: #9ca3af;
  pointer-events: none;
}

[contenteditable] img {
  max-width: 100%;
  border-radius: 4px;
  margin: 8px 0;
}

.prose img {
  max-width: 100%;
  border-radius: 4px;
  margin: 8px 0;
}
</style>
