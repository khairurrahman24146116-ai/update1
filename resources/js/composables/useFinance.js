import { ref } from 'vue'

export function formatRupiah(value) {
  const n = Number(value || 0)
  return 'Rp ' + n.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

export function formatDate(value) {
  if (!value) return '—'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return value
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

export function formatDateTime(value) {
  if (!value) return '—'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return value
  return d.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

export function monthLabel(period) {
  if (!period) return '—'
  const [y, m] = period.split('-')
  const d = new Date(Number(y), Number(m) - 1, 1)
  return d.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' })
}

export function currentPeriod() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

const FINANCE_UNIT_KEY = 'madani_finance_unit'

export function financeUnit() {
  return localStorage.getItem(FINANCE_UNIT_KEY) || 'sma'
}

export function setFinanceUnit(unit) {
  localStorage.setItem(FINANCE_UNIT_KEY, unit)
}

export function useFinanceApi() {
  const loading = ref(false)
  const error = ref('')

  function withUnit(url) {
    let role = null
    try {
      role = JSON.parse(localStorage.getItem('madani_user') || 'null')?.role || null
    } catch {
      role = null
    }
    if (role !== 'admin' || !url.startsWith('/api/finance/')) return url

    const [path, query = ''] = url.split('?')
    const params = new URLSearchParams(query)
    if (!params.has('unit')) params.set('unit', financeUnit())
    const qs = params.toString()
    return qs ? `${path}?${qs}` : path
  }

  async function request(url, options = {}) {
    const token = localStorage.getItem('madani_token')
    const headers = { Accept: 'application/json', ...(options.headers || {}) }
    if (token) headers.Authorization = `Bearer ${token}`
    if (options.body && !(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json'
      options.body = JSON.stringify(options.body)
    }
    const res = await fetch(withUnit(url), { ...options, headers })
    const data = res.status === 204 ? null : await res.json().catch(() => null)
    if (!res.ok) {
      const message = data?.message || Object.values(data?.errors || {}).flat().join(', ') || 'Permintaan gagal'
      throw Object.assign(new Error(message), { status: res.status, data })
    }
    return data
  }

  async function run(fn) {
    loading.value = true
    error.value = ''
    try {
      return await fn()
    } catch (e) {
      error.value = e.message
      throw e
    } finally {
      loading.value = false
    }
  }

  return { loading, error, request, run }
}
