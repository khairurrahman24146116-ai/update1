<template>
  <AppShell title="PPDB" subtitle="Statistik, pendaftar, dan riwayat pendaftaran santri baru">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <div class="flex items-center gap-2">
        <router-link to="/app/admin" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-bold border border-outline-variant bg-surface-container-low text-on-surface-variant hover:text-primary transition-colors">
          <span class="material-symbols-outlined text-[16px]">arrow_back</span> Kembali
        </router-link>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button v-for="t in tabs" :key="t.key" @click="tab = t.key" class="px-3 py-1.5 rounded-full text-xs font-bold border" :class="tab === t.key ? 'bg-primary text-on-primary border-primary' : 'bg-surface-container-low border-outline-variant text-on-surface-variant hover:text-primary'">{{ t.label }}</button>
        <router-link to="/app/admin/settings?tab=ppdb" class="text-xs font-semibold text-primary hover:underline ml-1">Kelola Gelombang →</router-link>
      </div>
    </div>

    <div v-if="loading" class="py-10 text-center text-on-surface-variant">Memuat data PPDB…</div>
    <p v-else-if="error" class="text-red-700 bg-red-50 border border-red-200 rounded-xl px-3 py-2 text-sm mb-4">{{ error }}</p>

    <template v-if="stats">
      <div v-if="tab === 'ringkasan'" class="space-y-4">
        <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-4">
          <h3 class="font-bold text-sm mb-2">Cari Pendaftar</h3>
          <div class="flex flex-wrap gap-2 items-center">
            <input v-model="filters.search" @keyup.enter="searchFromDashboard" class="input max-w-md" placeholder="Cari nama / NISN / no. pendaftaran…" />
            <button @click="searchFromDashboard" class="btn btn--primary btn--sm">Cari</button>
            <button v-if="filters.search" @click="clearDashboardSearch" class="btn btn--sm">Reset</button>
          </div>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-4"><p class="text-xs text-on-surface-variant font-medium">Total Pendaftar</p><p class="text-3xl font-bold text-on-surface mt-1">{{ stats.total }}</p></div>
          <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-4"><p class="text-xs text-on-surface-variant font-medium">Hari Ini</p><p class="text-3xl font-bold text-primary mt-1">{{ stats.today }}</p></div>
          <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-4"><p class="text-xs text-on-surface-variant font-medium">Pending</p><p class="text-3xl font-bold text-secondary mt-1">{{ stats.by_status?.pending || 0 }}</p></div>
          <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-4"><p class="text-xs text-on-surface-variant font-medium">Diterima</p><p class="text-3xl font-bold text-tertiary mt-1">{{ stats.by_status?.diterima || 0 }}</p></div>
        </div>
        <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-4">
          <h3 class="font-bold text-sm mb-3">Per Jalur</h3>
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div v-for="j in jalurKeys" :key="j.key" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-3">
              <p class="text-xs text-on-surface-variant font-medium">{{ j.label }}</p>
              <p class="text-2xl font-bold text-on-surface mt-1">{{ stats.by_jenjang?.[j.key] || 0 }}</p>
            </div>
          </div>
        </div>
        <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-4">
          <div class="flex items-center justify-between gap-2 mb-3"><h3 class="font-bold text-sm">Pendaftar Hari Ini</h3><button @click="loadToday" class="text-xs font-semibold text-primary">Muat ulang</button></div>
          <div v-if="todayRows.length === 0" class="text-sm text-on-surface-variant">Belum ada pendaftar hari ini.</div>
          <div v-else class="space-y-1">
            <div v-for="r in todayRows" :key="r.id" class="p-2 bg-surface-container-lowest border border-outline-variant rounded-xl flex items-center justify-between gap-2">
              <div class="min-w-0"><div class="font-bold text-xs truncate">{{ r.registration_number || ('#'+r.id) }} — {{ r.name }}</div><div class="text-xs text-on-surface-variant">{{ jenjangLabel(r.jenjang) }} · {{ statusLabel(r.status) }} · {{ fmtDateTime(r.created_at) }}</div></div>
              <button @click="openDetail(r)" class="px-2 py-1 border border-outline-variant rounded-md text-xs font-bold bg-white">Detail</button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="tab === 'pendaftar'" class="space-y-4">
        <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-4 space-y-3">
          <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-2">
            <select v-model="filters.wave_id" class="input"><option value="">Semua gelombang</option><option v-for="w in stats.by_wave || []" :key="w.id" :value="String(w.id)">{{ w.name }}</option></select>
            <select v-model="filters.jenjang" class="input"><option value="">Semua jalur</option><option v-for="j in jalurKeys" :key="j.key" :value="j.key">{{ j.label }}</option></select>
            <select v-model="filters.status" class="input"><option value="">Semua status</option><option value="pending">Pending</option><option value="diterima">Diterima</option><option value="cadangan">Cadangan</option><option value="ditolak">Ditolak</option></select>
            <input v-model="filters.search" @keyup.enter="applyFilters" class="input" placeholder="Cari nama/NISN/no. daftar" />
            <input v-model="filters.date_from" type="date" class="input" />
            <input v-model="filters.date_to" type="date" class="input" />
          </div>
          <div class="flex flex-wrap gap-2">
            <button @click="applyFilters" class="btn btn--primary btn--sm">Terapkan Filter</button>
            <button @click="resetFilters" class="btn btn--sm">Reset</button>
            <button @click="downloadRecapPdf" class="btn btn--sm">Rekap PDF</button>
          </div>
        </div>
        <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-4">
          <div class="flex items-center justify-between gap-2 mb-3"><h3 class="font-bold text-sm">Daftar Pendaftar <span class="text-on-surface-variant font-normal">({{ rows.length }} ditampilkan)</span></h3></div>
          <div v-if="rows.length === 0" class="text-sm text-on-surface-variant">Tidak ada pendaftar pada filter ini.</div>
          <div v-else class="space-y-1">
            <div v-for="r in rows" :key="r.id" class="p-2 bg-surface-container-lowest border border-outline-variant rounded-xl flex flex-wrap items-center justify-between gap-2">
              <div class="min-w-0 flex-1"><div class="font-bold text-xs truncate">{{ r.registration_number || ('#'+r.id) }} — {{ r.name }} <span class="text-on-surface-variant">({{ r.nisn || '-' }})</span></div><div class="text-xs text-on-surface-variant truncate">{{ fmtDate(r.created_at) }} · {{ jenjangLabel(r.jenjang) }} · {{ r.gender || '-' }} · {{ statusLabel(r.status) }}</div></div>
              <div class="flex items-center gap-1 shrink-0 flex-wrap">
                <button @click="downloadRegistrationPdf(r)" class="px-2 py-1 border border-outline-variant rounded-md text-xs font-bold bg-white">PDF</button>
                <button @click="openDetail(r)" class="px-2 py-1 border border-outline-variant rounded-md text-xs font-bold bg-white">Detail</button>
                <select :value="r.status" @change="e => updateStatus(r, e.target.value)" class="px-2 py-1 border border-outline-variant rounded-md text-xs font-bold"><option value="pending">pending</option><option value="diterima">diterima</option><option value="ditolak">ditolak</option><option value="cadangan">cadangan</option></select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="tab === 'riwayat'" class="space-y-4">
        <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-4 flex flex-wrap gap-2 items-end">
          <div><label class="text-xs font-bold block mb-1">Dari tanggal</label><input v-model="historyDateFrom" type="date" class="input" /></div>
          <div><label class="text-xs font-bold block mb-1">Sampai tanggal</label><input v-model="historyDateTo" type="date" class="input" /></div>
          <button @click="loadHistory" class="btn btn--primary btn--sm">Tampilkan</button>
        </div>
        <div v-for="group in historyGroups" :key="group.date" class="bg-surface-container-low border border-outline-variant rounded-2xl p-4">
          <div class="flex items-center justify-between gap-2 mb-2"><h3 class="font-bold text-sm">{{ group.label }}</h3><span class="text-xs font-semibold text-primary">{{ group.items.length }} pendaftar</span></div>
          <div class="space-y-1">
            <div v-for="r in group.items" :key="r.id" class="p-2 bg-surface-container-lowest border border-outline-variant rounded-xl flex items-center justify-between gap-2">
              <div class="min-w-0"><div class="font-bold text-xs truncate">{{ r.registration_number || ('#'+r.id) }} — {{ r.name }}</div><div class="text-xs text-on-surface-variant">{{ jenjangLabel(r.jenjang) }} · {{ statusLabel(r.status) }}</div></div>
              <span class="text-xs text-on-surface-variant shrink-0">{{ fmtTime(r.created_at) }}</span>
            </div>
          </div>
        </div>
        <div v-if="historyGroups.length === 0" class="text-sm text-on-surface-variant">Tidak ada riwayat pada rentang tanggal ini.</div>
      </div>
    </template>

    <div v-if="detail" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="detail=null">
      <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[85vh] overflow-y-auto p-5">
        <div class="flex items-center justify-between mb-3"><h3 class="font-bold">Detail Pendaftar: {{ detail.name }}</h3><button @click="detail=null" class="text-sm font-bold">Tutup</button></div>
        <div class="grid md:grid-cols-3 gap-3 text-xs">
          <div class="md:col-span-1">
            <img v-if="photoUrl" :src="photoUrl" class="w-full max-w-[180px] rounded-xl border" alt="Foto" />
            <div v-else class="w-32 h-40 bg-surface-container rounded-xl flex items-center justify-center text-on-surface-variant">Tanpa foto</div>
            <div class="mt-2 space-y-1">
              <label class="text-xs font-bold block">Upload/Ganti Foto (admin)</label>
              <input ref="adminPhotoInput" type="file" accept=".jpg,.jpeg,.png,.webp" class="input" />
              <button @click="uploadAdminPhoto" :disabled="uploadingPhoto" class="btn btn--sm btn--primary">{{ uploadingPhoto ? 'Mengunggah…' : 'Unggah Foto' }}</button>
            </div>
          </div>
          <div class="md:col-span-2 grid grid-cols-2 gap-x-3 gap-y-1">
            <div><b>No. Pendaftaran:</b> {{ detail.registration_number||'-' }}</div><div><b>Status:</b> {{ detail.status }}</div>
            <div><b>Jalur:</b> {{ jenjangLabel(detail.jenjang) }}</div><div><b>Gelombang:</b> {{ detail.wave?.name||'-' }}</div>
            <div><b>NIK:</b> {{ detail.nik }}</div><div><b>NISN:</b> {{ detail.nisn||'-' }}</div>
            <div><b>JK:</b> {{ detail.gender }}</div><div><b>TTL:</b> {{ detail.birth_place }}, {{ detail.birth_date }}</div>
            <div class="col-span-2"><b>Alamat:</b> {{ detail.address }}, {{ detail.village }}, {{ detail.district }}, {{ detail.city }}, {{ detail.province }}</div>
            <div><b>HP:</b> {{ detail.phone||'-' }}</div><div><b>Email:</b> {{ detail.email||'-' }}</div>
            <div class="col-span-2"><b>Sekolah asal:</b> {{ detail.origin_school_name||'-' }}</div>
            <div><b>Ayah:</b> {{ detail.father_name }}</div><div><b>Ibu:</b> {{ detail.mother_name }}</div>
            <div><b>Pembiaya:</b> {{ detail.financier_name }} ({{ detail.financier_relation }})</div><div><b>Darurat:</b> {{ detail.emergency_name }} {{ detail.emergency_phone }}</div>
            <div><b>Gol. Darah:</b> {{ detail.blood_type||'-' }}</div><div><b>Riwayat:</b> {{ (detail.medical_history||[]).join(', ')||'-' }}</div>
            <div class="col-span-2"><b>Pernyataan:</b> {{ detail.declarant_name }} ({{ detail.declaration_date||'-' }})</div>
            <div class="col-span-2"><b>Catatan:</b> {{ detail.notes||'-' }}</div>
            <div class="col-span-2 pt-1"><button @click="downloadRegistrationPdf(detail)" class="btn btn--sm btn--primary">Download PDF Formulir</button></div>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import AppShell from '../../Components/AppShell.vue'
import { useAuth } from '../../composables/useAuth.js'

const { token } = useAuth()
const auth = () => token.value ? { Authorization: `Bearer ${token.value}`, Accept: 'application/json' } : { Accept: 'application/json' }

const tabs = [
  { key: 'ringkasan', label: 'Ringkasan' },
  { key: 'pendaftar', label: 'Pendaftar' },
  { key: 'riwayat', label: 'Riwayat per Tanggal' },
]
const jalurKeys = [
  { key: 'lanjut', label: 'Jenjang Lanjut' },
  { key: 'sma', label: 'Pendaftaran SMA' },
  { key: 'smp', label: 'Pendaftaran SMP' },
  { key: 'salafiyah', label: 'Salafiyah Santri Putri' },
]
const statusMap = { pending: 'Pending', diterima: 'Diterima', ditolak: 'Ditolak', cadangan: 'Cadangan' }

const tab = ref('ringkasan')
const loading = ref(true)
const error = ref('')
const stats = ref(null)
const rows = ref([])
const todayRows = ref([])
const detail = ref(null)
const photoUrl = ref('')
const adminPhotoInput = ref(null)
const uploadingPhoto = ref(false)
const historyDateFrom = ref(new Date().toISOString().slice(0, 10))
const historyDateTo = ref(new Date().toISOString().slice(0, 10))
const historyRows = ref([])

const filters = ref({ wave_id: '', jenjang: '', status: '', search: '', date_from: '', date_to: '' })

const jenjangLabel = (v) => jalurKeys.find((j) => j.key === v)?.label || v || '—'
const statusLabel = (v) => statusMap[v] || v || '—'
const fmtDate = (d) => d ? String(d).slice(0, 10) : '—'
const fmtDateTime = (d) => { try { return new Date(d).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) } catch { return d || '—' } }
const fmtTime = (d) => { try { return new Date(d).toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit' }) } catch { return '' } }

async function loadStats() {
  const r = await fetch('/api/ppdb-registrations/stats', { headers: auth() })
  if (!r.ok) throw new Error('Gagal memuat statistik PPDB')
  stats.value = await r.json()
}
async function loadRows(params = {}) {
  const q = new URLSearchParams()
  q.set('per_page', '100')
  Object.entries(params).forEach(([k, v]) => { if (v) q.set(k, String(v)) })
  const r = await fetch('/api/ppdb-registrations?' + q.toString(), { headers: auth() })
  if (!r.ok) throw new Error('Gagal memuat pendaftar')
  const j = await r.json()
  rows.value = j.data ?? j
}
async function loadToday() {
  const today = new Date().toISOString().slice(0, 10)
  const q = new URLSearchParams({ per_page: '100', date_from: today, date_to: today })
  const r = await fetch('/api/ppdb-registrations?' + q.toString(), { headers: auth() })
  if (r.ok) { const j = await r.json(); todayRows.value = j.data ?? j }
}
async function loadAll() {
  loading.value = true
  error.value = ''
  try {
    await Promise.all([loadStats(), loadRows(), loadToday(), loadHistory()])
  } catch (e) {
    error.value = e.message || 'Gagal memuat data'
  } finally {
    loading.value = false
  }
}
function applyFilters() { loadRows(filters.value) }
function resetFilters() { filters.value = { wave_id: '', jenjang: '', status: '', search: '', date_from: '', date_to: '' }; loadRows() }
function searchFromDashboard() { tab.value = 'pendaftar'; loadRows(filters.value) }
function clearDashboardSearch() { filters.search = ''; tab.value = 'pendaftar'; loadRows(filters.value) }
async function loadHistory() {
  const params = {}
  if (historyDateFrom.value) params.date_from = historyDateFrom.value
  if (historyDateTo.value) params.date_to = historyDateTo.value
  const q = new URLSearchParams({ per_page: '100', ...params })
  const r = await fetch('/api/ppdb-registrations?' + q.toString(), { headers: auth() })
  if (r.ok) { const j = await r.json(); historyRows.value = j.data ?? j }
}
const historyGroups = computed(() => {
  const map = new Map()
  for (const row of historyRows.value) {
    const d = fmtDate(row.created_at)
    if (!map.has(d)) map.set(d, [])
    map.get(d).push(row)
  }
  return [...map.entries()].sort((a, b) => b[0].localeCompare(a[0])).map(([date, items]) => ({ date, label: date, items }))
})
async function openDetail(r) {
  const res = await fetch('/api/ppdb-registrations/' + r.id, { headers: auth() })
  if (!res.ok) return
  detail.value = await res.json()
  photoUrl.value = ''
  if (detail.value.photo_path) {
    const pr = await fetch('/api/ppdb-registrations/' + r.id + '/photo', { headers: auth() })
    if (pr.ok) photoUrl.value = URL.createObjectURL(await pr.blob())
  }
}
async function updateStatus(r, status) {
  const res = await fetch('/api/ppdb-registrations/' + r.id, { method: 'PUT', headers: { 'Content-Type': 'application/json', ...auth() }, body: JSON.stringify({ status }) })
  if (res.ok) { await Promise.all([loadStats(), loadRows(filters.value), loadToday(), loadHistory()]); if (detail.value?.id === r.id) detail.value = await res.json() }
}
async function downloadBlob(path, fallbackName) {
  const r = await fetch(path, { headers: auth() })
  if (!r.ok) { const j = await r.json().catch(() => ({})); alert(j.message || j.report || 'Gagal mengunduh PDF'); return }
  const blob = await r.blob()
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  const disposition = r.headers.get('content-disposition') || ''
  const match = disposition.match(/filename="?([^";]+)"?/i)
  a.href = url
  a.download = match?.[1] || fallbackName
  document.body.appendChild(a)
  a.click()
  a.remove()
  URL.revokeObjectURL(url)
}
function downloadRegistrationPdf(r) { downloadBlob('/api/ppdb-registrations/' + r.id + '/pdf', 'formulir-ppdb.pdf') }
function downloadRecapPdf() {
  const q = new URLSearchParams()
  Object.entries(filters.value).forEach(([k, v]) => { if (v) q.set(k, String(v)) })
  downloadBlob('/api/ppdb-registrations/pdf?' + q.toString(), 'rekap-ppdb.pdf')
}
async function uploadAdminPhoto() {
  if (!detail.value) return
  const file = adminPhotoInput.value?.files?.[0]
  if (!file) { alert('Pilih file foto terlebih dahulu.'); return }
  uploadingPhoto.value = true
  try {
    const fd = new FormData()
    fd.append('photo', file)
    const r = await fetch('/api/ppdb-registrations/' + detail.value.id + '/photo', { method: 'POST', headers: auth(), body: fd })
    const j = await r.json().catch(() => ({}))
    if (!r.ok) throw new Error(j.message || (j.errors ? Object.values(j.errors).flat()[0] : 'Gagal mengunggah foto'))
    detail.value = j
    const pr = await fetch('/api/ppdb-registrations/' + detail.value.id + '/photo', { headers: auth() })
    if (pr.ok) photoUrl.value = URL.createObjectURL(await pr.blob())
    if (adminPhotoInput.value) adminPhotoInput.value.value = ''
    await loadRows(filters.value)
  } catch (e) {
    alert(e.message)
  } finally {
    uploadingPhoto.value = false
  }
}

onMounted(loadAll)
</script>

<style scoped>
.input { width: 100%; padding: 8px 10px; border: 1px solid var(--color-outline-variant); border-radius: 10px; background: var(--color-surface-container-lowest); font-size: 12px; }
.btn { padding: 8px 12px; border: 1px solid var(--color-outline-variant); border-radius: 10px; background: var(--color-surface-container-lowest); font-size: 12px; font-weight: 700; }
.btn--sm { padding: 6px 10px; font-size: 11px; }
.btn--primary { background: var(--color-primary); color: var(--color-on-primary); border-color: var(--color-primary); }
</style>
