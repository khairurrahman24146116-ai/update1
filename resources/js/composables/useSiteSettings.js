import { ref } from 'vue'

const settings = ref(null)
const loading = ref(false)
let fetched = false

function applyFavicon(favicon) {
  const href = favicon
    ? (/^https?:\/\//.test(favicon) ? favicon : `${location.origin}/storage/${favicon}`)
    : `${location.origin}/images/profile/logo-lpi-transparent.png`
  let link = document.querySelector('link[rel="icon"]')
  if (!link) {
    link = document.createElement('link')
    link.rel = 'icon'
    document.head.appendChild(link)
  }
  link.href = href
}

async function fetchSettings({ force = false } = {}) {
  if (fetched && !force) return settings.value
  loading.value = true
  try {
    const r = await fetch('/api/site-settings/public')
    if (r.ok) {
      const data = await r.json()
      if (data && typeof data === 'object') {
        settings.value = data
        applyFavicon(data.favicon)
      } else {
        settings.value = null
      }
    }
  } catch {
    settings.value = null
  } finally {
    fetched = true
    loading.value = false
  }
  return settings.value
}

export function useSiteSettings() {
  async function load(opts) {
    await fetchSettings(opts)
  }
  function asset(path) {
    if (!path) return null
    return /^https?:\/\//.test(path) ? path : `${location.origin}/storage/${path}`
  }
  function get(key, fallback) {
    return settings.value?.[key] ?? fallback
  }
  return { settings, loading, load, get, asset }
}

export function arriveSiteSettings() {
  if (fetched || loading.value) return
  fetchSettings()
}