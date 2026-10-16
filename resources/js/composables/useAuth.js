import { ref } from 'vue'
import * as authApi from '../services/auth.js'
import { LOGIN_PATH } from '../site-paths.js'
const KEY = 'madani_token'
const KEY_USER = 'madani_user'
const token = ref(localStorage.getItem(KEY) || '')
let userInitial = null
try { userInitial = JSON.parse(localStorage.getItem(KEY_USER) || 'null') } catch { userInitial = null }
const user = ref(userInitial)
const loading = ref(false)
const error = ref('')

function clearSession() {
  token.value = ''; user.value = null
  localStorage.removeItem(KEY); localStorage.removeItem(KEY_USER)
}
function handleUnauthorized() {
  if (!token.value) return
  clearSession()
  const expired = location.pathname !== LOGIN_PATH
  location.href = expired ? `${LOGIN_PATH}?expired=1` : LOGIN_PATH
}

function patchFetch() {
  if (window.__madaniAuthPatched) return
  window.__madaniAuthPatched = true
  const origFetch = window.fetch.bind(window)
  window.fetch = async (url, opts = {}) => {
    if (typeof url === 'string' && url.startsWith('/api/')) {
      const init = { ...opts }
      if (token.value) {
        init.headers = { ...(init.headers || {}), Authorization: `Bearer ${token.value}`, Accept: 'application/json' }
      }
      const res = await origFetch(url, init)
      if (res.status === 401 && !url.includes('/login')) handleUnauthorized()
      return res
    }
    return origFetch(url, opts)
  }
}

if (token.value) patchFetch()

export function useAuth() {
  async function doLogin(email, password) {
    loading.value = true; error.value = ''
    try {
      const res = await authApi.login(email, password)
      token.value = res.token
      user.value = res.user
      localStorage.setItem(KEY, res.token)
      localStorage.setItem(KEY_USER, JSON.stringify(res.user))
      patchFetch()
      return res
    } catch (e) {
      error.value = e.body?.message || 'Gagal login'
      if (e.body?.errors) error.value = Object.values(e.body.errors).flat().join(', ')
      throw e
    } finally { loading.value = false }
  }
  function doLogout() {
    if (token.value) authApi.logout(token.value).catch(()=>{})
    clearSession()
  }
  function hasRole(...roles) {
    const role = user.value?.role
    return Boolean(role) && roles.includes(role)
  }
  return { token, user, loading, error, doLogin, doLogout, hasRole }
}
