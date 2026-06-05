<template>
  <div class="min-h-screen bg-[#18181b]">
    <router-view />
    <DownloadTray />
    <ToastContainer />
  </div>
</template>

<script>
import ToastContainer from '@/components/ToastContainer.vue'
import DownloadTray from '@/components/Global/DownloadTray.vue'

export default {
  name: 'App',
  components: {
    ToastContainer,
    DownloadTray
  }
}
</script>
