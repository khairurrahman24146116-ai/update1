import { ref } from 'vue'

const homepage = ref(null)
const loading = ref(false)
let fetched = false

export async function loadHomepage({ force = false } = {}) {
  if (fetched && !force) return homepage.value
  loading.value = true
  try {
    const r = await fetch('/api/homepage-settings/public')
    if (r.ok) {
      const data = await r.json()
      homepage.value = data && typeof data === 'object' ? data : null
    } else {
      homepage.value = null
    }
  } catch {
    homepage.value = null
  } finally {
    fetched = true
    loading.value = false
  }
  return homepage.value
}

export function useHomepage() {
  function asset(path) {
    if (!path) return null
    return /^https?:\/\//.test(path) ? path : `${location.origin}/storage/${path}`
  }
  return { homepage, loading, load: loadHomepage, asset }
}
