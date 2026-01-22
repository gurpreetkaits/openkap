<template>
  <div class="min-h-screen bg-base-200 flex flex-col justify-center items-center">
    <div class="text-center">
      <h1 class="text-9xl font-bold text-base-content/20">404</h1>
      <h2 class="text-2xl font-semibold text-base-content mb-4">Page not found</h2>
      <p class="text-base-content/60 mb-8">Sorry, we couldn't find the page you're looking for.</p>
      <router-link
        to="/videos"
        class="btn btn-primary"
      >
        Go back home
      </router-link>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NotFoundView'
}
</script>
