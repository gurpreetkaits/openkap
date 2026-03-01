import { createApp } from 'vue'
import { MotionPlugin } from '@vueuse/motion'
import posthog from 'posthog-js'
import App from './App.vue'
import { router } from './router'
import './style.css'

// Initialize PostHog - lightweight analytics
const posthogKey = import.meta.env.VITE_POSTHOG_KEY
const posthogHost = import.meta.env.VITE_POSTHOG_HOST || 'https://us.i.posthog.com'

if (posthogKey) {
    posthog.init(posthogKey, {
        api_host: posthogHost,
        person_profiles: 'anonymous',
        autocapture: true,
        capture_pageview: true,
        capture_pageleave: true,
        disable_session_recording: true,
        enable_heatmaps: false,
    })

    // Capture JS errors only
    window.addEventListener('error', (event) => {
        posthog.capture('js_error', {
            message: event.message,
            source: event.filename,
            line: event.lineno,
        })
    })
}

const app = createApp(App)
app.use(router)
app.use(MotionPlugin)
app.mount('#app')
