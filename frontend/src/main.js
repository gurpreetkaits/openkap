import { createApp } from 'vue'
import { MotionPlugin } from '@vueuse/motion'
import * as Sentry from '@sentry/vue'
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

// Initialize Sentry error monitoring - only when a DSN is configured
const sentryDsn = import.meta.env.VITE_SENTRY_DSN
if (sentryDsn) {
    Sentry.init({
        app,
        dsn: sentryDsn,
        environment: import.meta.env.MODE,
        integrations: [
            // Instruments vue-router navigations for performance tracing
            Sentry.browserTracingIntegration({ router }),
        ],
        // Performance tracing sample rate (0.0 = off, 1.0 = 100%)
        tracesSampleRate: Number(import.meta.env.VITE_SENTRY_TRACES_SAMPLE_RATE ?? 0),
    })
}

app.use(router)
app.use(MotionPlugin)
app.mount('#app')
