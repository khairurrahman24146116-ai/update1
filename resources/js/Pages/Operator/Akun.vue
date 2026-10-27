<template>
  <AppShell>
    <div class="operator-page space-y-6">
      <section class="op-hero">
        <div class="op-hero__copy">
          <span class="op-eyebrow">Operator · Portal Akun</span>
          <h1>Manajemen Akun</h1>
          <p>Kelola akses pengguna lintas unit, jabatan, dan status akun dalam satu tempat.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <router-link to="/app/admin/teachers" class="op-button op-button--light">
            <span class="material-symbols-outlined">person</span>
            Data Guru
          </router-link>
          <button type="button" class="op-button op-button--light" @click="openCreate">
            <span class="material-symbols-outlined">add</span>
            Tambah Akun
          </button>
        </div>
      </section>

      <nav class="op-tabs" aria-label="Filter unit">
        <button
          v-for="tab in unitTabs"
          :key="tab.value"
          type="button"
          class="op-tab"
          :class="{ 'is-active': filters.unit === tab.value }"
          @click="selectUnit(tab.value)"
        >
          {{ tab.label }}
        </button>
      </nav>

      <section class="op-panel">
        <div class="op-toolbar">
          <label class="op-search">
            <span class="material-symbols-outlined">search</span>
            <input
              v-model="filters.search"
              type="search"
              placeholder="Cari nama atau email..."
              aria-label="Cari akun"
              @input="onSearchInput"
            />
          </label>

          <select v-model="filters.role" class="op-select" aria-label="Filter role" @change="load(1)">
            <option value="">Semua role</option>
            <option v-for="role in options.roles" :key="role.value" :value="role.value">{{ role.label }}</option>
          </select>

          <select v-model="filters.is_active" class="op-select" aria-label="Filter status" @change="load(1)">
            <option value="">Semua status</option>
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>

          <button type="button" class="op-button op-button--ghost" @click="resetFilters">Reset</button>
        </div>

        <div v-if="pageError" class="op-alert" role="alert">
          <span class="material-symbols-outlined">error</span>
          <span>{{ pageError }}</span>
          <button type="button" @click="load()">Coba lagi</button>
        </div>

        <div v-if="loading" class="op-grid" aria-label="Memuat akun">
          <div v-for="index in 4" :key="index" class="op-account op-account--skeleton">
            <span class="op-skeleton op-skeleton--avatar"></span>
            <span class="op-skeleton op-skeleton--line"></span>
            <span class="op-skeleton op-skeleton--short"></span>
            <span class="op-skeleton op-skeleton--block"></span>
          </div>
        </div>

        <template v-else>
          <div v-if="accounts.length" class="op-grid">
            <article v-for="account in accounts" :key="account.id" class="op-account">
              <div class="op-account__head">
                <div class="op-avatar">{{ initials(account.name) }}</div>
                <div class="op-account__identity">
                  <h2>{{ account.name }}</h2>
                  <p>{{ account.email }}</p>
                </div>
                <span class="op-status" :class="account.is_active ? 'is-active' : 'is-inactive'">
                  {{ account.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </div>

              <div class="op-account__meta">
                <span class="op-role">{{ roleLabel(account.role) }}</span>
                <span class="op-meta-text">{{ account.units.length }} unit</span>
              </div>

              <div class="op-units">
                <div v-for="unit in account.units" :key="unit.code" class="op-unit">
                  <span class="op-unit__code">{{ unit.code }}</span>
                  <span class="op-unit__jabatan">{{ unit.jabatan || 'Tanpa jabatan' }}</span>
                </div>
                <span v-if="!account.units.length" class="op-unit op-unit--empty">Belum ada unit</span>
              </div>

              <div class="op-account__actions">
                <button v-if="canOpenPermissions(account)" type="button" class="op-action" @click="openPermissions(account)">Akses</button>
                <button type="button" class="op-action" @click="openEdit(account)">Edit</button>
                <button type="button" class="op-action" @click="openReset(account)">Reset sandi</button>
                <button
                  type="button"
                  class="op-action"
                  :class="account.is_active ? 'op-action--danger' : 'op-action--success'"
                  @click="toggleActive(account)"
                >
                  {{ account.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
              </div>
            </article>
          </div>

          <div v-else class="op-empty">
            <span class="material-symbols-outlined">group_off</span>
            <h2>Belum ada akun</h2>
            <p>Ubah filter atau tambahkan akun baru untuk unit ini.</p>
            <button type="button" class="op-button op-button--primary" @click="openCreate">Tambah Akun</button>
          </div>
        </template>

        <div v-if="meta && meta.last_page > 1" class="op-pagination">
          <span>Menampilkan {{ meta.from || 0 }}–{{ meta.to || 0 }} dari {{ meta.total }} akun</span>
          <div>
            <button type="button" class="op-page-button" :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">
              Sebelumnya
            </button>
            <span>Hal {{ meta.current_page }} / {{ meta.last_page }}</span>
            <button type="button" class="op-page-button" :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">
              Berikutnya
            </button>
          </div>
        </div>
      </section>

      <Transition name="op-fade">
        <div v-if="toast.message" class="op-toast" :class="`is-${toast.type}`" role="status">
          <span class="material-symbols-outlined">{{ toast.type === 'error' ? 'error' : 'check_circle' }}</span>
          {{ toast.message }}
        </div>
      </Transition>
    </div>

    <Teleport to="body">
      <Transition name="op-fade">
        <div v-if="formOpen" class="op-modal-backdrop" @click.self="closeForm">
          <form class="op-modal" @submit.prevent="submitForm">
            <header class="op-modal__head">
              <div>
                <span class="op-eyebrow">{{ form.id ? 'Ubah akun' : 'Akun baru' }}</span>
                <h2>{{ form.id ? 'Edit Akun' : 'Tambah Akun' }}</h2>
              </div>
              <button type="button" class="op-icon-button" aria-label="Tutup" @click="closeForm">
                <span class="material-symbols-outlined">close</span>
              </button>
            </header>

            <div class="op-modal__body">
              <div class="op-form-grid">
                <label class="op-field">
                  <span>Nama lengkap</span>
                  <input v-model="form.name" type="text" class="op-input" required autocomplete="name" />
                </label>
                <label class="op-field">
                  <span>Email</span>
                  <input v-model="form.email" type="email" class="op-input" required autocomplete="email" />
                </label>
                <div v-if="canAssignRole" class="op-field">
                  <span>Role</span>
                  <div ref="rolePicker" class="op-role-picker" @keydown.esc="roleOpen = false">
                    <button
                      type="button"
                      class="op-input op-role-trigger"
                      :class="{ 'is-open': roleOpen }"
                      aria-haspopup="listbox"
                      :aria-expanded="roleOpen"
                      @click="toggleRoleMenu"
                    >
                      <span class="op-role-trigger__value">{{ selectedRoleLabel }}</span>
                      <span class="op-role-trigger__chevron" aria-hidden="true"></span>
                    </button>
                    <div v-if="roleOpen" class="op-role-menu" role="listbox" aria-label="Pilih role">
                      <button
                        v-for="role in roleOptions"
                        :key="role.value"
                        type="button"
                        role="option"
                        class="op-role-option"
                        :class="{ 'is-selected': form.role === role.value }"
                        :aria-selected="form.role === role.value"
                        @click="selectRole(role.value)"
                      >
                        {{ role.label }}
                      </button>
                    </div>
                  </div>
                </div>
                <label v-if="!form.id" class="op-field">
                  <span>Kata sandi awal</span>
                  <div class="op-input-wrap">
                    <input
                      v-model="form.password"
                      :type="showPassword ? 'text' : 'password'"
                      class="op-input op-input--password"
                      minlength="6"
                      required
                      autocomplete="new-password"
                      placeholder="Minimal 6 karakter"
                    />
                    <button
                      type="button"
                      class="op-password-toggle"
                      :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                      :aria-pressed="showPassword"
                      @click="showPassword = !showPassword"
                    >
                      <span class="material-symbols-outlined" aria-hidden="true">
                        {{ showPassword ? 'visibility_off' : 'visibility' }}
                      </span>
                    </button>
                  </div>
                </label>
              </div>

              <div v-if="canAssignUnit" class="op-field">
                <div class="op-field__head">
                  <span>Unit & jabatan</span>
                  <small>Wajib minimal satu unit.</small>
                </div>
                <div class="op-unit-picker">
                  <div
                    v-for="unit in options.units"
                    :key="unit.code"
                    class="op-unit-choice"
                    :class="{ 'is-selected': isUnitSelected(unit.code) }"
                  >
                    <label class="op-unit-choice__toggle">
                      <input
                        type="checkbox"
                        class="op-unit-checkbox"
                        :checked="isUnitSelected(unit.code)"
                        :aria-label="`Pilih unit ${unit.code}`"
                        @change="toggleUnit(unit.code)"
                      />
                      <span class="op-unit-checkmark" aria-hidden="true"></span>
                      <span class="op-unit-choice__code">{{ unit.code }}</span>
                      <span class="op-unit-choice__label">{{ unit.label }}</span>
                    </label>
                    <label v-if="isUnitSelected(unit.code)" class="op-unit-choice__jabatan">
                      <span class="op-sr-only">Jabatan {{ unit.code }}</span>
                      <input
                        v-model="form.jabatan[unit.code]"
                        type="text"
                        class="op-input op-input--sm"
                        :placeholder="`Jabatan di ${unit.code}...`"
                      />
                    </label>
                  </div>
                </div>
              </div>

              <div v-if="!canAssignUnit" class="op-copy rounded-xl border border-outline-variant bg-surface-container-low p-3">
                Akun baru dibuat dengan status <strong>Menunggu Hak Akses</strong>. Admin Utama akan menetapkan role, unit, dan jabatan sebelum akun dapat digunakan.
              </div>

              <label class="op-switch">
                <input v-model="form.is_active" type="checkbox" />
                <span class="op-switch__track"></span>
                <span>
                  <strong>Akun aktif</strong>
                  <small>Akun nonaktif tidak dapat login.</small>
                </span>
              </label>

              <p v-if="formError" class="op-alert" role="alert">{{ formError }}</p>
            </div>

            <footer class="op-modal__foot">
              <button type="button" class="op-button op-button--ghost" @click="closeForm">Batal</button>
              <button type="submit" class="op-button op-button--primary" :disabled="formLoading">
                {{ formLoading ? 'Menyimpan...' : 'Simpan Akun' }}
              </button>
            </footer>
          </form>
        </div>
      </Transition>

      <Transition name="op-fade">
        <div v-if="resetOpen" class="op-modal-backdrop" @click.self="closeReset">
          <form class="op-modal op-modal--compact" @submit.prevent="submitReset">
            <header class="op-modal__head">
              <div>
                <span class="op-eyebrow">Keamanan akun</span>
                <h2>Reset Sandi</h2>
              </div>
              <button type="button" class="op-icon-button" aria-label="Tutup" @click="closeReset">
                <span class="material-symbols-outlined">close</span>
              </button>
            </header>

            <div class="op-modal__body">
              <p class="op-copy">Atur kata sandi baru untuk <strong>{{ resetAccount?.name }}</strong>. Sesi login lama akan diakhiri.</p>
              <label class="op-field">
                <span>Kata sandi baru</span>
                <div class="op-input-wrap">
                  <input
                    v-model="resetForm.password"
                    :type="showResetPassword ? 'text' : 'password'"
                    class="op-input op-input--password"
                    minlength="6"
                    required
                    autocomplete="new-password"
                    placeholder="Minimal 6 karakter"
                  />
                  <button
                    type="button"
                    class="op-password-toggle"
                    :aria-label="showResetPassword ? 'Sembunyikan kata sandi baru' : 'Tampilkan kata sandi baru'"
                    :aria-pressed="showResetPassword"
                    @click="showResetPassword = !showResetPassword"
                  >
                    <span class="material-symbols-outlined" aria-hidden="true">
                      {{ showResetPassword ? 'visibility_off' : 'visibility' }}
                    </span>
                  </button>
                </div>
              </label>
              <label class="op-field">
                <span>Ulangi kata sandi</span>
                <div class="op-input-wrap">
                  <input
                    v-model="resetForm.password_confirmation"
                    :type="showResetConfirm ? 'text' : 'password'"
                    class="op-input op-input--password"
                    minlength="6"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi kata sandi"
                  />
                  <button
                    type="button"
                    class="op-password-toggle"
                    :aria-label="showResetConfirm ? 'Sembunyikan ulangi kata sandi' : 'Tampilkan ulangi kata sandi'"
                    :aria-pressed="showResetConfirm"
                    @click="showResetConfirm = !showResetConfirm"
                  >
                    <span class="material-symbols-outlined" aria-hidden="true">
                      {{ showResetConfirm ? 'visibility_off' : 'visibility' }}
                    </span>
                  </button>
                </div>
              </label>
              <p v-if="resetError" class="op-alert" role="alert">{{ resetError }}</p>
            </div>

            <footer class="op-modal__foot">
              <button type="button" class="op-button op-button--ghost" @click="closeReset">Batal</button>
              <button type="submit" class="op-button op-button--primary" :disabled="resetLoading">
                {{ resetLoading ? 'Menyimpan...' : 'Reset Sandi' }}
              </button>
            </footer>
          </form>
        </div>
      </Transition>

      <PermissionsPanel
        v-if="permissionTarget"
        :acc="permissionTarget"
        @close="closePermissions"
        @saved="onPermissionsSaved"
      />
    </Teleport>
  </AppShell>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import AppShell from '../../Components/AppShell.vue'
import PermissionsPanel from '../../Components/PermissionsPanel.vue'
import { useDialog } from '../../composables/useDialog.js'

const unitTabs = [
  { value: 'all', label: 'Semua' },
  { value: 'LPI', label: 'LPI' },
  { value: 'SMA', label: 'SMA' },
  { value: 'SMP', label: 'SMP' },
]

const dialog = useDialog()
const accounts = ref([])
const meta = ref(null)
const options = reactive({
  roles: [],
  units: [],
  can_assign_role: false,
  can_assign_unit: false,
  can_manage_permissions: false,
  current_user_id: null,
})
const filters = reactive({ unit: 'all', search: '', role: '', is_active: '' })
const loading = ref(false)
const pageError = ref('')
const toast = reactive({ message: '', type: 'success' })
let toastTimer = null
let searchTimer = null

const formOpen = ref(false)
const formLoading = ref(false)
const formError = ref('')
const rolePicker = ref(null)
const roleOpen = ref(false)
const showPassword = ref(false)
const form = reactive({
  id: null,
  name: '',
  email: '',
  password: '',
  role: 'guru',
  is_active: true,
  selectedUnits: [],
  jabatan: {},
})

const permissionTarget = ref(null)

const resetOpen = ref(false)
const resetLoading = ref(false)
const resetError = ref('')
const resetAccount = ref(null)
const resetForm = reactive({ password: '', password_confirmation: '' })
const showResetPassword = ref(false)
const showResetConfirm = ref(false)

const canAssignRole = computed(() => Boolean(options.can_assign_role))
const canAssignUnit = computed(() => Boolean(options.can_assign_unit))
const canManagePermissions = computed(() => Boolean(options.can_manage_permissions))

const roleOptions = computed(() => {
  const seen = new Set()

  return options.roles
    .filter((role) => !['bendahara_lpi', 'bendahara_sma'].includes(role.value))
    .filter((role) => {
      if (seen.has(role.value)) return false
      seen.add(role.value)
      return true
    })
    .map((role) => (role.value === 'bendahara' ? { ...role, label: 'Bendahara' } : role))
})

const selectedRoleLabel = computed(() => {
  const selected = roleOptions.value.find((role) => role.value === form.role)
  return selected?.label || form.role.replaceAll('_', ' ')
})

onMounted(async () => {
  document.addEventListener('pointerdown', onDocumentPointerDown)
  await loadOptions()
  await load(1)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onDocumentPointerDown)
  clearTimeout(searchTimer)
  clearTimeout(toastTimer)
})

function onDocumentPointerDown(event) {
  if (!roleOpen.value) return
  if (!rolePicker.value?.contains(event.target)) roleOpen.value = false
}

async function request(url, requestOptions = {}) {
  const response = await fetch(url, {
    ...requestOptions,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(requestOptions.headers || {}),
    },
  })

  const data = response.status === 204 ? null : await response.json().catch(() => ({}))

  if (!response.ok) {
    const validation = data?.errors ? Object.values(data.errors).flat().join(' ') : ''
    throw new Error(validation || data?.message || 'Permintaan gagal diproses.')
  }

  return data
}

async function loadOptions() {
  try {
    const data = await request('/api/accounts/options')
    options.roles = data.roles || []
    options.units = data.units || []
    options.can_assign_role = Boolean(data.can_assign_role)
    options.can_assign_unit = Boolean(data.can_assign_unit)
    options.can_manage_permissions = Boolean(data.can_manage_permissions)
    options.current_user_id = data.current_user_id ?? null
  } catch (error) {
    pageError.value = error.message
  }
}

async function load(page = 1) {
  loading.value = true
  pageError.value = ''

  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', '20')

  if (filters.unit !== 'all') params.set('unit', filters.unit)
  if (filters.search.trim()) params.set('search', filters.search.trim())
  if (filters.role) params.set('role', filters.role)
  if (filters.is_active !== '') params.set('is_active', filters.is_active)

  try {
    const data = await request(`/api/accounts?${params}`)
    accounts.value = data.data || []
    meta.value = data
  } catch (error) {
    pageError.value = error.message
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => load(1), 300)
}

function selectUnit(unit) {
  filters.unit = unit
  load(1)
}

function resetFilters() {
  filters.unit = 'all'
  filters.search = ''
  filters.role = ''
  filters.is_active = ''
  load(1)
}

function roleLabel(role) {
  const normalized = normalizeRole(role)
  const match = roleOptions.value.find((item) => item.value === normalized)
    || options.roles.find((item) => item.value === normalized)
  return match?.label || normalized.replaceAll('_', ' ')
}

function normalizeRole(role) {
  return ['bendahara_lpi', 'bendahara_sma'].includes(role) ? 'bendahara' : role
}

function toggleRoleMenu() {
  roleOpen.value = !roleOpen.value
}

function selectRole(role) {
  form.role = role
  roleOpen.value = false
}

function initials(name) {
  return String(name || '?')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
}

function notify(message, type = 'success') {
  toast.message = message
  toast.type = type
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => {
    toast.message = ''
  }, 3500)
}

function canOpenPermissions(account) {
  return canManagePermissions.value
    && Number(account.id) !== Number(options.current_user_id)
    && account.role !== 'admin_utama'
    && !account.is_protected
}

function openPermissions(account) {
  permissionTarget.value = account
}

function closePermissions() {
  permissionTarget.value = null
}

function onPermissionsSaved() {
  notify('Hak akses berhasil diperbarui.')
}

function openCreate() {
  Object.assign(form, {
    id: null,
    name: '',
    email: '',
    password: '',
    role: canAssignRole.value
      ? (roleOptions.value.some((item) => item.value === 'guru') ? 'guru' : (roleOptions.value[0]?.value || 'guru'))
      : 'pending',
    is_active: true,
    selectedUnits: [],
    jabatan: {},
  })
  formError.value = ''
  roleOpen.value = false
  showPassword.value = false
  formOpen.value = true
}

function openEdit(account) {
  Object.assign(form, {
    id: account.id,
    name: account.name,
    email: account.email,
    password: '',
    role: canAssignRole.value && normalizeRole(account.role) === 'pending' ? 'guru' : normalizeRole(account.role),
    is_active: account.is_active,
    selectedUnits: account.units.map((unit) => unit.code),
    jabatan: Object.fromEntries(account.units.map((unit) => [unit.code, unit.jabatan || ''])),
  })
  formError.value = ''
  roleOpen.value = false
  showPassword.value = false
  formOpen.value = true
}

function closeForm() {
  formOpen.value = false
  formError.value = ''
  roleOpen.value = false
  showPassword.value = false
}

function isUnitSelected(code) {
  return form.selectedUnits.includes(code)
}

function toggleUnit(code) {
  if (isUnitSelected(code)) {
    form.selectedUnits = form.selectedUnits.filter((item) => item !== code)
    return
  }

  form.selectedUnits = [...form.selectedUnits, code]
  if (form.jabatan[code] === undefined) form.jabatan[code] = ''
}

async function submitForm() {
  formError.value = ''

  if (canAssignUnit.value && form.selectedUnits.length === 0) {
    formError.value = 'Pilih minimal satu unit.'
    return
  }

  formLoading.value = true

  const payload = {
    name: form.name,
    email: form.email,
    is_active: form.is_active,
  }

  if (canAssignRole.value) payload.role = form.role
  if (canAssignUnit.value) {
    payload.units = form.selectedUnits.map((code) => ({
      code,
      jabatan: form.jabatan[code] || null,
    }))
  }

  if (!form.id) payload.password = form.password

  try {
    await request(form.id ? `/api/accounts/${form.id}` : '/api/accounts', {
      method: form.id ? 'PUT' : 'POST',
      body: JSON.stringify(payload),
    })

    const message = form.id ? 'Akun berhasil diperbarui.' : 'Akun berhasil dibuat.'
    closeForm()
    notify(message)
    await load(meta.value?.current_page || 1)
  } catch (error) {
    formError.value = error.message
  } finally {
    formLoading.value = false
  }
}

async function toggleActive(account) {
  const activating = !account.is_active
  const confirmed = await dialog.confirm(
    activating
      ? `Aktifkan kembali akun ${account.name}?`
      : `Nonaktifkan akun ${account.name}? Akun ini tidak akan bisa login sampai diaktifkan kembali.`,
    {
      title: activating ? 'Aktifkan akun' : 'Nonaktifkan akun',
      variant: activating ? 'success' : 'danger',
      confirmText: activating ? 'Aktifkan' : 'Nonaktifkan',
    },
  )

  if (!confirmed) return

  try {
    await request(`/api/accounts/${account.id}`, {
      method: 'PUT',
      body: JSON.stringify({ is_active: activating }),
    })
    notify(activating ? 'Akun berhasil diaktifkan.' : 'Akun berhasil dinonaktifkan.')
    await load(meta.value?.current_page || 1)
  } catch (error) {
    notify(error.message, 'error')
  }
}

function openReset(account) {
  resetAccount.value = account
  resetForm.password = ''
  resetForm.password_confirmation = ''
  resetError.value = ''
  showResetPassword.value = false
  showResetConfirm.value = false
  resetOpen.value = true
}

function closeReset() {
  resetOpen.value = false
  resetError.value = ''
  showResetPassword.value = false
  showResetConfirm.value = false
}

async function submitReset() {
  resetError.value = ''
  resetLoading.value = true

  try {
    await request(`/api/accounts/${resetAccount.value.id}/reset-password`, {
      method: 'POST',
      body: JSON.stringify(resetForm),
    })
    closeReset()
    notify('Kata sandi berhasil direset.')
  } catch (error) {
    resetError.value = error.message
  } finally {
    resetLoading.value = false
  }
}
</script>

<style scoped>
.operator-page {
  --op-navy: #0b2347;
  --op-ink: #1a1c1a;
  --op-muted: #44474e;
  --op-canvas: #faf9f5;
  --op-card: #ffffff;
  --op-line: #c4c6cf;
  --op-soft: #f4f4f0;
  --op-danger: #ba1a1a;
  --op-success: #12683c;
  color: var(--op-ink);
  font-family: var(--font-body);
}

.op-hero {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1.5rem;
  padding: clamp(1.5rem, 4vw, 3rem);
  border-radius: 2rem;
  color: #ffffff;
  background:
    radial-gradient(circle at 92% 8%, rgba(173, 199, 254, 0.28), transparent 38%),
    linear-gradient(135deg, #000d27 0%, #0b2347 62%, #162439 100%);
  box-shadow: 0 18px 48px -28px rgba(11, 35, 71, 0.65);
}

.op-hero__copy {
  max-width: 42rem;
  min-width: 0;
}

.op-eyebrow {
  display: inline-flex;
  font-family: var(--font-label);
  font-size: 0.6875rem;
  font-weight: 600;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  opacity: 0.72;
}

.op-hero h1 {
  margin-top: 0.65rem;
  font-family: var(--font-headline);
  font-size: clamp(1.75rem, 4vw, 2.5rem);
  font-weight: 700;
  letter-spacing: -0.03em;
  line-height: 1.08;
}

.op-hero p {
  margin-top: 0.75rem;
  max-width: 36rem;
  color: rgba(255, 255, 255, 0.72);
  font-size: 0.9375rem;
  line-height: 1.65;
}

.op-tabs {
  display: flex;
  gap: 0.4rem;
  padding: 0.35rem;
  overflow-x: auto;
  border: 1px solid var(--op-line);
  border-radius: 999px;
  background: var(--op-card);
  box-shadow: 0 1px 3px rgba(11, 35, 71, 0.04);
  scrollbar-width: none;
}

.op-tabs::-webkit-scrollbar {
  display: none;
}

.op-tab {
  flex: 0 0 auto;
  min-width: 5rem;
  padding: 0.65rem 1.15rem;
  border: 0;
  border-radius: 999px;
  background: transparent;
  color: var(--op-muted);
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 180ms ease, color 180ms ease, transform 180ms ease;
}

.op-tab:hover {
  background: var(--op-soft);
  color: var(--op-navy);
}

.op-tab.is-active {
  background: var(--op-navy);
  color: #ffffff;
  box-shadow: 0 6px 16px rgba(11, 35, 71, 0.16);
}

.op-panel {
  padding: clamp(1rem, 3vw, 1.5rem);
  border: 1px solid var(--op-line);
  border-radius: 2rem;
  background: var(--op-card);
  box-shadow: 0 1px 3px rgba(11, 35, 71, 0.04);
}

.op-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.65rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--op-line);
}

.op-search {
  display: flex;
  flex: 1 1 16rem;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
  height: 2.75rem;
  padding: 0 1rem;
  border: 1px solid var(--op-line);
  border-radius: 999px;
  background: var(--op-card);
  box-shadow: inset 0 1px 2px rgba(11, 35, 71, 0.04);
}

.op-search .material-symbols-outlined {
  color: var(--op-muted);
  font-size: 1.15rem;
}

.op-search input {
  width: 100%;
  min-width: 0;
  border: 0;
  outline: 0;
  background: transparent;
  color: var(--op-ink);
  font-size: 0.875rem;
}

.op-search:focus-within {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-container);
}

.op-select,
.op-input {
  width: 100%;
  min-height: 2.75rem;
  padding: 0 1rem;
  border: 1px solid var(--op-line);
  border-radius: 999px;
  background: var(--op-card);
  color: var(--op-ink);
  font-family: var(--font-body);
  font-size: 0.875rem;
  outline: 0;
  box-shadow: inset 0 1px 2px rgba(11, 35, 71, 0.04);
}

.op-input {
  border-color: var(--color-outline);
  background: var(--color-surface-container-lowest);
  color: var(--color-on-surface);
  box-shadow: var(--shadow-inset);
}

.op-input::placeholder {
  color: var(--color-on-surface-variant);
  opacity: 1;
}

.op-input:focus {
  border-color: var(--color-primary);
  outline: none;
  box-shadow: 0 0 0 3px var(--color-primary-container);
}

.op-input-wrap {
  position: relative;
  display: flex;
  min-width: 0;
}

.op-input-wrap .op-input {
  padding-right: 3.2rem;
}

.op-password-toggle {
  position: absolute;
  top: 50%;
  right: 0.32rem;
  display: grid;
  place-items: center;
  width: 2.2rem;
  height: 2.2rem;
  padding: 0;
  border: 0;
  border-radius: 999px;
  background: transparent;
  color: var(--color-on-surface-variant);
  cursor: pointer;
  transform: translateY(-50%);
  transition: background 160ms ease, color 160ms ease;
}

.op-password-toggle:hover {
  background: var(--color-surface-container);
  color: var(--color-primary);
}

.op-password-toggle:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 1px;
}

.op-password-toggle .material-symbols-outlined {
  font-size: 1.15rem;
}

.op-sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.op-select {
  flex: 0 1 10.5rem;
  width: auto;
}

.op-role-picker {
  position: relative;
  min-width: 0;
}

.op-role-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.6rem;
  text-align: left;
  cursor: pointer;
}

.op-role-trigger.is-open {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-container);
}

.op-role-trigger__value {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.op-role-trigger__chevron {
  flex: 0 0 auto;
  width: 0.5rem;
  height: 0.5rem;
  margin-top: -0.2rem;
  border-right: 2px solid currentColor;
  border-bottom: 2px solid currentColor;
  transform: rotate(45deg);
  transition: transform 160ms ease;
}

.op-role-trigger.is-open .op-role-trigger__chevron {
  margin-top: 0.2rem;
  transform: rotate(225deg);
}

.op-role-menu {
  position: absolute;
  z-index: 20;
  top: calc(100% + 0.4rem);
  right: 0;
  left: 0;
  display: grid;
  gap: 0.15rem;
  max-height: 15rem;
  padding: 0.35rem;
  overflow-y: auto;
  border: 1px solid var(--color-outline);
  border-radius: 1rem;
  background: var(--color-surface-container-lowest);
  box-shadow: 0 16px 36px -18px rgba(0, 0, 0, 0.35);
}

.op-role-option {
  width: 100%;
  padding: 0.7rem 0.8rem;
  border: 0;
  border-radius: 0.7rem;
  background: transparent;
  color: var(--color-on-surface);
  font-family: var(--font-body);
  font-size: 0.8125rem;
  font-weight: 600;
  text-align: left;
  cursor: pointer;
  transition: background 150ms ease, color 150ms ease;
}

.op-role-option:hover,
.op-role-option:focus-visible {
  background: var(--color-surface-container);
  color: var(--color-on-surface);
  outline: none;
}

.op-role-option.is-selected {
  background: var(--color-primary);
  color: var(--color-on-primary);
  box-shadow: inset 3px 0 0 var(--color-secondary);
}

.op-role-option.is-selected:hover,
.op-role-option.is-selected:focus-visible {
  background: var(--color-primary-hover);
  color: var(--color-on-primary);
}

.op-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  min-height: 2.75rem;
  padding: 0 1.15rem;
  border: 1px solid transparent;
  border-radius: 999px;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 180ms ease, box-shadow 180ms ease, background 180ms ease;
}

.op-button .material-symbols-outlined {
  font-size: 1.1rem;
}

.op-button:hover:not(:disabled) {
  transform: translateY(-1px);
}

.op-button:disabled {
  cursor: wait;
  opacity: 0.65;
}

.op-button--primary {
  background: var(--color-primary);
  color: var(--color-on-primary);
  box-shadow: 0 6px 16px rgba(23, 37, 29, 0.16);
}

.op-button--primary:hover:not(:disabled) {
  background: var(--color-primary-hover);
}

.op-button--light {
  background: #ffffff;
  color: var(--op-navy);
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.18);
}

.op-button--ghost {
  border-color: var(--color-outline);
  background: var(--color-surface-container-lowest);
  color: var(--color-on-surface);
}

.op-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
  padding-top: 1rem;
}

.op-account {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
  min-width: 0;
  padding: 1.15rem;
  border: 1px solid var(--op-line);
  border-radius: 1.35rem;
  background: var(--op-canvas);
  transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
}

.op-account:hover {
  transform: translateY(-2px);
  border-color: rgba(11, 35, 71, 0.35);
  box-shadow: 0 6px 16px rgba(11, 35, 71, 0.08);
}

.op-account__head {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  min-width: 0;
}

.op-avatar {
  display: grid;
  place-items: center;
  flex: 0 0 2.65rem;
  width: 2.65rem;
  height: 2.65rem;
  border-radius: 999px;
  background: var(--op-navy);
  color: #ffffff;
  font-family: var(--font-headline);
  font-size: 0.8125rem;
  font-weight: 700;
  letter-spacing: 0.03em;
}

.op-account__identity {
  flex: 1 1 auto;
  min-width: 0;
}

.op-account__identity h2 {
  overflow: hidden;
  color: var(--op-ink);
  font-family: var(--font-headline);
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: -0.015em;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.op-account__identity p {
  overflow: hidden;
  margin-top: 0.15rem;
  color: var(--op-muted);
  font-size: 0.75rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.op-status {
  flex: 0 0 auto;
  padding: 0.25rem 0.6rem;
  border: 1px solid currentColor;
  border-radius: 999px;
  font-size: 0.625rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.op-status.is-active {
  background: #e7f5ec;
  color: var(--op-success);
}

.op-status.is-inactive {
  background: #fbe4e2;
  color: var(--op-danger);
}

.op-account__meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.op-role {
  padding: 0.22rem 0.6rem;
  border-radius: 999px;
  background: #d7e2ff;
  color: #32476c;
  font-size: 0.6875rem;
  font-weight: 700;
}

.op-meta-text {
  color: var(--op-muted);
  font-size: 0.6875rem;
}

.op-units {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.op-unit {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  max-width: 100%;
  padding: 0.3rem 0.55rem 0.3rem 0.35rem;
  border: 1px solid var(--op-line);
  border-radius: 999px;
  background: var(--op-card);
}

.op-unit__code {
  display: inline-flex;
  padding: 0.18rem 0.42rem;
  border-radius: 999px;
  background: var(--op-navy);
  color: #ffffff;
  font-size: 0.625rem;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.op-unit__jabatan {
  overflow: hidden;
  color: var(--op-muted);
  font-size: 0.6875rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.op-unit--empty {
  padding: 0.35rem 0.65rem;
  color: var(--op-danger);
  font-size: 0.6875rem;
}

.op-account__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-top: auto;
  padding-top: 0.15rem;
}

.op-action {
  padding: 0.4rem 0.7rem;
  border: 1px solid var(--op-line);
  border-radius: 999px;
  background: var(--op-card);
  color: var(--op-ink);
  font-size: 0.6875rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 180ms ease, border-color 180ms ease;
}

.op-action:hover {
  border-color: var(--op-navy);
  background: var(--op-soft);
}

.op-action--danger {
  color: var(--op-danger);
}

.op-action--success {
  color: var(--op-success);
}

.op-alert {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.8rem 1rem;
  border: 1px solid var(--color-error);
  border-radius: 1rem;
  background: var(--color-error-bg);
  color: var(--color-on-error-container);
  font-size: 0.8125rem;
}

.op-alert button {
  margin-left: auto;
  border: 0;
  background: transparent;
  color: inherit;
  font-weight: 700;
  text-decoration: underline;
  cursor: pointer;
}

.op-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.6rem;
  padding: 3.5rem 1rem;
  text-align: center;
}

.op-empty > .material-symbols-outlined {
  display: grid;
  place-items: center;
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 999px;
  background: var(--op-soft);
  color: var(--op-navy);
  font-size: 1.6rem;
}

.op-empty h2 {
  font-family: var(--font-headline);
  font-size: 1.1rem;
  font-weight: 700;
}

.op-empty p {
  color: var(--op-muted);
  font-size: 0.8125rem;
}

.op-pagination {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding-top: 1rem;
  margin-top: 1rem;
  border-top: 1px solid var(--op-line);
  color: var(--op-muted);
  font-size: 0.75rem;
}

.op-pagination > div {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.op-page-button {
  padding: 0.45rem 0.8rem;
  border: 1px solid var(--op-line);
  border-radius: 999px;
  background: var(--op-card);
  color: var(--op-ink);
  font-size: 0.6875rem;
  font-weight: 600;
  cursor: pointer;
}

.op-page-button:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

.op-account--skeleton {
  min-height: 13rem;
  pointer-events: none;
}

.op-skeleton {
  display: block;
  border-radius: 999px;
  background: linear-gradient(90deg, #eeeeea 25%, #f7f7f3 50%, #eeeeea 75%);
  background-size: 200% 100%;
  animation: op-shimmer 1.3s infinite;
}

.op-skeleton--avatar {
  width: 2.65rem;
  height: 2.65rem;
}

.op-skeleton--line {
  width: 70%;
  height: 0.9rem;
}

.op-skeleton--short {
  width: 45%;
  height: 0.7rem;
}

.op-skeleton--block {
  width: 100%;
  height: 3rem;
  border-radius: 1rem;
}

.op-toast {
  position: fixed;
  right: 1rem;
  bottom: 1rem;
  z-index: 70;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  max-width: min(24rem, calc(100vw - 2rem));
  padding: 0.8rem 1rem;
  border: 1px solid var(--op-line);
  border-radius: 999px;
  background: #ffffff;
  color: var(--op-ink);
  box-shadow: 0 12px 34px -12px rgba(11, 35, 71, 0.35);
  font-size: 0.8125rem;
  font-weight: 600;
}

.op-toast.is-error {
  border-color: var(--op-danger);
  color: #93000a;
}

.op-toast.is-success {
  border-color: var(--op-success);
  color: var(--op-success);
}

.op-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 80;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding: 0;
  background: rgba(23, 37, 29, 0.3);
  backdrop-filter: blur(2px);
}

:global(html[data-theme='dark']) .op-modal-backdrop {
  background: rgba(0, 0, 0, 0.48);
}

:global(html[data-theme='dark']) .op-input,
:global(html[data-theme='dark']) .op-unit-choice {
  background: var(--color-surface-container-low);
}

:global(html[data-theme='dark']) .op-role-menu {
  background: var(--color-surface-container);
}

:global(html[data-theme='dark']) .op-unit-choice.is-selected {
  background: var(--color-primary-container);
}

.op-modal {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 44rem;
  max-height: 92dvh;
  overflow: hidden;
  border: 1px solid var(--color-outline-variant);
  border-radius: 1.75rem 1.75rem 0 0;
  background: var(--color-surface-container-lowest);
  color: var(--color-on-surface);
  box-shadow: 0 24px 80px -20px rgba(0, 0, 0, 0.4);
}

.op-modal--compact {
  max-width: 30rem;
}

.op-modal__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.45rem 1.35rem 1.1rem;
  border-bottom: 1px solid var(--color-outline-variant);
  color: var(--color-on-surface);
  background: linear-gradient(135deg, var(--color-primary-container), var(--color-surface-container-lowest) 72%);
  box-shadow: inset 0 4px 0 var(--color-secondary);
}

.op-modal__head .op-eyebrow {
  color: var(--color-secondary);
  opacity: 1;
}

.op-modal__head h2 {
  margin-top: 0.25rem;
  font-family: var(--font-headline);
  font-size: 1.3rem;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.op-icon-button {
  display: grid;
  place-items: center;
  flex: 0 0 2.5rem;
  width: 2.5rem;
  height: 2.5rem;
  border: 1px solid var(--color-outline);
  border-radius: 999px;
  background: var(--color-surface-container-lowest);
  color: var(--color-on-surface);
  cursor: pointer;
  transition: background 160ms ease, border-color 160ms ease;
}

.op-icon-button:hover {
  border-color: var(--color-primary);
  background: var(--color-surface-container);
}

.op-modal__body {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1.35rem;
  overflow-y: auto;
}

.op-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
}

.op-field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  min-width: 0;
}

.op-field > span,
.op-field__head > span {
  color: var(--color-on-surface);
  font-size: 0.75rem;
  font-weight: 700;
}

.op-field small,
.op-field__head small {
  color: var(--color-on-surface-variant);
  font-size: 0.6875rem;
  font-weight: 400;
}

.op-field__head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.5rem;
}

.op-input--sm {
  min-height: 2.5rem;
  margin-top: 0;
  font-size: 0.75rem;
}

.op-unit-picker {
  display: grid;
  gap: 0.55rem;
  margin-top: 0.5rem;
}

.op-unit-choice {
  position: relative;
  overflow: hidden;
  border: 1px solid var(--color-outline-variant);
  border-radius: 1rem;
  background: var(--color-surface-container-lowest);
  transition: border-color 180ms ease, background 180ms ease, box-shadow 180ms ease;
}

.op-unit-choice:hover {
  border-color: var(--color-primary);
}

.op-unit-choice.is-selected {
  border-color: var(--color-primary);
  background: var(--color-surface-container-low);
  box-shadow: inset 3px 0 0 var(--color-primary);
}

.op-unit-choice__toggle {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  width: 100%;
  min-height: 3.4rem;
  padding: 0.8rem 0.9rem;
  cursor: pointer;
}

.op-unit-checkbox {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
}

.op-unit-checkmark {
  display: grid;
  place-items: center;
  flex: 0 0 1.25rem;
  width: 1.25rem;
  height: 1.25rem;
  border: 2px solid var(--color-outline);
  border-radius: 0.35rem;
  background: var(--color-surface-container-lowest);
  transition: background 160ms ease, border-color 160ms ease;
}

.op-unit-checkmark::after {
  content: '';
  width: 0.32rem;
  height: 0.58rem;
  border-right: 2px solid var(--color-on-primary);
  border-bottom: 2px solid var(--color-on-primary);
  transform: rotate(45deg) translate(-1px, -1px) scale(0);
  transition: transform 140ms ease;
}

.op-unit-checkbox:checked + .op-unit-checkmark {
  border-color: var(--color-primary);
  background: var(--color-primary);
}

.op-unit-checkbox:checked + .op-unit-checkmark::after {
  transform: rotate(45deg) translate(-1px, -1px) scale(1);
}

.op-unit-checkbox:focus-visible + .op-unit-checkmark {
  outline: 3px solid var(--color-primary-container);
  outline-offset: 2px;
}

.op-unit-choice__code {
  font-family: var(--font-code);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.03em;
}

.op-unit-choice__label {
  overflow: hidden;
  color: var(--color-on-surface-variant);
  font-size: 0.75rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.op-unit-choice__jabatan {
  display: block;
  padding: 0 0.9rem 0.9rem;
}

.op-switch {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  cursor: pointer;
}

.op-switch input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.op-switch__track {
  position: relative;
  flex: 0 0 2.6rem;
  width: 2.6rem;
  height: 1.45rem;
  border-radius: 999px;
  background: var(--color-surface-container-highest);
  transition: background 180ms ease;
}

.op-switch__track::after {
  content: '';
  position: absolute;
  top: 0.18rem;
  left: 0.18rem;
  width: 1.09rem;
  height: 1.09rem;
  border-radius: 999px;
  background: #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  transition: transform 180ms ease;
}

.op-switch input:checked + .op-switch__track {
  background: var(--color-primary);
}

.op-switch input:checked + .op-switch__track::after {
  transform: translateX(1.15rem);
}

.op-switch strong {
  display: block;
  color: var(--color-on-surface);
  font-size: 0.75rem;
}

.op-switch small {
  display: block;
  margin-top: 0.1rem;
  color: var(--color-on-surface-variant);
  font-size: 0.6875rem;
}

.op-copy {
  color: var(--color-on-surface-variant);
  font-size: 0.8125rem;
  line-height: 1.6;
}

.op-copy strong {
  color: var(--color-on-surface);
}

.op-modal__foot {
  display: flex;
  justify-content: flex-end;
  gap: 0.55rem;
  padding: 1rem 1.35rem;
  border-top: 1px solid var(--color-outline-variant);
  background: var(--color-surface-container-low);
}

.op-fade-enter-active,
.op-fade-leave-active {
  transition: opacity 180ms ease;
}

.op-fade-enter-from,
.op-fade-leave-to {
  opacity: 0;
}

@keyframes op-shimmer {
  to {
    background-position: -200% 0;
  }
}

@media (max-width: 900px) {
  .op-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .op-hero {
    align-items: stretch;
    flex-direction: column;
    border-radius: 1.5rem;
  }

  .op-hero .op-button {
    width: 100%;
  }

  .op-toolbar {
    align-items: stretch;
  }

  .op-search,
  .op-select {
    flex-basis: 100%;
    width: 100%;
  }

  .op-panel {
    border-radius: 1.5rem;
  }

  .op-form-grid {
    grid-template-columns: 1fr;
  }

  .op-modal-backdrop {
    align-items: flex-end;
  }

  .op-modal {
    max-height: 94dvh;
    border-radius: 1.5rem 1.5rem 0 0;
  }

  .op-modal__foot {
    flex-direction: column-reverse;
  }

  .op-modal__foot .op-button {
    width: 100%;
  }

  .op-toast {
    right: 0.75rem;
    bottom: 0.75rem;
    left: 0.75rem;
    max-width: none;
  }
}
</style>
