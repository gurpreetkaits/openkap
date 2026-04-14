<template>
  <div class="landing" ref="landingRef">
    <!-- Animated Background Orbs -->
    <div class="orb orb-1" />
    <div class="orb orb-2" />
    <div class="orb orb-3" />
    <div class="orb orb-4" />

    <!-- Navbar -->
    <nav class="navbar" :class="{ scrolled: scrolled }">
      <div class="nav-inner">
        <div class="nav-logo">
          <div class="logo-mark">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
              <rect width="28" height="28" rx="8" fill="url(#logoGrad)" />
              <circle cx="14" cy="14" r="5" fill="white" />
              <circle cx="14" cy="14" r="2.5" fill="url(#logoGrad)" />
              <defs>
                <linearGradient id="logoGrad" x1="0" y1="0" x2="28" y2="28">
                  <stop stop-color="#f97316" />
                  <stop offset="1" stop-color="#ea580c" />
                </linearGradient>
              </defs>
            </svg>
          </div>
          <span class="logo-text">OpenKap</span>
        </div>
        <div class="nav-links">
          <a href="#features">Features</a>
          <a href="#how-it-works">How it works</a>
          <a href="#pricing">Pricing</a>
        </div>
        <div class="nav-actions">
          <router-link to="/login" class="btn-ghost">Sign in</router-link>
          <router-link to="/login" class="btn-primary">Start free <span class="arrow">→</span></router-link>
        </div>
      </div>
    </nav>

    <!-- Hero -->
    <section class="hero" ref="heroRef">
      <div class="hero-content">
        <div class="hero-badge" :class="{ visible: heroVisible }">
          <span class="badge-dot" />
          Open source · Free to self-host
        </div>

        <h1 class="hero-headline" :class="{ visible: heroVisible }">
          Record. Share.<br />
          <span class="gradient-text">Ship faster.</span>
        </h1>

        <p class="hero-sub" :class="{ visible: heroVisible }">
          The modern screen recording platform for product teams. Capture your screen, share instantly, and collaborate with context — no friction.
        </p>

        <div class="hero-cta" :class="{ visible: heroVisible }">
          <router-link to="/login" class="btn-hero-primary">
            <span>Start recording free</span>
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </router-link>
          <a href="#how-it-works" class="btn-hero-ghost">
            <span class="play-icon">
              <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 2l9 5-9 5V2z" fill="currentColor"/></svg>
            </span>
            See how it works
          </a>
        </div>

        <p class="hero-meta">No credit card · Free forever plan · Self-hostable</p>
      </div>

      <!-- App Preview -->
      <div class="hero-preview" :class="{ visible: heroVisible }" ref="previewRef"
        @mousemove="onMouseMove" @mouseleave="onMouseLeave"
        :style="previewStyle">
        <div class="preview-window">
          <div class="preview-titlebar">
            <div class="dot red" /><div class="dot yellow" /><div class="dot green" />
            <div class="preview-url">app.openkap.co/videos</div>
          </div>
          <div class="preview-body">
            <!-- Sidebar -->
            <div class="preview-sidebar">
              <div class="sidebar-item active">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><rect x="1" y="1" width="12" height="12" rx="2" stroke="currentColor" stroke-width="1.2"/><path d="M5 4l5 3-5 3V4z" fill="currentColor"/></svg>
                Videos
              </div>
              <div class="sidebar-item">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><rect x="1" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="1" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/></svg>
                Workspaces
              </div>
              <div class="sidebar-item">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 1v12M1 7h12" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                New Recording
              </div>
            </div>
            <!-- Main area -->
            <div class="preview-main">
              <div class="preview-header-bar">
                <span class="preview-title-text">My Videos</span>
                <div class="preview-search" />
              </div>
              <div class="preview-grid">
                <div v-for="i in 6" :key="i" class="preview-card" :class="`card-${i}`">
                  <div class="preview-thumb">
                    <div class="thumb-play" />
                  </div>
                  <div class="preview-card-info">
                    <div class="preview-card-title" />
                    <div class="preview-card-meta" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Floating glass chips -->
        <div class="float-chip chip-1">
          <span class="chip-icon">🎬</span>
          <div>
            <div class="chip-label">Recording started</div>
            <div class="chip-sub">Screen + Audio captured</div>
          </div>
        </div>
        <div class="float-chip chip-2">
          <span class="chip-icon">🔗</span>
          <div>
            <div class="chip-label">Link copied!</div>
            <div class="chip-sub">Share instantly</div>
          </div>
        </div>
        <div class="float-chip chip-3">
          <div class="chip-avatars">
            <div class="avatar" style="background:#f97316">A</div>
            <div class="avatar" style="background:#3b82f6">B</div>
            <div class="avatar" style="background:#8b5cf6">C</div>
          </div>
          <div class="chip-label">3 teammates watching</div>
        </div>
      </div>
    </section>

    <!-- Logos / Social proof bar -->
    <div class="logos-bar">
      <p class="logos-label">Trusted by developers, designers, and PMs at</p>
      <div class="logos-row">
        <span v-for="co in companies" :key="co" class="logo-pill">{{ co }}</span>
      </div>
    </div>

    <!-- Features -->
    <section class="features" id="features">
      <div class="section-header">
        <div class="section-tag">Features</div>
        <h2 class="section-title">Everything you need to <span class="gradient-text">communicate visually</span></h2>
        <p class="section-sub">One platform to record, annotate, share, and collaborate — built for modern async teams.</p>
      </div>

      <div class="features-grid">
        <div
          v-for="(feat, i) in features"
          :key="feat.title"
          class="feature-card"
          :class="{ visible: featsVisible }"
          :style="{ transitionDelay: `${i * 80}ms` }"
          ref="featRef"
        >
          <div class="feature-icon" :style="{ background: feat.bg }">
            <span>{{ feat.icon }}</span>
          </div>
          <h3 class="feature-title">{{ feat.title }}</h3>
          <p class="feature-desc">{{ feat.desc }}</p>
          <div class="feature-arrow">→</div>
        </div>
      </div>
    </section>

    <!-- How it works -->
    <section class="hiw" id="how-it-works">
      <div class="hiw-inner">
        <div class="section-header">
          <div class="section-tag">How it works</div>
          <h2 class="section-title">From idea to shared video in <span class="gradient-text">60 seconds</span></h2>
        </div>
        <div class="steps">
          <div v-for="(step, i) in steps" :key="step.title" class="step">
            <div class="step-num">{{ String(i + 1).padStart(2, '0') }}</div>
            <div class="step-line" v-if="i < steps.length - 1" />
            <div class="step-content">
              <div class="step-icon">{{ step.icon }}</div>
              <h3 class="step-title">{{ step.title }}</h3>
              <p class="step-desc">{{ step.desc }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- AI Integration / MCP -->
    <section class="ai-integration" id="ai-integration">
      <div class="section-header">
        <div class="section-tag">AI Integration</div>
        <h2 class="section-title">Talk to your videos with <span class="gradient-text">Claude AI</span></h2>
        <p class="section-sub">Connect OpenKap to Claude Code, Claude Desktop, or any MCP-compatible tool. Ask questions about any shared video — transcriptions, summaries, and comments.</p>
      </div>

      <div class="mcp-showcase">
        <div class="mcp-terminal">
          <div class="terminal-bar">
            <div class="dot red" /><div class="dot yellow" /><div class="dot green" />
            <span class="terminal-title">Claude Code</span>
          </div>
          <div class="terminal-body">
            <div class="terminal-line user-line">
              <span class="terminal-prompt">you</span>
              <span class="terminal-text">What bugs were reported in this video? openkap.com/share/video/a8xK2m</span>
            </div>
            <div class="terminal-line tool-line">
              <span class="terminal-prompt">mcp</span>
              <span class="terminal-text terminal-dim">get_video_transcription("a8xK2m")</span>
            </div>
            <div class="terminal-line ai-line">
              <span class="terminal-prompt">claude</span>
              <span class="terminal-text">The video reports <strong>2 bugs</strong>: (1) Login button unresponsive on staging at <span class="terminal-ts">0:45</span>, and (2) session token expires too early at <span class="terminal-ts">2:12</span>. A teammate commented confirming both issues.</span>
            </div>
          </div>
        </div>

        <div class="mcp-cards">
          <div class="mcp-card">
            <div class="mcp-card-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            </div>
            <h4 class="mcp-card-title">Ask about transcriptions</h4>
            <p class="mcp-card-desc">"What was discussed at the 3-minute mark?"</p>
          </div>
          <div class="mcp-card">
            <div class="mcp-card-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <h4 class="mcp-card-title">Get summaries</h4>
            <p class="mcp-card-desc">"Summarize this product demo for me."</p>
          </div>
          <div class="mcp-card">
            <div class="mcp-card-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            </div>
            <h4 class="mcp-card-title">Read team comments</h4>
            <p class="mcp-card-desc">"What feedback did the team leave?"</p>
          </div>
        </div>

        <div class="mcp-install">
          <p class="mcp-install-label">Add to Claude Code in one command:</p>
          <div class="mcp-install-cmd">
            <code>claude mcp add openkap -- npx tsx mcp-server/server.ts</code>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats -->
    <section class="stats">
      <div class="stats-grid">
        <div v-for="stat in stats" :key="stat.label" class="stat-card">
          <div class="stat-value">{{ stat.value }}</div>
          <div class="stat-label">{{ stat.label }}</div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
      <div class="cta-box">
        <div class="cta-orb" />
        <div class="section-tag" style="margin-bottom: 1.5rem">Get started</div>
        <h2 class="cta-title">Start recording in<br /><span class="gradient-text">under a minute</span></h2>
        <p class="cta-sub">Free plan. No credit card. Self-hostable. Open source.</p>
        <router-link to="/login" class="btn-hero-primary" style="margin-top: 2rem; display: inline-flex">
          <span>Create free account</span>
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </router-link>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="footer-inner">
        <div class="footer-brand">
          <div class="nav-logo">
            <div class="logo-mark">
              <svg width="22" height="22" viewBox="0 0 28 28" fill="none">
                <rect width="28" height="28" rx="8" fill="url(#logoGrad2)" />
                <circle cx="14" cy="14" r="5" fill="white" />
                <circle cx="14" cy="14" r="2.5" fill="url(#logoGrad2)" />
                <defs>
                  <linearGradient id="logoGrad2" x1="0" y1="0" x2="28" y2="28">
                    <stop stop-color="#f97316" />
                    <stop offset="1" stop-color="#ea580c" />
                  </linearGradient>
                </defs>
              </svg>
            </div>
            <span class="logo-text" style="font-size: 0.95rem">OpenKap</span>
          </div>
          <p class="footer-tagline">Open source screen recording for modern teams.</p>
        </div>
        <div class="footer-links">
          <div class="footer-col">
            <div class="footer-col-title">Product</div>
            <a href="#features">Features</a>
            <a href="#how-it-works">How it works</a>
            <router-link to="/login">Sign up</router-link>
          </div>
          <div class="footer-col">
            <div class="footer-col-title">Open Source</div>
            <a href="https://github.com" target="_blank">GitHub</a>
            <a href="#" target="_blank">Self-host</a>
            <a href="#" target="_blank">Documentation</a>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© 2025 OpenKap. Open source under MIT license.</span>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'

const scrolled = ref(false)
const heroVisible = ref(false)
const featsVisible = ref(false)

const heroRef = ref(null)
const previewRef = ref(null)
const mouseX = ref(0)
const mouseY = ref(0)

const companies = ['Notion', 'Linear', 'Vercel', 'Stripe', 'Figma', 'Railway', 'Supabase']

const features = [
  { icon: '🎬', title: 'Instant Screen Recording', desc: 'Capture your screen, camera, or both with one click. Record tab, window, or full screen — no lag, no compression artifacts.', bg: 'rgba(249,115,22,0.1)' },
  { icon: '🔗', title: 'Shareable in One Click', desc: 'Every recording gets a unique link instantly. Share with anyone — no login required for viewers. Password-protect when needed.', bg: 'rgba(59,130,246,0.1)' },
  { icon: '💬', title: 'Contextual Comments', desc: 'Teammates leave timestamped comments directly on your video. React with emoji, thread replies, and resolve feedback loops fast.', bg: 'rgba(139,92,246,0.1)' },
  { icon: '✂️', title: 'Built-in Video Editor', desc: 'Trim, merge, and annotate videos right in the browser. No Premiere required. Export or keep it cloud-hosted.', bg: 'rgba(16,185,129,0.1)' },
  { icon: '🏢', title: 'Team Workspaces', desc: 'Organize recordings by team, project, or client. Invite members, manage permissions, and keep everyone aligned.', bg: 'rgba(245,158,11,0.1)' },
  { icon: '🔒', title: 'Self-Hostable & Open Source', desc: 'Run OpenKap on your own infrastructure. Full source code on GitHub. No vendor lock-in, no data leaving your servers.', bg: 'rgba(239,68,68,0.1)' },
]

const steps = [
  { icon: '🧩', title: 'Install the Extension', desc: 'Add OpenKap to Chrome in seconds. It lives quietly in your toolbar, ready when you need it.' },
  { icon: '⏺️', title: 'Hit Record', desc: 'Click record, choose your capture area, and start talking. Recording starts instantly — no waiting.' },
  { icon: '📤', title: 'Share Your Link', desc: 'When you stop recording, your video is ready. Copy the link and paste it anywhere — Slack, Notion, Linear, email.' },
]

const stats = [
  { value: '< 3s', label: 'Time to first recording' },
  { value: '100%', label: 'Open source codebase' },
  { value: '∞', label: 'Videos on self-host' },
  { value: '0', label: 'Vendor lock-in' },
]

const previewStyle = computed(() => {
  const rx = mouseX.value * 6
  const ry = mouseY.value * -6
  return {
    transform: `perspective(1200px) rotateY(${rx}deg) rotateX(${ry}deg) translateY(0)`,
  }
})

function onMouseMove(e) {
  const el = previewRef.value
  if (!el) return
  const rect = el.getBoundingClientRect()
  mouseX.value = ((e.clientX - rect.left) / rect.width - 0.5)
  mouseY.value = ((e.clientY - rect.top) / rect.height - 0.5)
}

function onMouseLeave() {
  mouseX.value = 0
  mouseY.value = 0
}

function onScroll() {
  scrolled.value = window.scrollY > 40
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })

  // Hero entrance
  requestAnimationFrame(() => {
    heroVisible.value = true
  })

  // Features scroll reveal
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) featsVisible.value = true
    })
  }, { threshold: 0.15 })

  const featSection = document.querySelector('.features')
  if (featSection) observer.observe(featSection)
})

onUnmounted(() => {
  window.removeEventListener('scroll', onScroll)
})
</script>

<style scoped>
/* ── Base ─────────────────────────────────────────────── */
.landing {
  min-height: 100vh;
  background: #fafaf9;
  color: #1c1917;
  font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
  overflow-x: hidden;
  position: relative;
}

/* ── Animated Orbs ────────────────────────────────────── */
.orb {
  position: fixed;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.45;
  pointer-events: none;
  z-index: 0;
  animation: orbFloat 18s ease-in-out infinite;
}
.orb-1 {
  width: 600px; height: 600px;
  background: radial-gradient(circle, #fed7aa 0%, #fb923c44 60%, transparent 100%);
  top: -200px; left: -150px;
  animation-delay: 0s;
}
.orb-2 {
  width: 500px; height: 500px;
  background: radial-gradient(circle, #fde68a55 0%, #fbbf2444 60%, transparent 100%);
  top: 100px; right: -100px;
  animation-delay: -6s;
}
.orb-3 {
  width: 400px; height: 400px;
  background: radial-gradient(circle, #dbeafe55 0%, #93c5fd33 60%, transparent 100%);
  top: 60vh; left: 20%;
  animation-delay: -12s;
}
.orb-4 {
  width: 350px; height: 350px;
  background: radial-gradient(circle, #ede9fe44 0%, #c4b5fd33 60%, transparent 100%);
  bottom: 20vh; right: 15%;
  animation-delay: -4s;
}
@keyframes orbFloat {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33%       { transform: translate(30px, -40px) scale(1.05); }
  66%       { transform: translate(-20px, 30px) scale(0.97); }
}

/* ── Navbar ───────────────────────────────────────────── */
.navbar {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 100;
  padding: 1rem 0;
  transition: all 0.3s ease;
}
.navbar.scrolled {
  background: rgba(255,255,255,0.82);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(0,0,0,0.06);
  padding: 0.65rem 0;
  box-shadow: 0 2px 20px rgba(0,0,0,0.06);
}
.nav-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  align-items: center;
  gap: 2rem;
}
.nav-logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  color: inherit;
}
.logo-mark { display: flex; }
.logo-text {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1c1917;
  letter-spacing: -0.02em;
}
.nav-links {
  display: flex;
  gap: 1.75rem;
  margin-left: 2rem;
  flex: 1;
}
.nav-links a {
  text-decoration: none;
  color: #57534e;
  font-size: 0.9rem;
  font-weight: 500;
  transition: color 0.2s;
}
.nav-links a:hover { color: #f97316; }
.nav-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.btn-ghost {
  text-decoration: none;
  color: #57534e;
  font-size: 0.875rem;
  font-weight: 500;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  transition: all 0.2s;
}
.btn-ghost:hover { background: rgba(0,0,0,0.05); color: #1c1917; }
.btn-primary {
  text-decoration: none;
  background: linear-gradient(135deg, #f97316, #ea580c);
  color: white;
  font-size: 0.875rem;
  font-weight: 600;
  padding: 0.5rem 1.25rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(249,115,22,0.3);
}
.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(249,115,22,0.4);
}
.arrow { transition: transform 0.2s; }
.btn-primary:hover .arrow { transform: translateX(3px); }

/* ── Hero ─────────────────────────────────────────────── */
.hero {
  position: relative;
  z-index: 1;
  min-height: 100vh;
  display: grid;
  grid-template-columns: 1fr 1fr;
  align-items: center;
  gap: 4rem;
  max-width: 1200px;
  margin: 0 auto;
  padding: 7rem 2rem 5rem;
}
.hero-content { display: flex; flex-direction: column; gap: 1.5rem; }

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255,255,255,0.8);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(249,115,22,0.2);
  border-radius: 100px;
  padding: 0.4rem 1rem;
  font-size: 0.8rem;
  font-weight: 500;
  color: #92400e;
  width: fit-content;
  opacity: 0;
  transform: translateY(16px);
  transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.hero-badge.visible { opacity: 1; transform: translateY(0); }
.badge-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: #f97316;
  animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.6; transform: scale(0.8); }
}

.hero-headline {
  font-size: clamp(2.8rem, 5vw, 4.2rem);
  font-weight: 800;
  letter-spacing: -0.04em;
  line-height: 1.08;
  color: #1c1917;
  opacity: 0;
  transform: translateY(24px);
  transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.1s;
}
.hero-headline.visible { opacity: 1; transform: translateY(0); }

.gradient-text {
  background: linear-gradient(135deg, #f97316 0%, #fb923c 40%, #fbbf24 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-sub {
  font-size: 1.1rem;
  line-height: 1.7;
  color: #78716c;
  max-width: 480px;
  opacity: 0;
  transform: translateY(24px);
  transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.2s;
}
.hero-sub.visible { opacity: 1; transform: translateY(0); }

.hero-cta {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  opacity: 0;
  transform: translateY(24px);
  transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.3s;
}
.hero-cta.visible { opacity: 1; transform: translateY(0); }

.btn-hero-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #f97316, #ea580c);
  color: white;
  text-decoration: none;
  font-size: 0.95rem;
  font-weight: 600;
  padding: 0.85rem 1.75rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(249,115,22,0.35);
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.btn-hero-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 32px rgba(249,115,22,0.45);
}

.btn-hero-ghost {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  text-decoration: none;
  color: #57534e;
  font-size: 0.9rem;
  font-weight: 500;
  padding: 0.85rem 1.25rem;
  border-radius: 12px;
  border: 1px solid rgba(0,0,0,0.1);
  background: rgba(255,255,255,0.6);
  backdrop-filter: blur(8px);
  transition: all 0.2s;
}
.btn-hero-ghost:hover {
  background: rgba(255,255,255,0.9);
  border-color: rgba(0,0,0,0.15);
  color: #1c1917;
}
.play-icon {
  width: 28px; height: 28px;
  background: linear-gradient(135deg, #f97316, #ea580c);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.hero-meta {
  font-size: 0.78rem;
  color: #a8a29e;
  opacity: 0;
  transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.4s;
}
.hero-cta.visible ~ .hero-meta { opacity: 1; }

/* ── App Preview ──────────────────────────────────────── */
.hero-preview {
  position: relative;
  z-index: 1;
  opacity: 0;
  transform: translateX(40px) perspective(1200px) rotateY(-8deg);
  transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.2s,
              transform 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.2s;
  will-change: transform;
}
.hero-preview.visible {
  opacity: 1;
  transform: perspective(1200px) rotateY(0deg);
}

.preview-window {
  background: rgba(255,255,255,0.85);
  backdrop-filter: blur(24px);
  border: 1px solid rgba(255,255,255,0.9);
  border-radius: 16px;
  box-shadow:
    0 2px 4px rgba(0,0,0,0.04),
    0 8px 24px rgba(0,0,0,0.08),
    0 32px 64px rgba(0,0,0,0.1),
    inset 0 1px 0 rgba(255,255,255,1);
  overflow: hidden;
}
.preview-titlebar {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.7rem 1rem;
  background: rgba(249,250,251,0.9);
  border-bottom: 1px solid rgba(0,0,0,0.06);
}
.dot {
  width: 10px; height: 10px;
  border-radius: 50%;
}
.dot.red    { background: #ff5f57; }
.dot.yellow { background: #febc2e; }
.dot.green  { background: #28c840; }
.preview-url {
  margin-left: 0.75rem;
  font-size: 0.7rem;
  color: #9ca3af;
  background: rgba(0,0,0,0.05);
  border-radius: 6px;
  padding: 0.2rem 0.75rem;
}
.preview-body {
  display: grid;
  grid-template-columns: 120px 1fr;
  height: 280px;
}
.preview-sidebar {
  border-right: 1px solid rgba(0,0,0,0.06);
  padding: 0.75rem 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.sidebar-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 0.6rem;
  border-radius: 7px;
  font-size: 0.7rem;
  color: #9ca3af;
  cursor: default;
}
.sidebar-item.active {
  background: rgba(249,115,22,0.1);
  color: #f97316;
  font-weight: 600;
}
.preview-main { padding: 0.75rem; }
.preview-header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.65rem;
}
.preview-title-text { font-size: 0.75rem; font-weight: 600; color: #374151; }
.preview-search {
  width: 80px; height: 20px;
  background: rgba(0,0,0,0.05);
  border-radius: 6px;
}
.preview-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
}
.preview-card {
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid rgba(0,0,0,0.06);
  background: white;
}
.preview-thumb {
  height: 56px;
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  display: flex;
  align-items: center;
  justify-content: center;
}
.card-2 .preview-thumb { background: linear-gradient(135deg, #dbeafe, #bfdbfe); }
.card-3 .preview-thumb { background: linear-gradient(135deg, #ede9fe, #ddd6fe); }
.card-4 .preview-thumb { background: linear-gradient(135deg, #dcfce7, #bbf7d0); }
.card-5 .preview-thumb { background: linear-gradient(135deg, #fee2e2, #fecaca); }
.card-6 .preview-thumb { background: linear-gradient(135deg, #fdf2f8, #fce7f3); }
.thumb-play {
  width: 18px; height: 18px;
  background: rgba(255,255,255,0.8);
  border-radius: 50%;
  position: relative;
}
.thumb-play::after {
  content: '';
  position: absolute;
  left: 55%; top: 50%;
  transform: translate(-50%, -50%);
  border: 5px solid transparent;
  border-left: 8px solid rgba(0,0,0,0.3);
}
.preview-card-info { padding: 0.35rem 0.4rem; }
.preview-card-title {
  height: 7px; border-radius: 4px;
  background: rgba(0,0,0,0.1);
  margin-bottom: 4px;
  width: 80%;
}
.preview-card-meta {
  height: 5px; border-radius: 3px;
  background: rgba(0,0,0,0.06);
  width: 55%;
}

/* Floating chips */
.float-chip {
  position: absolute;
  background: rgba(255,255,255,0.92);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.9);
  border-radius: 14px;
  padding: 0.65rem 0.9rem;
  box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06);
  display: flex;
  align-items: center;
  gap: 0.65rem;
  animation: chipFloat 4s ease-in-out infinite;
}
.chip-1 { bottom: -20px; left: -30px; animation-delay: 0s; }
.chip-2 { top: 20px; right: -30px; animation-delay: -1.5s; }
.chip-3 { bottom: 60px; right: -20px; animation-delay: -3s; }
@keyframes chipFloat {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}
.chip-icon { font-size: 1.2rem; }
.chip-label { font-size: 0.72rem; font-weight: 600; color: #1c1917; white-space: nowrap; }
.chip-sub { font-size: 0.65rem; color: #9ca3af; }
.chip-avatars { display: flex; }
.avatar {
  width: 22px; height: 22px;
  border-radius: 50%;
  border: 2px solid white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.6rem;
  font-weight: 700;
  color: white;
  margin-left: -6px;
}
.chip-avatars .avatar:first-child { margin-left: 0; }

/* ── Logos Bar ────────────────────────────────────────── */
.logos-bar {
  position: relative;
  z-index: 1;
  text-align: center;
  padding: 3rem 2rem;
  border-top: 1px solid rgba(0,0,0,0.06);
  border-bottom: 1px solid rgba(0,0,0,0.06);
  background: rgba(255,255,255,0.5);
  backdrop-filter: blur(8px);
}
.logos-label {
  font-size: 0.8rem;
  color: #a8a29e;
  margin-bottom: 1.25rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.logos-row {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 0.75rem;
}
.logo-pill {
  background: rgba(255,255,255,0.8);
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 100px;
  padding: 0.35rem 1.1rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #57534e;
  backdrop-filter: blur(8px);
}

/* ── Section Shared ───────────────────────────────────── */
.section-header {
  text-align: center;
  max-width: 640px;
  margin: 0 auto 4rem;
}
.section-tag {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #f97316;
  background: rgba(249,115,22,0.08);
  border: 1px solid rgba(249,115,22,0.15);
  border-radius: 100px;
  padding: 0.3rem 1rem;
  margin-bottom: 1.25rem;
}
.section-title {
  font-size: clamp(1.9rem, 3.5vw, 2.8rem);
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1.15;
  color: #1c1917;
  margin-bottom: 1rem;
}
.section-sub {
  font-size: 1rem;
  color: #78716c;
  line-height: 1.7;
}

/* ── Features ─────────────────────────────────────────── */
.features {
  position: relative;
  z-index: 1;
  max-width: 1200px;
  margin: 0 auto;
  padding: 6rem 2rem;
}
.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
}
.feature-card {
  background: rgba(255,255,255,0.72);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.9);
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04), 0 8px 24px rgba(0,0,0,0.04);
  cursor: default;
  transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
  opacity: 0;
  transform: translateY(24px);
  position: relative;
  overflow: hidden;
}
.feature-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(249,115,22,0.03) 0%, transparent 60%);
  opacity: 0;
  transition: opacity 0.3s;
}
.feature-card:hover { transform: translateY(-4px); box-shadow: 0 4px 16px rgba(0,0,0,0.06), 0 16px 40px rgba(0,0,0,0.08); }
.feature-card:hover::before { opacity: 1; }
.feature-card.visible { opacity: 1; transform: translateY(0); }
.feature-icon {
  width: 44px; height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  margin-bottom: 1.25rem;
}
.feature-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1c1917;
  margin-bottom: 0.5rem;
  letter-spacing: -0.01em;
}
.feature-desc {
  font-size: 0.875rem;
  color: #78716c;
  line-height: 1.65;
  margin-bottom: 1.25rem;
}
.feature-arrow {
  font-size: 1rem;
  color: #d6d3d1;
  transition: all 0.25s;
}
.feature-card:hover .feature-arrow { color: #f97316; transform: translateX(4px); }

/* ── How It Works ─────────────────────────────────────── */
.hiw {
  position: relative;
  z-index: 1;
  background: rgba(255,255,255,0.5);
  backdrop-filter: blur(12px);
  border-top: 1px solid rgba(0,0,0,0.06);
  border-bottom: 1px solid rgba(0,0,0,0.06);
  padding: 6rem 2rem;
}
.hiw-inner { max-width: 1000px; margin: 0 auto; }
.steps {
  display: flex;
  justify-content: center;
  gap: 0;
  position: relative;
}
.step {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  position: relative;
}
.step-num {
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: #f97316;
  margin-bottom: 1rem;
}
.step-line {
  position: absolute;
  top: 2.8rem;
  left: 55%;
  right: -45%;
  height: 1px;
  background: linear-gradient(to right, rgba(249,115,22,0.3), rgba(249,115,22,0.1));
}
.step-content {
  padding: 0 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.step-icon {
  width: 56px; height: 56px;
  border-radius: 16px;
  background: rgba(255,255,255,0.9);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(249,115,22,0.15);
  box-shadow: 0 4px 16px rgba(249,115,22,0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-bottom: 1.25rem;
}
.step-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1c1917;
  margin-bottom: 0.5rem;
}
.step-desc {
  font-size: 0.85rem;
  color: #78716c;
  line-height: 1.65;
}

/* ── AI Integration / MCP ────────────────────────────── */
.ai-integration {
  position: relative;
  z-index: 1;
  max-width: 1200px;
  margin: 0 auto;
  padding: 6rem 2rem;
}
.mcp-showcase {
  max-width: 860px;
  margin: 0 auto;
}
.mcp-terminal {
  background: #1c1917;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 40px rgba(0,0,0,0.15), 0 2px 8px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
}
.terminal-bar {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 0.75rem 1rem;
  background: rgba(255,255,255,0.06);
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.terminal-bar .dot { width: 10px; height: 10px; border-radius: 50%; }
.terminal-bar .red { background: #ef4444; }
.terminal-bar .yellow { background: #eab308; }
.terminal-bar .green { background: #22c55e; }
.terminal-title {
  margin-left: 0.75rem;
  font-size: 0.75rem;
  color: rgba(255,255,255,0.4);
  font-weight: 500;
}
.terminal-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.terminal-line {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  font-size: 0.85rem;
  line-height: 1.6;
}
.terminal-prompt {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  flex-shrink: 0;
  margin-top: 0.1rem;
}
.user-line .terminal-prompt {
  color: #f97316;
  background: rgba(249,115,22,0.12);
}
.tool-line .terminal-prompt {
  color: #a78bfa;
  background: rgba(167,139,250,0.12);
}
.ai-line .terminal-prompt {
  color: #34d399;
  background: rgba(52,211,153,0.12);
}
.terminal-text {
  color: rgba(255,255,255,0.85);
  font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
}
.terminal-text strong {
  color: #fff;
  font-weight: 600;
}
.terminal-dim {
  color: rgba(255,255,255,0.4);
  font-family: ui-monospace, 'SF Mono', monospace;
  font-size: 0.8rem;
}
.terminal-ts {
  color: #fbbf24;
  font-weight: 600;
}

.mcp-cards {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 2.5rem;
}
.mcp-card {
  background: rgba(255,255,255,0.72);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.9);
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  transition: all 0.25s ease;
}
.mcp-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}
.mcp-card-icon {
  width: 40px; height: 40px;
  border-radius: 10px;
  background: rgba(249,115,22,0.08);
  color: #f97316;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}
.mcp-card-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: #1c1917;
  margin-bottom: 0.35rem;
}
.mcp-card-desc {
  font-size: 0.8rem;
  color: #78716c;
  font-style: italic;
  line-height: 1.5;
}

.mcp-install {
  text-align: center;
}
.mcp-install-label {
  font-size: 0.8rem;
  color: #78716c;
  margin-bottom: 0.5rem;
  font-weight: 500;
}
.mcp-install-cmd {
  display: inline-block;
  background: #1c1917;
  border-radius: 10px;
  padding: 0.65rem 1.25rem;
}
.mcp-install-cmd code {
  font-family: ui-monospace, 'SF Mono', monospace;
  font-size: 0.8rem;
  color: rgba(255,255,255,0.8);
  letter-spacing: -0.01em;
}

/* ── Stats ────────────────────────────────────────────── */
.stats {
  position: relative;
  z-index: 1;
  max-width: 1000px;
  margin: 0 auto;
  padding: 5rem 2rem;
}
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
}
.stat-card {
  background: rgba(255,255,255,0.72);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.9);
  border-radius: 20px;
  padding: 2rem;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.stat-value {
  font-size: 2.4rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  background: linear-gradient(135deg, #f97316, #fbbf24);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 0.4rem;
}
.stat-label {
  font-size: 0.82rem;
  color: #78716c;
  font-weight: 500;
}

/* ── CTA ──────────────────────────────────────────────── */
.cta-section {
  position: relative;
  z-index: 1;
  padding: 6rem 2rem;
}
.cta-box {
  max-width: 680px;
  margin: 0 auto;
  text-align: center;
  background: rgba(255,255,255,0.75);
  backdrop-filter: blur(24px);
  border: 1px solid rgba(255,255,255,0.95);
  border-radius: 28px;
  padding: 4rem 3rem;
  box-shadow: 0 8px 40px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04);
  position: relative;
  overflow: hidden;
}
.cta-orb {
  position: absolute;
  width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(249,115,22,0.12) 0%, transparent 70%);
  border-radius: 50%;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  pointer-events: none;
}
.cta-title {
  font-size: clamp(2rem, 4vw, 2.8rem);
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1.15;
  color: #1c1917;
  margin-bottom: 1rem;
}
.cta-sub { font-size: 0.95rem; color: #78716c; line-height: 1.6; }

/* ── Footer ───────────────────────────────────────────── */
.footer {
  position: relative;
  z-index: 1;
  border-top: 1px solid rgba(0,0,0,0.07);
  background: rgba(255,255,255,0.6);
  backdrop-filter: blur(12px);
  padding: 3.5rem 2rem 2rem;
}
.footer-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  gap: 3rem;
  margin-bottom: 2.5rem;
}
.footer-tagline {
  margin-top: 0.5rem;
  font-size: 0.82rem;
  color: #a8a29e;
  max-width: 200px;
  line-height: 1.5;
}
.footer-links { display: flex; gap: 4rem; }
.footer-col {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}
.footer-col-title {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #1c1917;
  margin-bottom: 0.25rem;
}
.footer-col a {
  text-decoration: none;
  font-size: 0.85rem;
  color: #78716c;
  transition: color 0.2s;
}
.footer-col a:hover { color: #f97316; }
.footer-bottom {
  max-width: 1200px;
  margin: 0 auto;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(0,0,0,0.06);
  font-size: 0.78rem;
  color: #a8a29e;
}

/* ── Responsive ───────────────────────────────────────── */
@media (max-width: 1024px) {
  .features-grid { grid-template-columns: repeat(2, 1fr); }
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .mcp-cards { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
  .hero {
    grid-template-columns: 1fr;
    padding-top: 6rem;
    text-align: center;
  }
  .hero-badge, .hero-sub, .hero-cta { align-self: center; margin: 0 auto; }
  .hero-cta { justify-content: center; }
  .hero-preview { display: none; }
  .nav-links { display: none; }
  .features-grid { grid-template-columns: 1fr; }
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .mcp-cards { grid-template-columns: 1fr; }
  .mcp-install-cmd { max-width: 100%; overflow-x: auto; }
  .mcp-install-cmd code { font-size: 0.7rem; }
  .terminal-line { flex-direction: column; gap: 0.25rem; }
  .steps { flex-direction: column; gap: 2rem; }
  .step-line { display: none; }
  .footer-inner { flex-direction: column; }
}
</style>
