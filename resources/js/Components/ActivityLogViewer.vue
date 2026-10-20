<template>
  <div class="space-y-4">
    <div class="bg-white p-4 sm:p-6 rounded-xl border border-outline-variant shadow-sm space-y-4">
      <div v-if="heading" class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 class="font-headline-md text-headline-md">Log Aktivitas</h2>
          <p class="text-xs text-on-surface-variant">Jejak audit tindakan pengguna pada sistem. Log tidak dapat diubah.</p>
        </div>
        <span class="badge bg-surface-container-low text-on-surface-variant">{{ meta.total }} baris</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-2">
        <div class="xl:col-span-2 relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant !w-4 !h-4">search</span>
          <input v-model="filters.search" @keyup.enter="apply" placeholder="Cari aksi, deskripsi, pengguna, IP..." class="input pl-9" />
        </div>
        <select v-model="filters.module" @change="onModuleChange" class="input">
          <option value="">Semua modul</option>
          <option v-for="m in options.modules" :key="m.value" :value="m.value">{{ m.label }}</option>
        </select>
        <select v-model="filters.action" class="input">
          <option value="">Semua aksi</option>
          <option v-for="a in filteredActions" :key="a.value" :value="a.value">{{ a.label }}</option>
        </select>
        <select v-model="filters.user_id" class="input">
          <option value="">Semua pengguna</option>
          <option v-for="u in options.users" :key="u.id" :value="u.id">{{ u.name }}{{ u.role ? ` · ${roleLabel(u.role)}` : '' }}</option>
        </select>
        <div class="flex items-center gap-2">
          <input v-model="filters.from" type="date" class="input min-w-0" title="Dari tanggal" />
          <span class="text-xs text-on-surface-variant">s.d.</span>
          <input v-model="filters.to" type="date" class="input min-w-0" title="Sampai tanggal" />
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <button @click="apply" class="btn btn--primary btn--sm">
          <span class="material-symbols-outlined !w-4 !h-4">filter_alt</span> Terapkan
        </button>
        <button @click="reset" class="btn btn--ghost btn--sm">Reset</button>
        <button @click="load" :disabled="loading" class="btn btn--ghost btn--sm disabled:opacity-50">
          <span class="material-symbols-outlined !w-4 !h-4">refresh</span> Muat Ulang
        </button>
        <span class="flex-1"></span>
        <button @click="download('csv')" :disabled="exporting !== '' || !meta.total" class="btn btn--ghost btn--sm disabled:opacity-40">
          <span class="material-symbols-outlined !w-4 !h-4">download</span> CSV
        </button>
        <button @click="download('pdf')" :disabled="exporting !== '' || !meta.total" class="btn btn--ghost btn--sm disabled:opacity-40">
          <span class="material-symbols-outlined !w-4 !h-4">picture_as_pdf</span> {{ exporting === 'pdf' ? 'Menyiapkan…' : 'PDF' }}
        </button>
      </div>

      <p v-if="message" class="text-xs font-bold px-3 py-2 rounded-lg border" :class="messageType === 'error' ? 'bg-red-50 text-error border-red-600' : 'bg-green-50 text-green-700 border-green-600'">{{ message }}</p>
    </div>

    <div class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-x-auto">
      <table class="w-full text-sm min-w-[920px]">
        <thead class="bg-primary text-on-primary">
          <tr>
            <th class="p-3 text-left font-bold text-xs">Waktu WIB</th>
            <th class="p-3 text-left font-bold text-xs">Pengguna</th>
            <th class="p-3 text-left font-bold text-xs">Modul</th>
            <th class="p-3 text-left font-bold text-xs">Aksi</th>
            <th class="p-3 text-left font-bold text-xs">Deskripsi</th>
            <th class="p-3 text-left font-bold text-xs">IP</th>
            <th class="p-3 text-right font-bold text-xs">Detail</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs" :key="log.id" class="border-t border-outline-variant/60 hover:bg-surface-container-low/50 align-top">
            <td class="p-3 whitespace-nowrap text-xs text-on-surface-variant">{{ log.created_at_wib }}</td>
            <td class="p-3">
              <div class="font-bold text-xs">{{ log.user_name }}</div>
              <div class="text-[11px] text-on-surface-variant">{{ roleLabel(log.user_role) }}</div>
            </td>
            <td class="p-3 text-xs">{{ log.module_label }}</td>
            <td class="p-3">
              <div class="text-xs font-bold">{{ log.action_label }}</div>
              <div class="text-[11px] text-on-surface-variant font-mono">{{ log.action }}</div>
            </td>
            <td class="p-3 text-xs max-w-[28rem]">{{ log.description }}</td>
            <td class="p-3 text-xs font-mono">{{ log.ip_address || '-' }}</td>
            <td class="p-3 text-right">
              <button @click="selected = log" class="btn btn--ghost btn--sm">Lihat</button>
            </td>
          </tr>
          <tr v-if="!logs.length && !loading">
            <td colspan="7" class="p-8 text-center text-sm text-on-surface-variant">Tidak ada log yang cocok dengan filter ini.</td>
          </tr>
          <tr v-if="loading">
            <td colspan="7" class="p-8 text-center text-sm text-on-surface-variant">Memuat log…</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="meta.total" class="flex flex-wrap items-center justify-between gap-2 text-xs">
      <span class="text-on-surface-variant">Total {{ meta.total }} — Hal {{ meta.current_page }} / {{ meta.last_page }}</span>
      <div class="flex items-center gap-2">
        <select v-model.number="perPage" @change="goPage(1)" class="input h-8 py-0 w-24 text-xs">
          <option :value="25">25 / hal</option>
          <option :value="50">50 / hal</option>
          <option :value="100">100 / hal</option>
        </select>
        <button @click="goPage(meta.current_page - 1)" :disabled="meta.current_page <= 1" class="btn btn--ghost btn--sm disabled:opacity-40">‹ Prev</button>
        <button @click="goPage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page" class="btn btn--ghost btn--sm disabled:opacity-40">Next ›</button>
      </div>
    </div>

    <Teleport to="body">
      <Transition name="fade">
        <div v-if="selected" class="fixed inset-0 z-[80] bg-black/40 p-3 sm:p-6 flex items-start sm:items-center justify-center overflow-y-auto" @click.self="selected = null">
          <div class="bg-white w-full max-w-3xl rounded-xl border border-outline-variant shadow-lg my-4">
            <div class="flex items-start justify-between gap-3 p-4 border-b border-outline-variant">
              <div>
                <h3 class="font-headline-sm text-headline-sm">Detail Log</h3>
                <p class="text-xs text-on-surface-variant">#{{ selected.id }} · {{ selected.created_at_wib }} WIB</p>
              </div>
              <button @click="selected = null" class="btn btn--ghost btn--sm" aria-label="Tutup">✕</button>
            </div>
            <div class="p-4 space-y-4">
              <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div><dt class="text-xs font-bold text-on-surface-variant">Pengguna</dt><dd>{{ selected.user_name }} ({{ roleLabel(selected.user_role) }})</dd></div>
                <div><dt class="text-xs font-bold text-on-surface-variant">Modul</dt><dd>{{ selected.module_label }}</dd></div>
                <div><dt class="text-xs font-bold text-on-surface-variant">Aksi</dt><dd class="font-mono text-xs">{{ selected.action }}</dd></div>
                <div><dt class="text-xs font-bold text-on-surface-variant">Target</dt><dd class="font-mono text-xs">{{ selected.target_label || '-' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs font-bold text-on-surface-variant">Deskripsi</dt><dd>{{ selected.description }}</dd></div>
                <div><dt class="text-xs font-bold text-on-surface-variant">IP Address</dt><dd class="font-mono text-xs">{{ selected.ip_address || '-' }}</dd></div>
                <div><dt class="text-xs font-bold text-on-surface-variant">User Agent</dt><dd class="text-xs break-words">{{ selected.user_agent || '-' }}</dd></div>
              </dl>
              <div>
                <div class="text-xs font-bold text-on-surface-variant mb-1">Metadata (tersanitasi)</div>
                <pre class="text-xs bg-surface-container-low border border-outline-variant rounded-lg p-3 overflow-auto max-h-72 whitespace-pre-wrap break-words">{{ prettyMetadata(selected.metadata) }}</pre>
              </div>
            </div>
            <div class="p-4 border-t border-outline-variant flex justify-end">
              <button @click="selected = null" class="btn btn--primary btn--sm">Tutup</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'

defineProps({ heading: { type: Boolean, default: true } })

const filters = reactive({ search: '', module: '', action: '', user_id: '', from: '', to: '' })
const options = ref({ actions: [], modules: [], users: [] })
const logs = ref([])
const meta = ref({ total: 0, current_page: 1, last_page: 1 })
const perPage = ref(25)
const loading = ref(false)
const exporting = ref('')
const message = ref('')
const messageType = ref('success')
const selected = ref(null)

const token = () => localStorage.getItem('madani_token')
const auth = () => ({ Authorization: `Bearer ${token()}` })

const filteredActions = computed(() =>
  options.value.actions.filter((a) => !filters.module || a.module === filters.module),
)

function roleLabel(role) {
  return ({ admin: 'Admin', guru: 'Guru', bendahara: 'Bendahara', bendahara_lpi: 'Bendahara LPI', bendahara_sma: 'Bendahara SMA', wali_murid: 'Wali Murid' })[role] || role || '-'
}

function notify(text, type = 'success') {
  message.value = text
  messageType.value = type
  window.clearTimeout(notify.timer)
  notify.timer = window.setTimeout(() => { message.value = '' }, 5000)
}

function query() {
  const q = new URLSearchParams()
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) q.set(key, String(value))
  })
  q.set('per_page', String(perPage.value))
  return q
}

function apply() {
  goPage(1)
}

function reset() {
  Object.assign(filters, { search: '', module: '', action: '', user_id: '', from: '', to: '' })
  goPage(1)
}

function onModuleChange() {
  if (filters.action && !filteredActions.value.some((a) => a.value === filters.action)) {
    filters.action = ''
  }
}

function goPage(page) {
  meta.value.current_page = Math.max(1, page)
  load()
}

async function load() {
  loading.value = true
  try {
    const q = query()
    q.set('page', String(meta.value.current_page))
    const response = await fetch(`/api/activity-logs?${q}`, { headers: auth() })
    if (!response.ok) {
      const error = await response.json().catch(() => ({}))
      const detail = Object.values(error.errors || {}).flat()[0] || error.message
      throw new Error(detail || 'Gagal memuat log aktivitas.')
    }
    const payload = await response.json()
    logs.value = payload.data ?? []
    meta.value = {
      total: payload.total ?? logs.value.length,
      current_page: payload.current_page ?? 1,
      last_page: payload.last_page ?? 1,
    }
  } catch (error) {
    notify(error.message || 'Gagal memuat log aktivitas.', 'error')
  } finally {
    loading.value = false
  }
}

async function loadOptions() {
  try {
    const response = await fetch('/api/activity-logs/options', { headers: auth() })
    if (response.ok) options.value = await response.json()
  } catch {}
}

async function download(type) {
  if (exporting.value) return
  exporting.value = type
  message.value = ''
  try {
    const q = query()
    const response = await fetch(`/api/activity-logs/${type}?${q}`, { headers: auth() })
    if (!response.ok) {
      const payload = await response.json().catch(() => ({}))
      const detail = payload.errors?.report?.[0]
        || Object.values(payload.errors || {}).flat()[0]
        || payload.message
      throw new Error(detail || `Gagal membuat ${type.toUpperCase()}.`)
    }
    const blob = await response.blob()
    const disposition = response.headers.get('Content-Disposition') || ''
    const match = disposition.match(/filename="?([^";]+)"?/i)
    const filename = match?.[1] || `log-aktivitas.${type}`
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
    notify(`${type.toUpperCase()} berhasil dibuat.`)
  } catch (error) {
    notify(error.message || `Gagal membuat ${type.toUpperCase()}.`, 'error')
  } finally {
    exporting.value = ''
  }
}

function prettyMetadata(metadata) {
  if (!metadata || (typeof metadata === 'object' && !Object.keys(metadata).length)) return 'Tidak ada metadata.'
  try {
    return JSON.stringify(metadata, null, 2)
  } catch {
    return String(metadata)
  }
}

watch(() => filters.user_id, (value) => {
  if (value === '') return
  filters.user_id = Number(value)
})

onMounted(async () => {
  await Promise.all([loadOptions(), load()])
})
</script>
