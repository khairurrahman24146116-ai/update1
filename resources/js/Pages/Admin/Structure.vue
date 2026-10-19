<template>
<AppShell title="Kelola Struktur">
  <BackButton fallback="/app/admin" />

  <div class="relative bg-white p-6 md:p-8 rounded-xl border border-outline-variant shadow-md flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4">
    <div class="space-y-2 max-w-3xl">
      <p class="text-sm text-on-surface-variant">Susun struktur organisasi tiap unit (LPI, SMA, SMP). Atur atasan (parent), urutan, jabatan, foto, serta status aktif dan konfirmasi data. Maksimal tiga tingkat di bawah puncak.</p>
    </div>
    <div class="flex flex-wrap gap-3">
      <router-link :to="previewPath" target="_blank" class="btn btn--ghost"><span class="material-symbols-outlined !w-4 !h-4">open_in_new</span> Lihat Bagan</router-link>
      <button @click="openCreate" class="btn btn--primary"><span class="material-symbols-outlined !w-4 !h-4">add</span> Tambah Anggota</button>
    </div>
  </div>

  <div class="flex flex-wrap items-center gap-2 border-b border-outline-variant" role="tablist" aria-label="Pilih unit struktur">
    <button
      v-for="u in units"
      :key="u.key"
      type="button"
      role="tab"
      :aria-selected="activeUnit === u.key"
      class="px-4 py-2 -mb-px border-b-2 font-title-md text-title-md transition-colors"
      :class="activeUnit === u.key ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface'"
      @click="switchUnit(u.key)"
    >{{ u.label }}</button>
  </div>

  <section v-if="editing" class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
    <div class="bg-surface-container-low border-b border-outline-variant px-4 py-3 flex items-center gap-2">
      <span class="w-8 h-8 rounded-full bg-primary text-on-primary flex items-center justify-center"><span class="material-symbols-outlined !w-5 !h-5">{{ form.id ? 'edit' : 'person_add' }}</span></span>
      <h2 class="font-bold text-sm">{{ form.id ? 'Ubah Anggota' : 'Tambah Anggota' }} — {{ unitLabel(form.unit) }}</h2>
    </div>
    <div class="p-4 md:p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="space-y-1.5 md:col-span-3 flex flex-col sm:flex-row gap-4 items-start">
        <div class="flex flex-col items-center gap-2 p-3 bg-surface border border-dashed border-outline-variant rounded-xl shrink-0">
          <div class="w-24 h-24 rounded-lg bg-white border border-outline-variant overflow-hidden flex items-center justify-center">
            <img v-if="photoPreview" :src="photoPreview" alt="Pratinjau foto" class="w-full h-full object-cover" @error="photoPreview = ''" />
            <span v-else class="material-symbols-outlined text-outline">person</span>
          </div>
          <label class="btn btn--ghost btn--sm cursor-pointer">
            <span class="material-symbols-outlined !w-4 !h-4">upload</span>
            {{ uploading ? 'Mengunggah…' : 'Unggah Foto' }}
            <input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" :disabled="uploading" @change="uploadPhoto" />
          </label>
          <button v-if="form.photo_path" type="button" @click="clearPhoto" class="text-xs text-error">Hapus foto</button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1 w-full">
          <div class="space-y-1.5"><label class="block text-xs font-bold">Nama lengkap</label><input v-model="form.name" class="input" placeholder="Kosongkan bila belum diketahui" /></div>
          <div class="space-y-1.5"><label class="block text-xs font-bold">Jabatan *</label><input v-model="form.position" class="input" placeholder="mis. Kepala Sekolah" /></div>
          <div class="space-y-1.5">
            <label class="block text-xs font-bold">Atasan (parent)</label>
            <select v-model="form.parent_id" class="input">
              <option :value="null">— Tidak ada (tingkat tertinggi)</option>
              <option v-for="p in parentOptions" :key="p.id" :value="p.id">{{ p.label }}</option>
            </select>
          </div>
          <div class="space-y-1.5"><label class="block text-xs font-bold">Urutan tampil</label><input v-model.number="form.sort_order" type="number" min="0" class="input" /></div>
          <div class="sm:col-span-2 flex flex-wrap gap-4 items-center">
            <label class="flex items-center gap-2 text-xs"><input v-model="form.is_confirmed" type="checkbox" /> Data terkonfirmasi</label>
            <label class="flex items-center gap-2 text-xs"><input v-model="form.is_active" type="checkbox" /> Tampilkan di halaman publik</label>
          </div>
        </div>
      </div>
      <div class="md:col-span-3 flex gap-2">
        <button @click="save" :disabled="saving" class="btn btn--primary disabled:opacity-50">{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
        <button @click="cancel" class="btn btn--ghost">Batal</button>
      </div>
    </div>
  </section>

  <section class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
    <div class="bg-surface-container-low border-b border-outline-variant px-4 py-3 flex items-center justify-between">
      <h2 class="font-bold text-sm">Daftar Anggota {{ unitLabel(activeUnit) }} ({{ members.length }})</h2>
      <span class="text-xs text-on-surface-variant">Urutan &amp; hierarki memengaruhi tampilan bagan</span>
    </div>

    <p v-if="loading" class="p-6 text-sm text-on-surface-variant">Memuat…</p>
    <p v-else-if="!members.length" class="p-6 text-sm text-on-surface-variant">Belum ada anggota pada unit ini. Tambahkan anggota pertama.</p>

    <ul v-else class="divide-y divide-outline-variant">
      <li v-for="row in treeRows" :key="row.member.id" class="p-4 flex items-center gap-3" :style="{ paddingLeft: (16 + row.depth * 20) + 'px' }">
        <span class="material-symbols-outlined text-outline" aria-hidden="true">{{ row.depth === 0 ? 'account_tree' : 'subdirectory_arrow_right' }}</span>
        <div class="w-12 h-12 rounded-lg bg-surface-container-low border border-outline-variant overflow-hidden flex items-center justify-center shrink-0">
          <img v-if="resolvePhoto(row.member.photo_path)" :src="resolvePhoto(row.member.photo_path)" alt="" class="w-full h-full object-cover" @error="$event.target.style.display='none'" />
          <span v-else class="material-symbols-outlined text-outline">person</span>
        </div>
        <div class="min-w-0 flex-1">
          <div class="font-bold text-sm truncate">{{ row.member.name || '— Belum diisi —' }}</div>
          <div class="text-xs text-on-surface-variant truncate">{{ row.member.position }}<span v-if="parentName(row.member.parent_id)"> • atasan: {{ parentName(row.member.parent_id) }}</span></div>
        </div>
        <div class="hidden sm:flex flex-col items-end gap-1 shrink-0">
          <span class="badge" :class="row.member.is_confirmed ? 'bg-tertiary-fixed' : 'bg-error-container'">{{ row.member.is_confirmed ? 'Terkonfirmasi' : 'Perlu konfirmasi' }}</span>
          <span class="badge" :class="row.member.is_active ? 'bg-primary-fixed' : 'bg-surface-container-high'">{{ row.member.is_active ? 'Tampil' : 'Disembunyikan' }}</span>
        </div>
        <div class="flex items-center gap-1 shrink-0">
          <button @click="move(row.member, -1)" :disabled="!canMove(row.member, -1)" class="w-8 h-8 rounded-full border border-outline-variant bg-white disabled:opacity-40" aria-label="Naikkan urutan"><span class="material-symbols-outlined !w-4 !h-4">arrow_upward</span></button>
          <button @click="move(row.member, 1)" :disabled="!canMove(row.member, 1)" class="w-8 h-8 rounded-full border border-outline-variant bg-white disabled:opacity-40" aria-label="Turunkan urutan"><span class="material-symbols-outlined !w-4 !h-4">arrow_downward</span></button>
          <button @click="openEdit(row.member)" class="w-8 h-8 rounded-full border border-outline-variant bg-white" aria-label="Ubah"><span class="material-symbols-outlined !w-4 !h-4">edit</span></button>
          <button @click="remove(row.member)" class="w-8 h-8 rounded-full border border-error text-error bg-white" aria-label="Hapus"><span class="material-symbols-outlined !w-4 !h-4">delete</span></button>
        </div>
      </li>
    </ul>
  </section>

  <Transition name="toast">
    <div v-if="toast" class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-full text-sm font-bold border border-outline-variant shadow-sm" :class="toastType === 'error' ? 'bg-error-container text-on-error-container' : 'bg-primary-fixed text-on-surface'">{{ toast }}</div>
  </Transition>
</AppShell>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import AppShell from '../../Components/AppShell.vue'
import BackButton from '../../Components/BackButton.vue'

const token = () => localStorage.getItem('madani_token')

const units = [
  { key: 'lpi', label: 'LPI' },
  { key: 'sma', label: 'SMA' },
  { key: 'smp', label: 'SMP' },
]

const previewPaths = { lpi: '/struktur', sma: '/sma/struktur', smp: '/smp/struktur' }

const activeUnit = ref('lpi')
const members = ref([])
const loading = ref(false)
const editing = ref(false)
const saving = ref(false)
const uploading = ref(false)
const toast = ref('')
const toastType = ref('success')
const photoPreview = ref('')

const blank = () => ({ id: null, unit: activeUnit.value, name: '', position: '', parent_id: null, photo_path: '', sort_order: 0, is_confirmed: false, is_active: true })
const form = reactive(blank())

const previewPath = computed(() => previewPaths[activeUnit.value] || '/struktur')
const unitLabel = (key) => units.find((u) => u.key === key)?.label || key

function notify(msg, type = 'success') {
  toast.value = msg
  toastType.value = type
  setTimeout(() => { toast.value = '' }, 3200)
}

function resolvePhoto(path) {
  if (!path) return null
  if (/^https?:\/\//.test(path) || String(path).startsWith('/')) return path
  return `${location.origin}/storage/${path}`
}

async function readError(r, fallback) {
  const j = await r.json().catch(() => ({}))
  return j.message || (j.errors && Object.values(j.errors).flat().join(', ')) || fallback
}

const siblingsOf = (member) => members.value.filter((m) => m.parent_id === member.parent_id)

function siblingOrdered(member) {
  return siblingsOf(member)
    .slice()
    .sort((a, b) => (a.sort_order - b.sort_order) || (a.id - b.id))
}

function canMove(member, delta) {
  const list = siblingOrdered(member)
  const i = list.findIndex((m) => m.id === member.id)
  const target = i + delta
  return i !== -1 && target >= 0 && target < list.length
}

const childrenOf = (id) => members.value.filter((m) => m.parent_id === id)

const depthOf = (member) => {
  let depth = 0
  let cur = member
  const guard = new Set()
  while (cur && cur.parent_id && !guard.has(cur.parent_id)) {
    guard.add(cur.parent_id)
    cur = members.value.find((x) => x.id === cur.parent_id)
    if (!cur) break
    depth++
  }
  return depth
}

const parentName = (id) => members.value.find((m) => m.id === id)?.name || members.value.find((m) => m.id === id)?.position || ''

const treeRows = computed(() => {
  const byParent = {}
  members.value.forEach((m) => {
    const key = m.parent_id ?? 'root'
    ;(byParent[key] = byParent[key] || []).push(m)
  })
  const rows = []
  const walk = (parentKey, depth) => {
    const list = (byParent[parentKey] || []).slice().sort((a, b) => (a.sort_order - b.sort_order) || (a.id - b.id))
    list.forEach((m) => {
      rows.push({ member: m, depth })
      walk(m.id, depth + 1)
    })
  }
  walk('root', 0)
  return rows
})

const parentOptions = computed(() => {
  const excluded = new Set()
  if (form.id) {
    excluded.add(form.id)
    const stack = [form.id]
    while (stack.length) {
      const cur = stack.pop()
      childrenOf(cur).forEach((c) => { if (!excluded.has(c.id)) { excluded.add(c.id); stack.push(c.id) } })
    }
  }
  return treeRows.value
    .filter((row) => !excluded.has(row.member.id))
    .map((row) => ({ id: row.member.id, label: `${'— '.repeat(row.depth)}${row.member.name || row.member.position}` }))
})

async function fetchMembers() {
  loading.value = true
  try {
    const r = await fetch(`/api/structure-members?unit=${activeUnit.value}`, { headers: { Authorization: `Bearer ${token()}`, Accept: 'application/json' } })
    if (!r.ok) throw new Error(await readError(r, 'Gagal memuat struktur.'))
    const j = await r.json()
    members.value = j.members || []
  } catch (e) {
    notify(e.message, 'error')
  } finally {
    loading.value = false
  }
}

function switchUnit(key) {
  if (activeUnit.value === key) return
  activeUnit.value = key
  cancel()
  fetchMembers()
}

function openCreate() {
  Object.assign(form, blank())
  form.unit = activeUnit.value
  form.sort_order = (members.value.filter((m) => m.parent_id === null).length || 0) + 1
  photoPreview.value = ''
  editing.value = true
}

function openEdit(m) {
  Object.assign(form, { ...blank(), ...m })
  photoPreview.value = resolvePhoto(m.photo_path) || ''
  editing.value = true
}

function cancel() {
  editing.value = false
  Object.assign(form, blank())
  photoPreview.value = ''
}

async function save() {
  if (!form.position.trim()) { notify('Jabatan wajib diisi.', 'error'); return }
  saving.value = true
  try {
    const payload = {
      name: form.name || null,
      position: form.position,
      parent_id: form.parent_id ?? null,
      photo_path: form.photo_path || null,
      sort_order: form.sort_order ?? 0,
      is_confirmed: !!form.is_confirmed,
      is_active: !!form.is_active,
    }
    const isEdit = !!form.id
    const r = await fetch(isEdit ? `/api/structure-members/${form.id}` : '/api/structure-members', {
      method: isEdit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token()}` },
      body: JSON.stringify(isEdit ? payload : { ...payload, unit: activeUnit.value }),
    })
    if (!r.ok) throw new Error(await readError(r, 'Gagal menyimpan anggota.'))
    notify(isEdit ? 'Anggota diperbarui.' : 'Anggota ditambahkan.')
    cancel()
    await fetchMembers()
  } catch (e) {
    notify(e.message, 'error')
  } finally {
    saving.value = false
  }
}

async function remove(m) {
  if (!confirm(`Hapus "${m.name || m.position}"? Anggota di bawahnya akan dipindahkan ke atasannya.`)) return
  try {
    const r = await fetch(`/api/structure-members/${m.id}`, { method: 'DELETE', headers: { Authorization: `Bearer ${token()}` } })
    if (!r.ok) throw new Error(await readError(r, 'Gagal menghapus anggota.'))
    members.value = members.value.filter((x) => x.id !== m.id)
    notify('Anggota dihapus.')
  } catch (e) {
    notify(e.message, 'error')
  }
}

async function move(m, delta) {
  try {
    const r = await fetch(`/api/structure-members/${m.id}/move`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token()}` },
      body: JSON.stringify({ direction: delta < 0 ? 'up' : 'down' }),
    })
    if (!r.ok) throw new Error(await readError(r, 'Gagal mengubah urutan.'))
    await fetchMembers()
  } catch (e) {
    notify(e.message, 'error')
  }
}

async function uploadPhoto(event) {
  const file = event.target.files?.[0]
  if (!file) return
  uploading.value = true
  try {
    const fd = new FormData()
    fd.append('file', file)
    const r = await fetch('/api/structure-members/upload-photo', { method: 'POST', headers: { Authorization: `Bearer ${token()}` }, body: fd })
    if (!r.ok) throw new Error(await readError(r, 'Gagal mengunggah foto.'))
    const j = await r.json()
    form.photo_path = j.path
    photoPreview.value = resolvePhoto(j.path)
    notify('Foto diunggah. Jangan lupa simpan.')
  } catch (e) {
    notify(e.message, 'error')
  } finally {
    uploading.value = false
    event.target.value = ''
  }
}

function clearPhoto() {
  form.photo_path = ''
  photoPreview.value = ''
}

onMounted(fetchMembers)
</script>
