import { ref } from 'vue'
import settingsService from '@/services/settingsService'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888'

// Module-level singleton state
const brandColor = ref('#F97316')
const logoUrl = ref(null)
const loaded = ref(false)

/**
 * Convert hex color to HSL
 */
function hexToHsl(hex) {
  hex = hex.replace('#', '')
  const r = parseInt(hex.substring(0, 2), 16) / 255
  const g = parseInt(hex.substring(2, 4), 16) / 255
  const b = parseInt(hex.substring(4, 6), 16) / 255

  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  let h, s
  const l = (max + min) / 2

  if (max === min) {
    h = s = 0
  } else {
    const d = max - min
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min)
    switch (max) {
      case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break
      case g: h = ((b - r) / d + 2) / 6; break
      case b: h = ((r - g) / d + 4) / 6; break
    }
  }

  return { h: h * 360, s: s * 100, l: l * 100 }
}

/**
 * Convert HSL to RGB triplet string (e.g. "249 115 22")
 */
function hslToRgbString(h, s, l) {
  s /= 100
  l /= 100
  const k = n => (n + h / 30) % 12
  const a = s * Math.min(l, 1 - l)
  const f = n => l - a * Math.max(-1, Math.min(k(n) - 3, Math.min(9 - k(n), 1)))

  const r = Math.round(f(0) * 255)
  const g = Math.round(f(8) * 255)
  const b = Math.round(f(4) * 255)

  return `${r} ${g} ${b}`
}

/**
 * Generate a full shade palette from a single hex color (treated as the 500 shade).
 * Returns an object mapping shade numbers to RGB triplet strings.
 */
function generatePalette(hex) {
  const { h, s, l } = hexToHsl(hex)

  // Shade definitions: [shade, lightness, saturation adjustment]
  const shades = [
    [50,  97,  0],
    [100, 93,  0],
    [200, 86,  0],
    [300, 75,  -3],
    [400, 60,  -2],
    [500, l,   0],     // Keep original lightness for 500
    [600, l - 8,  -5],
    [700, l - 18, -8],
    [800, l - 26, -10],
    [900, l - 32, -12],
    [950, l - 42, -15],
  ]

  const palette = {}
  for (const [shade, lightness, satAdj] of shades) {
    const clampedL = Math.max(2, Math.min(98, lightness))
    const clampedS = Math.max(0, Math.min(100, s + satAdj))
    palette[shade] = hslToRgbString(h, clampedS, clampedL)
  }

  return palette
}

/**
 * Apply brand color palette to CSS custom properties on :root
 */
function applyBrandColor(hex) {
  if (!hex || !/^#[0-9A-Fa-f]{6}$/.test(hex)) return

  const palette = generatePalette(hex)
  const root = document.documentElement

  for (const [shade, rgb] of Object.entries(palette)) {
    root.style.setProperty(`--color-brand-${shade}`, rgb)
  }
}

/**
 * Load branding settings from the API and apply them
 */
async function loadBranding() {
  try {
    const userSettings = await settingsService.getUserSettings()
    if (userSettings) {
      if (userSettings.brand_color) {
        brandColor.value = userSettings.brand_color
        applyBrandColor(userSettings.brand_color)
      }
      if (userSettings.organization_logo) {
        logoUrl.value = `${API_BASE_URL}/storage/${userSettings.organization_logo}`
      } else {
        logoUrl.value = null
      }
    }
    loaded.value = true
  } catch (error) {
    console.error('Failed to load branding:', error)
    loaded.value = true
  }
}

/**
 * Set the brand color and apply it immediately
 */
function setBrandColor(hex) {
  if (!hex) {
    // Reset to default orange
    hex = '#F97316'
  }
  brandColor.value = hex
  applyBrandColor(hex)
}

/**
 * Set the logo URL (or null to revert to default)
 */
function setLogoUrl(url) {
  logoUrl.value = url || null
}

export function useBranding() {
  return {
    brandColor,
    logoUrl,
    loaded,
    loadBranding,
    setBrandColor,
    setLogoUrl,
    applyBrandColor,
  }
}
