import { computed, ref } from 'vue'
import * as authApi from '../services/auth.js'
import { LOGIN_PATH } from '../site-paths.js'
import { can as canPermission, canAny as canAnyPermission, hasAdminAreaAccess, hasFinanceAccess, permissionsOf } from '../permissions.js'
const KEY = 'madani_token'
const KEY_USER = 'madani_user'
const token = ref(localStorage.getItem(KEY) || '')
let userInitial = null
try { userInitial = JSON.parse(localStorage.getItem(KEY_USER) || 'null') } catch { userInitial = null }
const user = ref(userInitial)
const loading = ref(false)
const error = ref('')
const errorCode = ref('')
let refreshPromise = null

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

export async function refreshUserSession() {
  if (!token.value) return null
  if (refreshPromise) return refreshPromise
  refreshPromise = (async () => {
    const res = await fetch('/api/me', { headers: { Authorization: `Bearer ${token.value}`, Accept: 'application/json' } })
    if (!res.ok) return null
    const data = await res.json()
    if (data.has_full_permission_access) {
      clearSession()
      location.href = `${LOGIN_PATH}?all_permissions=1`
      return null
    }
    user.value = data
    localStorage.setItem(KEY_USER, JSON.stringify(data))
    return data
  })().finally(() => {
    refreshPromise = null
  })
  return refreshPromise
}

export function useAuth() {
  async function doLogin(email, password) {
    loading.value = true; error.value = ''; errorCode.value = ''
    try {
      const res = await authApi.login(email, password)
      if (res.user?.has_full_permission_access) {
        clearSession()
        throw {
          status: 403,
          body: {
            code: 'all_permissions',
            message: 'Akun ini memiliki seluruh izin. Akun non-admin tidak diizinkan memakai akses penuh. Hubungi admin; lebih baik gunakan akun admin.',
          },
        }
      }
      token.value = res.token
      user.value = res.user
      localStorage.setItem(KEY, res.token)
      localStorage.setItem(KEY_USER, JSON.stringify(res.user))
      patchFetch()
      return res
    } catch (e) {
      errorCode.value = e.body?.code || ''
      error.value = e.body?.message || 'Gagal login'
      if (e.body?.errors) { error.value = Object.values(e.body.errors).flat().join(', '); errorCode.value = 'validation' }
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
  const permissions = computed(() => permissionsOf(user.value))
  const can = (permission) => canPermission(user.value, permission)
  const canAny = (list) => canAnyPermission(user.value, list)
  const isAdminAreaUser = computed(() => hasAdminAreaAccess(user.value))
  const isFinanceAreaUser = computed(() => hasFinanceAccess(user.value))

  async function refreshUser() {
    return refreshUserSession()
  }

  return { token, user, loading, error, errorCode, doLogin, doLogout, hasRole, permissions, can, canAny, isAdminAreaUser, isFinanceAreaUser, refreshUser }
}
