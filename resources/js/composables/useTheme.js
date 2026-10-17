import { computed, ref } from 'vue'

const STORAGE_KEY = 'lpi-theme'

const media = typeof window !== 'undefined' && window.matchMedia
  ? window.matchMedia('(prefers-color-scheme: dark)')
  : null

function readStored() {
  try {
    const value = localStorage.getItem(STORAGE_KEY)
    return value === 'light' || value === 'dark' ? value : null
  } catch {
    return null
  }
}

const mode = ref(readStored())
const systemDark = ref(media ? media.matches : false)

const isDark = computed(() => (mode.value ? mode.value === 'dark' : systemDark.value))

function apply() {
  if (typeof document === 'undefined') {
    return
  }
  const theme = isDark.value ? 'dark' : 'light'
  document.documentElement.setAttribute('data-theme', theme)
  document.documentElement.style.colorScheme = theme
}

function setMode(next) {
  mode.value = next === 'light' || next === 'dark' ? next : null
  try {
    if (mode.value) {
      localStorage.setItem(STORAGE_KEY, mode.value)
    } else {
      localStorage.removeItem(STORAGE_KEY)
    }
  } catch {
    /* localStorage tidak tersedia */
  }
  apply()
}

function toggle() {
  setMode(isDark.value ? 'light' : 'dark')
}

if (media) {
  const onChange = (event) => {
    systemDark.value = event.matches
    if (!mode.value) {
      apply()
    }
  }
  if (typeof media.addEventListener === 'function') {
    media.addEventListener('change', onChange)
  } else if (typeof media.addListener === 'function') {
    media.addListener(onChange)
  }
}

apply()

export function useTheme() {
  return { mode, isDark, setMode, toggle }
}
