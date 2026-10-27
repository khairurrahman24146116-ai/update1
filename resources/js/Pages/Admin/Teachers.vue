<template>
  <AppShell title="Data Guru">
    <div class="space-y-4">
      <BackButton :fallback="backFallback" />

      <section class="bg-white rounded-xl border border-outline-variant shadow-sm p-4 md:p-6 space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
          <div class="space-y-1">
            <h2 class="font-headline-sm text-headline-sm">Direktori Guru</h2>
            <p class="text-sm text-on-surface-variant max-w-3xl">
              <template v-if="isPrincipal">Data guru pada unit yang Anda pimpin. Anda dapat mengatur penugasan, wali kelas, dan absensi guru.</template>
              <template v-else>Kelola profil, unit, jabatan, penugasan, dan status akun guru. Operator hanya dapat membuat akun yang menunggu penetapan Admin Utama.</template>
            </p>
          </div>
          <button v-if="permissions.create" type="button" class="btn btn--primary btn--sm shrink-0" @click="openCreate">
            + Tambah Guru
          </button>
        </div>

        <div class="flex flex-wrap gap-2 border-b border-outline-variant pb-3" role="tablist" aria-label="Filter unit">
          <button
            v-for="tab in tabs"
            :key="tab.code"
            type="button"
            class="btn btn--sm"
            :class="unitFilter === tab.code ? 'btn--primary' : 'btn--ghost'"
            role="tab"
            :aria-selected="unitFilter === tab.code"
            @click="selectUnit(tab.code)"
          >
            {{ tab.code === 'all' ? 'Semua' : tab.code }}
          </button>
        </div>

        <div class="flex flex-col sm:flex-row gap-2">
          <input v-model="search" type="search" class="input w-full sm:max-w-sm" placeholder="Cari nama, NIP, atau email" @input="onSearch" />
          <select v-model="statusFilter" class="input w-full sm:w-44" @change="goPage(1)">
            <option value="">Semua status</option>
            <option value="pending">Menunggu</option>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
          </select>
        </div>

        <p v-if="error" class="text-error text-sm">{{ error }}</p>
      </section>

      <section v-if="showForm" class="bg-surface-container-low border border-outline-variant rounded-xl p-4 md:p-6 space-y-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="font-bold">Tambah Guru</h3>
            <p class="text-xs text-on-surface-variant mt-1">
              {{ canAssignUnits ? 'Akun langsung berstatus Guru. Lengkapi unit dan jabatan bila sudah diketahui.' : 'Akun dibuat sebagai Menunggu. Admin Utama akan menetapkan role dan unit.' }}
            </p>
          </div>
          <button type="button" class="btn btn--ghost btn--sm" @click="cancelForm">Batal</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <label class="space-y-1 text-xs font-bold">Nama lengkap
            <input v-model="form.name" class="input" autocomplete="name" />
          </label>
          <label class="space-y-1 text-xs font-bold">Email
            <input v-model="form.email" type="email" class="input" autocomplete="email" />
          </label>
          <label class="space-y-1 text-xs font-bold">NIP
            <input v-model="form.nip" class="input" placeholder="Opsional" />
          </label>
          <label class="space-y-1 text-xs font-bold">Telepon
            <input v-model="form.phone" class="input" placeholder="Opsional" />
          </label>
          <label class="space-y-1 text-xs font-bold">Kata sandi awal
            <input v-model="form.password" type="password" class="input" autocomplete="new-password" />
          </label>
          <label class="flex items-center gap-2 text-sm pt-6">
            <input v-model="form.is_active" type="checkbox" /> Akun aktif
          </label>
        </div>

        <div v-if="canAssignUnits" class="space-y-2">
          <p class="text-xs font-bold">Unit & jabatan</p>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
            <div v-for="unit in optionUnits" :key="unit.code" class="border border-outline-variant rounded-lg p-3 bg-white space-y-2">
              <label class="flex items-center gap-2 text-sm font-bold">
                <input v-model="selectedUnits" type="checkbox" :value="unit.code" />
                {{ unit.code }}
              </label>
              <p class="text-[11px] text-on-surface-variant">{{ unit.name }}</p>
              <input
                v-model="unitJabatan[unit.code]"
                class="input"
                placeholder="Jabatan"
                :disabled="!selectedUnits.includes(unit.code)"
              />
            </div>
          </div>
        </div>

        <p v-if="formError" class="text-error text-xs">{{ formError }}</p>
        <div class="flex gap-2">
          <button type="button" class="btn btn--primary btn--sm" :disabled="saving" @click="submit">
            {{ saving ? 'Menyimpan...' : 'Simpan' }}
          </button>
          <button type="button" class="btn btn--ghost btn--sm" @click="cancelForm">Batal</button>
        </div>
      </section>

      <section class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div v-if="loading" class="p-8 text-center text-sm text-on-surface-variant">Memuat data guru...</div>

        <template v-else>
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-primary text-on-primary">
                <tr>
                  <th class="p-3 text-left">Guru</th>
                  <th class="p-3 text-left">Unit & Jabatan</th>
                  <th class="p-3 text-center">Jumlah Kelas</th>
                  <th class="p-3 text-left">Status</th>
                  <th class="p-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="teacher in teachers" :key="teacher.id" class="border-t border-outline-variant align-top">
                  <td class="p-3">
                    <div class="flex items-center gap-3 min-w-48">
                      <div class="w-11 h-11 rounded-xl border border-outline-variant overflow-hidden bg-surface-container-low flex items-center justify-center shrink-0">
                        <img v-if="teacher.photo_url" :src="teacher.photo_url" :alt="`Foto ${teacher.name}`" class="w-full h-full object-cover" />
                        <span v-else class="material-symbols-outlined text-outline">person</span>
                      </div>
                      <div class="min-w-0">
                        <p class="font-bold truncate">{{ teacher.name }}</p>
                        <p class="text-xs text-on-surface-variant truncate">{{ teacher.email || 'Tanpa email' }}</p>
                        <p v-if="teacher.nip" class="text-[11px] text-on-surface-variant">NIP {{ teacher.nip }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="p-3">
                    <div v-if="teacher.units.length" class="flex flex-wrap gap-1">
                      <span v-for="unit in teacher.units" :key="unit.code" class="badge bg-surface-container-high text-on-surface">
                        {{ unit.code }}<template v-if="unit.jabatan"> · {{ unit.jabatan }}</template>
                      </span>
                    </div>
                    <span v-else class="text-xs text-on-surface-variant">Belum ada unit</span>
                  </td>
                  <td class="p-3 text-center font-bold">{{ teacher.class_count }}</td>
                  <td class="p-3">
                    <span class="badge" :class="statusClass(teacher.status)">{{ statusLabel(teacher.status) }}</span>
                  </td>
                  <td class="p-3 text-right">
                    <router-link :to="detailPath(teacher.id)" class="btn btn--ghost btn--sm">Detail</router-link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="md:hidden divide-y divide-outline-variant">
            <article v-for="teacher in teachers" :key="teacher.id" class="p-4 space-y-3">
              <div class="flex items-start gap-3">
                <div class="w-14 h-14 rounded-xl border border-outline-variant overflow-hidden bg-surface-container-low flex items-center justify-center shrink-0">
                  <img v-if="teacher.photo_url" :src="teacher.photo_url" :alt="`Foto ${teacher.name}`" class="w-full h-full object-cover" />
                  <span v-else class="material-symbols-outlined text-outline">person</span>
                </div>
                <div class="min-w-0 flex-1">
                  <p class="font-bold">{{ teacher.name }}</p>
                  <p class="text-xs text-on-surface-variant break-all">{{ teacher.email || 'Tanpa email' }}</p>
                  <div class="flex flex-wrap gap-1 mt-1">
                    <span v-for="unit in teacher.units" :key="unit.code" class="badge bg-surface-container-high text-on-surface">
                      {{ unit.code }}<template v-if="unit.jabatan"> · {{ unit.jabatan }}</template>
                    </span>
                    <span v-if="!teacher.units.length" class="text-xs text-on-surface-variant">Belum ada unit</span>
                  </div>
                </div>
              </div>
              <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                <div class="flex items-center gap-2">
                  <span class="badge" :class="statusClass(teacher.status)">{{ statusLabel(teacher.status) }}</span>
                  <span class="text-on-surface-variant">{{ teacher.class_count }} kelas</span>
                </div>
                <router-link :to="detailPath(teacher.id)" class="btn btn--ghost btn--sm">Lihat Detail</router-link>
              </div>
            </article>
          </div>

          <EmptyState
            v-if="!teachers.length"
            icon="person_off"
            title="Belum ada guru"
            description="Tidak ada guru yang cocok dengan pencarian dan filter saat ini."
          />

          <div v-if="meta" class="flex flex-wrap items-center justify-between gap-2 p-4 border-t border-outline-variant text-xs">
            <span class="text-on-surface-variant">Total {{ meta.total }} · Halaman {{ meta.current_page }} dari {{ meta.last_page }}</span>
            <div class="flex gap-2">
              <button type="button" class="btn btn--ghost btn--sm disabled:opacity-40" :disabled="meta.current_page <= 1" @click="goPage(meta.current_page - 1)">Prev</button>
              <button type="button" class="btn btn--ghost btn--sm disabled:opacity-40" :disabled="meta.current_page >= meta.last_page" @click="goPage(meta.current_page + 1)">Next</button>
            </div>
          </div>
        </template>
      </section>
    </div>
  </AppShell>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import AppShell from '../../Components/AppShell.vue'
import BackButton from '../../Components/BackButton.vue'
import EmptyState from '../../Components/EmptyState.vue'
import { useAuth } from '../../composables/useAuth.js'

const { user } = useAuth()

const teachers = ref([])
const meta = ref(null)
const tabs = ref([{ code: 'all', label: 'Semua' }])
const permissions = ref({ create: false })
const unitFilter = ref('all')
const statusFilter = ref('')
const search = ref('')
const page = ref(1)
const loading = ref(false)
const error = ref('')
const showForm = ref(false)
const saving = ref(false)
const formError = ref('')
const optionUnits = ref([])
const selectedUnits = ref([])
const unitJabatan = ref({})

const blankForm = () => ({ name: '', email: '', nip: '', phone: '', password: '', is_active: true })
const form = ref(blankForm())

const isPrincipal = computed(() => user.value?.role === 'kepala_sekolah')
const canAssignUnits = computed(() => user.value?.role === 'admin_utama')
const backFallback = computed(() => (isPrincipal.value ? '/app/kepala/guru' : '/app/admin'))
const detailPath = (id) => (isPrincipal.value ? `/app/kepala/guru/${id}` : `/app/admin/teachers/${id}`)

let searchTimer = null

function onSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => goPage(1), 350)
}

function goPage(nextPage) {
  page.value = Math.max(1, nextPage)
  load()
}

async function readError(response, fallback) {
  const payload = await response.json().catch(() => ({}))
  return payload.message || Object.values(payload.errors || {}).flat().join(', ') || fallback
}

async function load() {
  loading.value = true
  error.value = ''

  try {
    const query = new URLSearchParams()
    query.set('page', String(page.value))
    query.set('per_page', '20')
    query.set('unit', unitFilter.value)
    if (search.value.trim()) query.set('search', search.value.trim())
    if (statusFilter.value) query.set('status', statusFilter.value)

    const response = await fetch(`/api/teacher-directory?${query}`)
    if (!response.ok) throw new Error(await readError(response, 'Gagal memuat data guru.'))
    const payload = await response.json()

    teachers.value = payload.data || []
    meta.value = payload.current_page
      ? { current_page: payload.current_page, last_page: payload.last_page, total: payload.total }
      : null
    tabs.value = payload.tabs || []
    permissions.value = payload.permissions || { create: false }

    if (!tabs.value.some((tab) => tab.code === unitFilter.value)) {
      unitFilter.value = tabs.value[0]?.code || 'all'
      if (tabs.value.length) return load()
    }
  } catch (loadError) {
    error.value = loadError.message
    teachers.value = []
    meta.value = null
  } finally {
    loading.value = false
  }
}

async function loadOptions() {
  if (!canAssignUnits.value) return

  try {
    const response = await fetch('/api/teacher-directory/options')
    if (!response.ok) return
    const payload = await response.json()
    optionUnits.value = payload.units || []
  } catch {
    optionUnits.value = []
  }
}

function selectUnit(code) {
  if (unitFilter.value === code) return
  unitFilter.value = code
  goPage(1)
}

function openCreate() {
  form.value = blankForm()
  selectedUnits.value = []
  unitJabatan.value = {}
  formError.value = ''
  showForm.value = true
}

function cancelForm() {
  showForm.value = false
  formError.value = ''
}

async function submit() {
  formError.value = ''

  if (!form.value.name.trim() || !form.value.email.trim() || !form.value.password) {
    formError.value = 'Nama, email, dan kata sandi wajib diisi.'
    return
  }

  saving.value = true

  try {
    const payload = {
      name: form.value.name.trim(),
      email: form.value.email.trim(),
      password: form.value.password,
      nip: form.value.nip.trim() || null,
      phone: form.value.phone.trim() || null,
      is_active: Boolean(form.value.is_active),
    }

    if (canAssignUnits.value) {
      payload.units = selectedUnits.value.map((code) => ({
        code,
        jabatan: unitJabatan.value[code]?.trim() || null,
      }))
    }

    const response = await fetch('/api/teacher-directory', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })

    if (!response.ok) throw new Error(await readError(response, 'Gagal menambah guru.'))

    showForm.value = false
    await load()
  } catch (submitError) {
    formError.value = submitError.message
  } finally {
    saving.value = false
  }
}

function statusLabel(status) {
  return { pending: 'Menunggu', aktif: 'Aktif', nonaktif: 'Nonaktif' }[status] || status
}

function statusClass(status) {
  if (status === 'pending') return 'bg-error-container text-on-error-container'
  if (status === 'aktif') return 'bg-primary-fixed text-on-surface'
  return 'bg-surface-container-high text-on-surface-variant'
}

onMounted(async () => {
  await Promise.all([load(), loadOptions()])
})
</script>
