<template>
  <div class="animate-fade-in py-4 px-4 lg:px-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div>
        <h1 class="text-lg font-semibold text-base-content">Feedback</h1>
        <p class="text-xs text-base-content/60">Help us improve ScreenSense</p>
      </div>
      <button
        @click="showModal = true"
        class="btn btn-primary btn-sm gap-1.5"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card bg-base-100 border border-base-300 p-8 text-center">
      <span class="loading loading-spinner loading-md text-primary mx-auto"></span>
    </div>

    <!-- Empty -->
    <div v-else-if="feedbackList.length === 0" class="card bg-base-100 border border-base-300 p-8 text-center">
      <svg class="w-10 h-10 mx-auto text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
      </svg>
      <p class="mt-2 text-sm text-base-content/60">No feedback yet</p>
      <button @click="showModal = true" class="btn btn-primary btn-sm mt-3">
        Send Feedback
      </button>
    </div>

    <!-- Table -->
    <div v-else class="card bg-base-100 border border-base-300 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Title</th>
              <th class="hidden sm:table-cell w-24">Date</th>
              <th class="w-20">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in feedbackList"
              :key="item.id"
              @click="viewFeedback(item)"
              class="hover cursor-pointer"
            >
              <td>
                <div class="flex items-center gap-1.5">
                  <span class="text-sm text-base-content truncate">{{ item.title }}</span>
                  <svg v-if="hasImages(item.description)" class="w-3.5 h-3.5 text-base-content/40 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  <span v-if="item.reply" class="badge badge-xs badge-success"></span>
                </div>
              </td>
              <td class="text-xs text-base-content/60 hidden sm:table-cell">{{ formatDate(item.created_at) }}</td>
              <td>
                <span :class="statusBadgeClass(item.status)" class="badge badge-sm capitalize">
                  {{ item.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.lastPage > 1" class="px-3 py-2 border-t border-base-200 flex items-center justify-between text-xs">
        <span class="text-base-content/60">{{ pagination.from }}-{{ pagination.to }} of {{ pagination.total }}</span>
        <div class="join">
          <button
            @click="goToPage(pagination.currentPage - 1)"
            :disabled="pagination.currentPage === 1"
            class="join-item btn btn-xs"
          >
            Prev
          </button>
          <button
            @click="goToPage(pagination.currentPage + 1)"
            :disabled="pagination.currentPage === pagination.lastPage"
            class="join-item btn btn-xs"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Rate limit note -->
    <p class="text-xs text-base-content/40 mt-3 text-center">Limited to 3 submissions per hour</p>

    <!-- New Modal -->
    <div v-if="showModal" class="modal modal-open">
      <div class="modal-backdrop bg-black/50" @click="closeModal"></div>
      <div class="modal-box w-full max-w-xl">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-base font-semibold text-base-content">New Feedback</h2>
          <button @click="closeModal" class="btn btn-ghost btn-sm btn-circle">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <form @submit.prevent="submitFeedback" class="space-y-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Title</span>
            </label>
            <input
              v-model="form.title"
              type="text"
              placeholder="Brief summary of your feedback"
              maxlength="255"
              required
              class="input input-bordered w-full"
              :disabled="submitting"
            />
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Description</span>
              <span class="label-text-alt text-base-content/60">You can paste images</span>
            </label>
            <div
              ref="descriptionEditor"
              contenteditable="true"
              @input="onDescriptionInput"
              @paste="onPaste"
              class="textarea textarea-bordered min-h-[200px] max-h-[400px] overflow-y-auto prose prose-sm w-full"
              :class="{ 'opacity-50 pointer-events-none': submitting }"
              data-placeholder="Describe your feedback, bug report, or feature request. You can paste screenshots directly..."
            ></div>
          </div>
          <div class="modal-action">
            <button type="button" @click="closeModal" class="btn btn-ghost" :disabled="submitting">
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submitting || !isFormValid"
              class="btn btn-primary gap-1.5"
            >
              <span v-if="submitting" class="loading loading-spinner loading-sm"></span>
              {{ submitting ? 'Sending...' : 'Send Feedback' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- View Modal -->
    <div v-if="selectedFeedback" class="modal modal-open">
      <div class="modal-backdrop bg-black/50" @click="selectedFeedback = null"></div>
      <div class="modal-box w-full max-w-xl max-h-[90vh] flex flex-col">
        <div class="flex items-start justify-between pb-4 border-b border-base-200">
          <div class="flex-1 min-w-0 pr-2">
            <h2 class="text-base font-semibold text-base-content">{{ selectedFeedback.title }}</h2>
            <p class="text-xs text-base-content/60 mt-0.5">{{ formatDate(selectedFeedback.created_at) }}</p>
          </div>
          <div class="flex items-center gap-2">
            <span :class="statusBadgeClass(selectedFeedback.status)" class="badge capitalize">
              {{ selectedFeedback.status }}
            </span>
            <button @click="selectedFeedback = null" class="btn btn-ghost btn-sm btn-circle">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto py-4 space-y-3">
          <!-- User's feedback -->
          <div class="bg-base-200 rounded-lg p-3">
            <p class="text-xs font-medium text-base-content/60 mb-2">Your feedback</p>
            <div class="text-sm text-base-content prose prose-sm max-w-none" v-html="selectedFeedback.description"></div>
          </div>

          <!-- Admin Reply -->
          <div v-if="selectedFeedback.reply" class="alert alert-success">
            <div class="flex flex-col items-start gap-2 w-full">
              <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
                <span class="text-xs font-medium">Reply from ScreenSense</span>
                <span v-if="selectedFeedback.replied_at" class="text-xs opacity-70">{{ formatDate(selectedFeedback.replied_at) }}</span>
              </div>
              <p class="text-sm whitespace-pre-wrap">{{ selectedFeedback.reply }}</p>
            </div>
          </div>
          <div v-else class="text-center py-3 text-xs text-base-content/40">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Awaiting response
          </div>
        </div>

        <div class="modal-action pt-4 border-t border-base-200">
          <button @click="selectedFeedback = null" class="btn btn-ghost w-full">
            Close
          </button>
        </div>
      </div>
    </div>
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

const statusBadgeClass = (status) => ({
  'pending': 'badge-warning',
  'reviewed': 'badge-info',
  'resolved': 'badge-success'
}[status] || 'badge-ghost')

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
  color: oklch(var(--bc) / 0.4);
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

.modal-backdrop {
  position: fixed;
  inset: 0;
}
</style>
