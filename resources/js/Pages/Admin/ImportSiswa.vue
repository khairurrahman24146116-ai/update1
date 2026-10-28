<template>
  <AppShell title="Import Siswa">
    <BackButton fallback="/app/admin" label="Kembali" />

    <div class="bg-white border border-outline-variant rounded-xl p-5 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <label class="space-y-1">
          <span class="text-xs text-on-surface-variant">Tahun Ajaran</span>
          <select v-model="yearId" class="input" @change="loadPreviewIfAny">
            <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}<template v-if="y.is_active"> (aktif)</template></option>
          </select>
        </label>
        <label class="flex items-end gap-2 pb-2">
          <input v-model="updateExisting" type="checkbox" class="h-4 w-4" />
          <span class="text-sm">Update data yang sudah ada (tidak mengubah kelas)</span>
        </label>
        <div class="flex items-end pb-2">
          <button @click="downloadTemplate" :disabled="!yearId" class="btn btn--ghost btn--sm disabled:opacity-50">
            <span class="material-symbols-outlined !w-4 !h-4">download</span>
            Unduh Template
          </button>
        </div>
      </div>

      <div class="text-xs bg-surface-container-low border border-outline-variant rounded-xl p-3">
        Header wajib: <span class="font-bold">NIS / NISN / Nama / Jenis Kelamin / Kelas</span> (urutan bebas).
        NISN yang kosong menjadi peringatan. Kelas mengikuti master tahun ajaran terpilih.
        <span class="font-bold text-error">Jika ada satu baris error, seluruh file ditolak.</span>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <input type="file" accept=".xlsx,.csv" @change="onFile" class="text-sm" />
        <button @click="doPreview" :disabled="!file || !yearId || busy" class="btn btn--primary btn--sm disabled:opacity-50">
          {{ busy ? 'Memproses...' : 'Preview & Validasi' }}
        </button>
        <span v-if="fileName" class="text-xs text-on-surface-variant">{{ fileName }}</span>
      </div>

      <p v-if="error" class="text-error text-sm">{{ error }}</p>
    </div>

    <div v-if="preview" class="bg-white border border-outline-variant rounded-xl shadow-sm p-5 space-y-4">
      <div class="flex flex-wrap items-center gap-4 text-sm font-bold">
        <span>Total: {{ preview.summary.total }}</span>
        <span class="text-green-700">Valid: {{ preview.summary.valid }}</span>
        <span class="text-amber-600">Peringatan: {{ preview.summary.peringatan }}</span>
        <span class="text-error">Error: {{ preview.summary.error }}</span>
      </div>
      <div class="flex flex-wrap gap-2">
        <button @click="filterStatus = ''; loadBatch()" :class="filterStatus === '' ? 'btn btn--primary btn--sm' : 'btn btn--ghost btn--sm'">Semua</button>
        <button @click="filterStatus = 'valid'; loadBatch()" :class="filterStatus === 'valid' ? 'btn btn--primary btn--sm' : 'btn btn--ghost btn--sm'">Valid</button>
        <button @click="filterStatus = 'peringatan'; loadBatch()" :class="filterStatus === 'peringatan' ? 'btn btn--primary btn--sm' : 'btn btn--ghost btn--sm'">Peringatan</button>
        <button @click="filterStatus = 'error'; loadBatch()" :class="filterStatus === 'error' ? 'btn btn--primary btn--sm' : 'btn btn--ghost btn--sm'">Error</button>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead class="bg-primary text-on-primary">
            <tr>
              <th class="p-2 text-left">Baris</th>
              <th class="p-2 text-left">NIS</th>
              <th class="p-2 text-left">NISN</th>
              <th class="p-2 text-left">Nama</th>
              <th class="p-2 text-left">Kelas</th>
              <th class="p-2 text-left">Unit</th>
              <th class="p-2 text-left">Aksi</th>
              <th class="p-2 text-left">Status</th>
              <th class="p-2 text-left">Catatan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.id" class="border-t border-outline-variant align-top">
              <td class="p-2">{{ r.nomor_baris }}</td>
              <td class="p-2">{{ r.data_valid?.nis }}</td>
              <td class="p-2">{{ r.data_valid?.nisn }}</td>
              <td class="p-2">{{ r.data_valid?.nama }}</td>
              <td class="p-2">{{ r.data_valid?.kelas }}</td>
              <td class="p-2">{{ r.data_valid?.unit || '-' }}</td>
              <td class="p-2">{{ labelAksi(r.aksi) }}</td>
              <td class="p-2 font-bold" :class="statusClass(r.status)">{{ r.status }}</td>
              <td class="p-2">
                <span v-for="(p, i) in r.pesan || []" :key="i" :class="p.tingkat === 'error' ? 'text-error' : 'text-amber-600'">
                  {{ p.pesan }}<br />
                </span>
              </td>
            </tr>
          </tbody>
        </table>
        <EmptyState v-if="!rows.length" icon="table_view" title="Tidak ada baris" description="Tidak ada baris untuk filter ini." />
      </div>
      <div v-if="meta" class="flex items-center justify-between text-xs">
        <span class="text-on-surface-variant">Total {{ meta.total }} — Hal {{ meta.current_page }}/{{ meta.last_page }}</span>
        <div class="flex gap-2">
          <button @click="goPage(meta.current_page - 1)" :disabled="meta.current_page <= 1" class="btn btn--ghost btn--sm disabled:opacity-40">Prev</button>
          <button @click="goPage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page" class="btn btn--ghost btn--sm disabled:opacity-40">Next</button>
        </div>
      </div>
      <div v-if="hasBlockingErrors" class="text-xs bg-red-50 border border-red-200 text-red-700 rounded-xl p-3">
        Ada {{ preview.summary.error }} baris error. Import tidak bisa dilanjutkan.
        Perbaiki file lalu upload ulang; tidak ada data yang akan disimpan.
      </div>
      <div class="flex flex-wrap gap-2 pt-2 border-t border-outline-variant">
        <button @click="doConfirm" :disabled="busy || hasBlockingErrors" class="btn btn--primary btn--sm disabled:opacity-50">
          Konfirmasi Import
        </button>
        <button @click="downloadErrors" class="btn btn--ghost btn--sm">Unduh Error (CSV)</button>
        <button @click="doCancel" class="btn btn--ghost btn--sm text-error">Batalkan</button>
      </div>
    </div>

    <div v-if="result" class="bg-white border border-outline-variant rounded-xl shadow-sm p-5 text-sm space-y-2">
      <div class="font-extrabold">Hasil Import</div>
      <div>Berhasil: {{ result.berhasil }} | Gagal: {{ result.gagal }} | Peringatan: {{ result.peringatan }}</div>
      <button @click="downloadErrors" class="underline text-primary text-xs">Unduh laporan error</button>
    </div>

    <div class="bg-white border border-outline-variant rounded-xl shadow-sm p-5 space-y-3">
      <div class="font-bold text-sm">Riwayat Import</div>
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead class="bg-surface-container-low">
            <tr>
              <th class="p-2 text-left">File</th>
              <th class="p-2 text-left">Tahun</th>
              <th class="p-2 text-left">Oleh</th>
              <th class="p-2 text-left">Status</th>
              <th class="p-2 text-left">Berhasil</th>
              <th class="p-2 text-left">Gagal</th>
              <th class="p-2 text-left">Waktu</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in history" :key="b.id" class="border-t border-outline-variant">
              <td class="p-2">{{ b.file_name }}</td>
              <td class="p-2">{{ b.academic_year?.name }}</td>
              <td class="p-2">{{ b.importer?.name }}</td>
              <td class="p-2">{{ b.status }}</td>
              <td class="p-2">{{ b.berhasil }}</td>
              <td class="p-2">{{ b.gagal }}</td>
              <td class="p-2">{{ b.created_at }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import BackButton from '../../Components/BackButton.vue'
import AppShell from '../../Components/AppShell.vue'
import EmptyState from '../../Components/EmptyState.vue'

const years = ref([])
const yearId = ref(null)
const updateExisting = ref(false)
const file = ref(null)
const fileName = ref('')
const preview = ref(null)
const rows = ref([])
const meta = ref(null)
const filterStatus = ref('')
const page = ref(1)
const batchId = ref(null)
const result = ref(null)
const history = ref([])
const busy = ref(false)
const error = ref('')
const hasBlockingErrors = computed(() => Number(preview.value?.summary?.error || 0) > 0)

async function parseError(res) {
  try {
    const j = await res.json()
    if (j.errors) return Object.values(j.errors).flat().join('; ')
    return j.message || `Terjadi kesalahan (${res.status})`
  } catch {
    return `Terjadi kesalahan (${res.status})`
  }
}

async function loadOptions() {
  const r = await fetch('/api/import-siswa/options')
  if (!r.ok) { error.value = await parseError(r); return }
  const j = await r.json()
  years.value = j.years || []
  yearId.value = j.active_year_id || years.value[0]?.id || null
}

async function loadHistory() {
  const r = await fetch('/api/import-siswa/batches?per_page=5')
  if (!r.ok) return
  const j = await r.json()
  history.value = j.data || []
}

function loadPreviewIfAny() {
  preview.value = null
  rows.value = []
  meta.value = null
  result.value = null
}

function onFile(e) {
  file.value = e.target.files[0]
  fileName.value = file.value?.name || ''
  preview.value = null
  rows.value = []
  meta.value = null
  result.value = null
  error.value = ''
}

async function downloadTemplate() {
  error.value = ''
  const r = await fetch(`/api/import-siswa/template?tahun_ajaran_id=${yearId.value}`)
  if (!r.ok) { error.value = await parseError(r); return }
  const blob = await r.blob()
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = 'template_import_siswa.xlsx'
  a.click()
  URL.revokeObjectURL(a.href)
}

async function doPreview() {
  if (!file.value || !yearId.value) return
  busy.value = true
  error.value = ''
  result.value = null
  try {
    const fd = new FormData()
    fd.append('file', file.value)
    fd.append('tahun_ajaran_id', yearId.value)
    fd.append('update_existing', updateExisting.value ? '1' : '0')
    const r = await fetch('/api/import-siswa/preview', { method: 'POST', body: fd })
    if (!r.ok) { error.value = await parseError(r); return }
    const j = await r.json()
    batchId.value = j.batch.id
    preview.value = j
    rows.value = j.rows.data
    meta.value = j.rows
    page.value = 1
    await loadHistory()
  } finally {
    busy.value = false
  }
}

async function loadBatch() {
  if (!batchId.value) return
  const q = new URLSearchParams({ page: String(page.value) })
  if (filterStatus.value) q.set('status', filterStatus.value)
  const r = await fetch(`/api/import-siswa/batches/${batchId.value}?${q}`)
  if (!r.ok) { error.value = await parseError(r); return }
  const j = await r.json()
  preview.value = j
  rows.value = j.rows.data
  meta.value = j.rows
}

function goPage(p) {
  page.value = Math.max(1, p)
  loadBatch()
}

async function doConfirm() {
  if (!batchId.value) return
  if (hasBlockingErrors.value) {
    error.value = 'Perbaiki semua baris error terlebih dahulu. Import tidak akan menyimpan data apa pun.'
    return
  }
  busy.value = true
  error.value = ''
  try {
    const r = await fetch(`/api/import-siswa/batches/${batchId.value}/confirm`, { method: 'POST' })
    if (!r.ok) { error.value = await parseError(r); return }
    const j = await r.json()
    result.value = j.batch || null
    preview.value = null
    rows.value = []
    meta.value = null
    await loadHistory()
  } finally {
    busy.value = false
  }
}

async function downloadErrors() {
  if (!batchId.value) return
  const r = await fetch(`/api/import-siswa/batches/${batchId.value}/errors`)
  if (!r.ok) { error.value = await parseError(r); return }
  const blob = await r.blob()
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = `error_import_siswa_${batchId.value}.csv`
  a.click()
  URL.revokeObjectURL(a.href)
}

async function doCancel() {
  if (!batchId.value) return
  const r = await fetch(`/api/import-siswa/batches/${batchId.value}`, { method: 'DELETE' })
  if (!r.ok) { error.value = await parseError(r); return }
  preview.value = null
  rows.value = []
  meta.value = null
  batchId.value = null
  await loadHistory()
}

function statusClass(status) {
  return status === 'valid' ? 'text-green-700' : status === 'error' ? 'text-error' : 'text-amber-600'
}

function labelAksi(aksi) {
  if (aksi === 'create') return 'Tambah'
  if (aksi === 'update') return 'Update'
  return '—'
}

onMounted(async () => {
  await loadOptions()
  await loadHistory()
})
</script>
