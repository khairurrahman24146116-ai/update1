<template>
  <AppShell title="Mata Pelajaran">
    <div class="space-y-4">
      <BackButton fallback="/app/admin" />

      <div class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <p class="font-body-sm text-body-sm text-on-surface-variant">Master data mapel formal dan non formal per unit/jenjang.</p>
          <button @click="openCreate" class="btn btn--primary btn--sm">+ Tambah Mapel</button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            v-for="tab in jenisTabs"
            :key="tab.value"
            type="button"
            class="btn btn--sm"
            :class="filterJenis === tab.value ? 'btn--primary' : 'btn--ghost'"
            @click="filterJenis = tab.value; goPage(1)"
          >
            {{ tab.label }}
          </button>
        </div>

        <div class="flex flex-wrap gap-2">
          <input v-model="search" @input="onSearch" placeholder="Cari nama / kode" class="input flex-1 min-w-44 max-w-sm" />
          <select v-model="filterUnit" @change="goPage(1)" class="input w-32">
            <option value="">Semua Unit</option>
            <option v-for="unit in enabledUnits" :key="unit.value" :value="unit.value">{{ unit.label }}</option>
          </select>
          <select v-model="filterJenjang" @change="goPage(1)" class="input w-40">
            <option value="">Semua Jenjang</option>
            <option v-for="jenjang in jenjangOptions" :key="jenjang" :value="jenjang">{{ jenjang }}</option>
          </select>
          <select v-model="filterStatus" @change="goPage(1)" class="input w-36">
            <option value="">Semua Status</option>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
          </select>
        </div>
        <p v-if="error" class="text-error text-xs">{{ error }}</p>
        <p v-if="notice" class="text-primary text-xs">{{ notice }}</p>
      </div>

      <div v-if="showForm" class="bg-surface-container-low border border-outline-variant rounded-xl p-4 space-y-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="font-bold">{{ editingId ? 'Edit Mapel' : 'Tambah Mapel' }}</h3>
            <p class="text-xs text-on-surface-variant mt-1">Satu mapel bisa dipakai di beberapa unit/jenjang.</p>
          </div>
          <button type="button" class="btn btn--ghost btn--sm" @click="closeForm">Batal</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <label class="space-y-1 text-xs font-bold">Nama mapel
            <input v-model="form.name" class="input" placeholder="Contoh: Matematika" />
          </label>
          <label class="space-y-1 text-xs font-bold">Kode (opsional)
            <input v-model="form.code" class="input" placeholder="Contoh: MTK" />
          </label>
          <label class="space-y-1 text-xs font-bold">Jenis
            <select v-model="form.jenis" class="input">
              <option value="formal">Formal</option>
              <option value="non_formal">Non Formal</option>
            </select>
          </label>
          <label class="flex items-center gap-2 text-sm pt-6">
            <input v-model="form.is_active" type="checkbox" /> Mapel aktif
          </label>
        </div>

        <div class="space-y-2">
          <p class="text-xs font-bold">Unit & Jenjang</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            <label v-for="option in placementOptions" :key="option.key" class="flex items-center gap-2 rounded-lg border border-outline-variant bg-white px-3 py-2 text-sm">
              <input v-model="form.placements" type="checkbox" :value="option.key" />
              {{ option.label }}
            </label>
          </div>
          <p v-if="!placementOptions.length" class="text-xs text-on-surface-variant">Tidak ada unit aktif pada konfigurasi.</p>
        </div>

        <p v-if="formError" class="text-error text-xs">{{ formError }}</p>
        <button type="button" class="btn btn--primary btn--sm" :disabled="saving" @click="submit">
          {{ saving ? 'Menyimpan...' : 'Simpan Mapel' }}
        </button>
      </div>

      <div class="bg-white p-4 rounded-xl border border-outline-variant shadow-sm overflow-x-auto">
        <div v-if="loading" class="p-6 text-center text-sm text-on-surface-variant">Memuat...</div>
        <table v-else class="w-full text-sm">
          <thead class="bg-primary text-on-primary">
            <tr>
              <th class="p-2 text-left">Nama</th>
              <th class="p-2 text-left">Jenis</th>
              <th class="p-2 text-left">Unit / Jenjang</th>
              <th class="p-2 text-left">Status</th>
              <th class="p-2 text-left">Pemakaian</th>
              <th class="p-2 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="subject in list" :key="subject.id" class="border-t border-outline-variant">
              <td class="p-2">
                <p class="font-bold">{{ subject.name }}</p>
                <p class="text-xs text-on-surface-variant">{{ subject.code || 'Tanpa kode' }}</p>
              </td>
              <td class="p-2">
                <span class="badge" :class="jenisClass(subject.jenis)">{{ subject.jenis_label }}</span>
              </td>
              <td class="p-2">
                <div class="flex flex-wrap gap-1">
                  <span v-for="scope in visibleScopes(subject)" :key="`${scope.unit}:${scope.jenjang}`" class="badge bg-surface-container-high text-on-surface">{{ scope.label }}</span>
                  <span v-if="!visibleScopes(subject).length" class="text-xs text-on-surface-variant">Belum diatur</span>
                </div>
              </td>
              <td class="p-2">
                <span class="badge" :class="subject.is_active ? 'bg-primary-fixed text-on-surface' : 'bg-surface-container-high text-on-surface-variant'">
                  {{ subject.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td class="p-2 text-xs text-on-surface-variant">
                <span v-if="subject.usage.total">{{ subject.usage.total }} data</span>
                <span v-else>-</span>
              </td>
              <td class="p-2 text-right whitespace-nowrap">
                <button @click="openEdit(subject)" class="text-xs underline">Edit</button>
                <button @click="toggleActive(subject)" class="text-xs underline ml-2">{{ subject.is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                <button @click="remove(subject)" class="text-xs underline text-error ml-2">Hapus</button>
              </td>
            </tr>
          </tbody>
        </table>
        <EmptyState v-if="!loading && !list.length" icon="menu_book" title="Tidak ada data" description="Belum ada mata pelajaran yang cocok dengan filter saat ini." />
        <div v-if="meta" class="flex flex-wrap items-center justify-between gap-2 mt-3 pt-3 border-t text-xs">
          <span class="text-on-surface-variant">Total {{ meta.total }} • Hal {{ meta.current_page }} / {{ meta.last_page }}</span>
          <div class="flex gap-2">
            <button @click="goPage(meta.current_page - 1)" :disabled="meta.current_page <= 1" class="btn btn--ghost btn--sm disabled:opacity-40">Prev</button>
            <button @click="goPage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page" class="btn btn--ghost btn--sm disabled:opacity-40">Next</button>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import BackButton from '../../Components/BackButton.vue'
import EmptyState from '../../Components/EmptyState.vue'
import AppShell from '../../Components/AppShell.vue'
import { useAuth } from '../../composables/useAuth.js'
import { enabledUnitCodes } from '../../units.js'

const { user } = useAuth()
const search = ref('')
const filterJenis = ref('')
const filterUnit = ref('')
const filterJenjang = ref('')
const filterStatus = ref('')
const list = ref([])
const meta = ref(null)
const page = ref(1)
const loading = ref(false)
const error = ref('')
const notice = ref('')
const showForm = ref(false)
const formError = ref('')
const editingId = ref(null)
const saving = ref(false)
let searchTimer = null

const jenisTabs = [
  { value: '', label: 'Semua' },
  { value: 'formal', label: 'Formal' },
  { value: 'non_formal', label: 'Non Formal' },
]

const unitCatalog = [
  { value: 'lpi', label: 'LPI', jenjang: ['Tsanawiyah', 'Aliyah'] },
  { value: 'sma', label: 'SMA', jenjang: ['SMA'] },
  { value: 'smp', label: 'SMP', jenjang: ['SMP'] },
]

const enabledCodes = computed(() => enabledUnitCodes(user.value))
const enabledUnits = computed(() => unitCatalog.filter((unit) => enabledCodes.value.includes(unit.value)))
const placementOptions = computed(() => enabledUnits.value.flatMap((unit) => unit.jenjang.map((jenjang) => ({
  key: `${unit.value}|${jenjang}`,
  unit: unit.value,
  jenjang,
  label: unit.value === 'sma' ? 'SMA' : `${unit.label} - ${jenjang}`,
}))))
const jenjangOptions = computed(() => [...new Set(placementOptions.value.map((option) => option.jenjang))])

const form = ref(emptyForm())

function emptyForm() {
  return { name: '', code: '', jenis: 'formal', is_active: true, placements: [] }
}

function jenisClass(jenis) {
  return jenis === 'non_formal' ? 'bg-tertiary-fixed text-on-surface' : 'bg-primary-fixed text-on-surface'
}

function visibleScopes(subject) {
  return (subject.scopes || []).filter((scope) => enabledCodes.value.includes(String(scope.unit).toLowerCase()))
}

function onSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => goPage(1), 350)
}

function goPage(target) {
  page.value = Math.max(1, target)
  load()
}

async function load() {
  loading.value = true
  error.value = ''

  try {
    const query = new URLSearchParams()
    if (search.value) query.set('search', search.value)
    if (filterJenis.value) query.set('jenis', filterJenis.value)
    if (filterUnit.value) query.set('unit', filterUnit.value)
    if (filterJenjang.value) query.set('jenjang', filterJenjang.value)
    if (filterStatus.value) query.set('status', filterStatus.value)
    query.set('page', String(page.value))
    query.set('per_page', '15')

    const response = await fetch(`/api/subjects?${query}`)
    const payload = await response.json().catch(() => ({}))
    if (!response.ok) throw new Error(payload.message || 'Gagal memuat mapel.')
    list.value = payload.data ?? payload
    meta.value = payload.meta ?? null
  } catch (loadError) {
    error.value = loadError.message
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editingId.value = null
  form.value = emptyForm()
  formError.value = ''
  notice.value = ''
  showForm.value = true
}

function openEdit(subject) {
  editingId.value = subject.id
  form.value = {
    name: subject.name,
    code: subject.code || '',
    jenis: subject.jenis,
    is_active: Boolean(subject.is_active),
    placements: visibleScopes(subject).map((scope) => `${scope.unit}|${scope.jenjang}`),
  }
  formError.value = ''
  notice.value = ''
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editingId.value = null
  formError.value = ''
}

async function submit() {
  formError.value = ''
  notice.value = ''

  if (!form.value.name.trim()) {
    formError.value = 'Nama mapel wajib diisi.'
    return
  }

  if (!form.value.placements.length) {
    formError.value = 'Pilih minimal satu unit dan jenjang.'
    return
  }

  const placements = form.value.placements.map((key) => {
    const [unit, jenjang] = key.split('|')
    return { unit, jenjang }
  })
  const payload = {
    name: form.value.name.trim(),
    code: form.value.code.trim() || null,
    jenis: form.value.jenis,
    is_active: form.value.is_active,
    placements,
  }
  const url = editingId.value ? `/api/subjects/${editingId.value}` : '/api/subjects'
  const method = editingId.value ? 'PUT' : 'POST'

  saving.value = true

  try {
    const response = await fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
    const body = await response.json().catch(() => ({}))
    if (!response.ok) {
      formError.value = body.message || Object.values(body.errors ?? {}).flat().join(', ') || 'Gagal menyimpan mapel.'
      return
    }
    showForm.value = false
    editingId.value = null
    notice.value = 'Mapel berhasil disimpan.'
    await load()
  } finally {
    saving.value = false
  }
}

async function toggleActive(subject) {
  error.value = ''
  notice.value = ''

  const response = await fetch(`/api/subjects/${subject.id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ is_active: !subject.is_active }),
  })
  const body = await response.json().catch(() => ({}))

  if (!response.ok) {
    error.value = body.message || 'Gagal mengubah status mapel.'
    return
  }

  notice.value = `Mapel ${subject.name} ${subject.is_active ? 'dinonaktifkan' : 'diaktifkan'}.`
  await load()
}

async function remove(subject) {
  error.value = ''
  notice.value = ''

  if (!confirm(`Hapus mapel ${subject.name}? Mapel yang sudah dipakai tidak akan dihapus.`)) return

  const response = await fetch(`/api/subjects/${subject.id}`, { method: 'DELETE' })
  const body = await response.json().catch(() => ({}))

  if (!response.ok) {
    error.value = body.message || 'Gagal menghapus mapel.'
    return
  }

  notice.value = `Mapel ${subject.name} dihapus.`
  await load()
}

onMounted(load)
</script>
