<template>
  <AppShell title="Detail Guru">
    <div class="space-y-4">
      <BackButton :fallback="backFallback" label="Kembali ke Data Guru" />

      <div v-if="loading" class="bg-white rounded-xl border border-outline-variant p-8 text-center text-sm text-on-surface-variant">
        Memuat detail guru...
      </div>

      <div v-else-if="error" class="bg-error-container text-on-error-container rounded-xl border border-outline-variant p-4 text-sm">
        {{ error }}
      </div>

      <template v-else-if="teacher">
        <section class="bg-white rounded-xl border border-outline-variant shadow-sm p-4 md:p-6">
          <div class="flex flex-col lg:flex-row lg:items-start gap-5">
            <div class="w-24 h-24 md:w-28 md:h-28 rounded-xl border border-outline-variant overflow-hidden bg-surface-container-low flex items-center justify-center shrink-0">
              <img v-if="teacher.photo_url" :src="teacher.photo_url" :alt="`Foto ${teacher.name}`" class="w-full h-full object-cover" />
              <span v-else class="material-symbols-outlined text-outline text-[42px]">person</span>
            </div>

            <div class="min-w-0 flex-1 space-y-3">
              <div class="flex flex-wrap items-start gap-2">
                <div class="min-w-0 flex-1">
                  <h2 class="font-headline-md text-headline-md break-words">{{ teacher.name }}</h2>
                  <p class="text-sm text-on-surface-variant break-all">{{ teacher.email || 'Tanpa email' }}</p>
                </div>
                <span class="badge" :class="statusClass(teacher.status)">{{ statusLabel(teacher.status) }}</span>
              </div>

              <div class="flex flex-wrap gap-1">
                <span v-for="unit in teacher.units" :key="unit.code" class="badge bg-primary-fixed text-on-surface">
                  {{ unit.code }}<template v-if="unit.jabatan"> · {{ unit.jabatan }}</template>
                </span>
                <span v-if="!teacher.units.length" class="badge bg-surface-container-high text-on-surface-variant">Belum ada unit</span>
              </div>

              <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                <div class="rounded-lg bg-surface-container-low border border-outline-variant p-3">
                  <p class="text-on-surface-variant">Jumlah kelas</p>
                  <p class="font-bold text-lg">{{ teacher.class_count }}</p>
                </div>
                <div class="rounded-lg bg-surface-container-low border border-outline-variant p-3">
                  <p class="text-on-surface-variant">Wali kelas</p>
                  <p class="font-bold text-lg">{{ teacher.wali_classrooms.length }}</p>
                </div>
                <div class="rounded-lg bg-surface-container-low border border-outline-variant p-3">
                  <p class="text-on-surface-variant">Penugasan</p>
                  <p class="font-bold text-lg">{{ teacher.assignments.length }}</p>
                </div>
              </div>

              <div class="flex flex-wrap gap-2 pt-1">
                <button v-if="teacher.permissions.update" type="button" class="btn btn--primary btn--sm" @click="openProfileForm">Edit Profil</button>
                <button v-if="teacher.permissions.manage_units" type="button" class="btn btn--ghost btn--sm" @click="openUnitsForm">Atur Unit & Jabatan</button>
                <button v-if="teacher.permissions.manage_assignments" type="button" class="btn btn--ghost btn--sm" @click="openAssignmentForm">Atur Penugasan</button>
                <a v-if="teacher.permissions.manage_attendance" href="#rekap-absensi" class="btn btn--ghost btn--sm">Rekap Absensi</a>
              </div>

              <label v-if="teacher.permissions.update" class="inline-flex items-center gap-2 text-xs font-bold text-primary cursor-pointer">
                <span>{{ photoUploading ? 'Mengunggah foto...' : 'Unggah foto profil' }}</span>
                <input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" :disabled="photoUploading" @change="uploadPhoto" />
              </label>
            </div>
          </div>
        </section>

        <section v-if="showProfileForm" class="bg-surface-container-low rounded-xl border border-outline-variant p-4 md:p-6 space-y-4">
          <div class="flex items-start justify-between gap-3">
            <h3 class="font-bold">Edit Profil</h3>
            <button type="button" class="btn btn--ghost btn--sm" @click="showProfileForm = false">Batal</button>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <label class="space-y-1 text-xs font-bold">Nama lengkap
              <input v-model="profileForm.name" class="input" />
            </label>
            <label class="space-y-1 text-xs font-bold">Email
              <input v-model="profileForm.email" type="email" class="input" />
            </label>
            <label class="space-y-1 text-xs font-bold">NIP
              <input v-model="profileForm.nip" class="input" />
            </label>
            <label class="space-y-1 text-xs font-bold">Telepon
              <input v-model="profileForm.phone" class="input" />
            </label>
            <label class="space-y-1 text-xs font-bold">Kata sandi baru
              <input v-model="profileForm.password" type="password" class="input" placeholder="Kosongkan bila tidak diganti" autocomplete="new-password" />
            </label>
            <label class="flex items-center gap-2 text-sm pt-6">
              <input v-model="profileForm.is_active" type="checkbox" /> Akun aktif
            </label>
          </div>
          <p v-if="profileError" class="text-error text-xs">{{ profileError }}</p>
          <button type="button" class="btn btn--primary btn--sm" :disabled="profileSaving" @click="saveProfile">
            {{ profileSaving ? 'Menyimpan...' : 'Simpan Profil' }}
          </button>
        </section>

        <section v-if="showUnitsForm" class="bg-surface-container-low rounded-xl border border-outline-variant p-4 md:p-6 space-y-4">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="font-bold">Unit & Jabatan</h3>
              <p class="text-xs text-on-surface-variant mt-1">Unit menentukan tampilan di direktori dan cakupan akses data. Jabatan diambil dari data unit guru.</p>
            </div>
            <button type="button" class="btn btn--ghost btn--sm" @click="showUnitsForm = false">Batal</button>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
            <div v-for="unit in options.units" :key="unit.code" class="border border-outline-variant rounded-lg p-3 bg-white space-y-2">
              <label class="flex items-center gap-2 text-sm font-bold">
                <input v-model="unitsForm.selected" type="checkbox" :value="unit.code" />
                {{ unit.code }}
              </label>
              <p class="text-[11px] text-on-surface-variant">{{ unit.name }}</p>
              <input
                v-model="unitsForm.jabatan[unit.code]"
                class="input"
                placeholder="Jabatan"
                :disabled="!unitsForm.selected.includes(unit.code)"
              />
            </div>
          </div>
          <p v-if="unitsError" class="text-error text-xs">{{ unitsError }}</p>
          <button type="button" class="btn btn--primary btn--sm" :disabled="unitsSaving" @click="saveUnits">
            {{ unitsSaving ? 'Menyimpan...' : 'Simpan Unit' }}
          </button>
        </section>

        <section v-if="showAssignmentForm" class="bg-surface-container-low rounded-xl border border-outline-variant p-4 md:p-6 space-y-4">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="font-bold">Penugasan & Wali Kelas</h3>
              <p class="text-xs text-on-surface-variant mt-1">Penugasan mata pelajaran disimpan per tahun ajaran. Pilih kelas yang berada pada unit guru.</p>
            </div>
            <button type="button" class="btn btn--ghost btn--sm" @click="showAssignmentForm = false">Batal</button>
          </div>

          <label class="block space-y-1 text-xs font-bold max-w-52">Tahun ajaran
            <input v-model="assignmentYear" class="input" @change="syncAssignmentRows" />
          </label>

          <div v-if="assignableClassrooms.length" class="space-y-3">
            <div class="space-y-2">
              <div v-for="(row, index) in assignmentRows" :key="row.key" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end border border-outline-variant rounded-lg p-3 bg-white">
                <label class="space-y-1 text-xs font-bold">Mata pelajaran
                  <select v-model="row.subject_id" class="input">
                    <option value="">Pilih mapel</option>
                    <option v-for="subject in options.subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                  </select>
                </label>
                <label class="space-y-1 text-xs font-bold">Kelas
                  <select v-model="row.classroom_id" class="input">
                    <option value="">Pilih kelas</option>
                    <option v-for="classroom in assignableClassrooms" :key="classroom.id" :value="classroom.id">
                      {{ classroom.name }} · {{ classroom.unit }}
                    </option>
                  </select>
                </label>
                <button type="button" class="btn btn--ghost btn--sm" @click="removeAssignmentRow(index)">Hapus</button>
              </div>
            </div>
            <button type="button" class="btn btn--ghost btn--sm" @click="addAssignmentRow">+ Tambah Mapel</button>

            <div class="border border-outline-variant rounded-lg p-3 bg-white space-y-2">
              <p class="text-xs font-bold">Wali kelas</p>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                <label v-for="classroom in assignableClassrooms" :key="classroom.id" class="flex items-center gap-2 text-sm">
                  <input v-model="waliClassroomIds" type="checkbox" :value="classroom.id" />
                  {{ classroom.name }} · {{ classroom.unit }}
                </label>
              </div>
            </div>
          </div>

          <p v-else class="text-sm text-on-surface-variant">
            Tetapkan unit guru terlebih dahulu sebelum mengatur penugasan kelas.
          </p>

          <p v-if="assignmentError" class="text-error text-xs">{{ assignmentError }}</p>
          <button
            type="button"
            class="btn btn--primary btn--sm"
            :disabled="assignmentSaving || !assignableClassrooms.length"
            @click="saveAssignments"
          >
            {{ assignmentSaving ? 'Menyimpan...' : 'Simpan Penugasan' }}
          </button>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <section class="bg-white rounded-xl border border-outline-variant shadow-sm">
            <div class="border-b border-outline-variant px-4 py-3">
              <h3 class="font-bold text-sm">Identitas</h3>
            </div>
            <dl class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
              <div>
                <dt class="text-xs text-on-surface-variant">Nama</dt>
                <dd class="font-bold break-words">{{ teacher.name }}</dd>
              </div>
              <div>
                <dt class="text-xs text-on-surface-variant">Status</dt>
                <dd class="font-bold">{{ statusLabel(teacher.status) }}</dd>
              </div>
              <div>
                <dt class="text-xs text-on-surface-variant">Email</dt>
                <dd class="break-all">{{ teacher.email || '-' }}</dd>
              </div>
              <div>
                <dt class="text-xs text-on-surface-variant">Telepon</dt>
                <dd>{{ teacher.phone || '-' }}</dd>
              </div>
              <div>
                <dt class="text-xs text-on-surface-variant">NIP</dt>
                <dd>{{ teacher.nip || '-' }}</dd>
              </div>
              <div>
                <dt class="text-xs text-on-surface-variant">Terdaftar</dt>
                <dd>{{ formatDate(teacher.created_at) }}</dd>
              </div>
            </dl>
          </section>

          <section class="bg-white rounded-xl border border-outline-variant shadow-sm">
            <div class="border-b border-outline-variant px-4 py-3">
              <h3 class="font-bold text-sm">Unit & Jabatan</h3>
            </div>
            <div v-if="teacher.units.length" class="divide-y divide-outline-variant">
              <div v-for="unit in teacher.units" :key="unit.code" class="p-4 flex items-start justify-between gap-3">
                <div>
                  <p class="font-bold text-sm">{{ unit.code }}</p>
                  <p class="text-xs text-on-surface-variant">{{ unit.name }}</p>
                </div>
                <span class="badge bg-surface-container-high text-on-surface">{{ unit.jabatan || 'Tanpa jabatan' }}</span>
              </div>
            </div>
            <p v-else class="p-4 text-sm text-on-surface-variant">Belum ada unit yang ditetapkan.</p>
          </section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <section class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
            <div class="border-b border-outline-variant px-4 py-3 flex items-center justify-between gap-2">
              <h3 class="font-bold text-sm">Kelas Diampu</h3>
              <span class="text-xs text-on-surface-variant">{{ teacher.assignments.length }} penugasan</span>
            </div>
            <div v-if="teacher.assignments.length" class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-surface-container-low">
                  <tr>
                    <th class="p-3 text-left">Mapel</th>
                    <th class="p-3 text-left">Kelas</th>
                    <th class="p-3 text-left">Tahun</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="assignment in teacher.assignments" :key="assignment.id" class="border-t border-outline-variant">
                    <td class="p-3">{{ assignment.subject_name || '-' }}</td>
                    <td class="p-3">
                      {{ assignment.classroom_name || '-' }}
                      <span v-if="assignment.classroom_unit" class="text-xs text-on-surface-variant">· {{ assignment.classroom_unit }}</span>
                    </td>
                    <td class="p-3 text-xs">{{ assignment.academic_year }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="p-4 text-sm text-on-surface-variant">Belum ada penugasan mata pelajaran.</p>
          </section>

          <section class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
            <div class="border-b border-outline-variant px-4 py-3 flex items-center justify-between gap-2">
              <h3 class="font-bold text-sm">Wali Kelas</h3>
              <span class="text-xs text-on-surface-variant">{{ teacher.wali_classrooms.length }} kelas</span>
            </div>
            <div v-if="teacher.wali_classrooms.length" class="divide-y divide-outline-variant">
              <div v-for="classroom in teacher.wali_classrooms" :key="classroom.id" class="p-4 flex items-center justify-between gap-3">
                <div>
                  <p class="font-bold text-sm">{{ classroom.name }}</p>
                  <p class="text-xs text-on-surface-variant">{{ classroom.unit }} · {{ classroom.academic_year }}</p>
                </div>
                <span class="badge bg-tertiary-fixed text-on-surface">Wali</span>
              </div>
            </div>
            <p v-else class="p-4 text-sm text-on-surface-variant">Belum menjadi wali kelas.</p>
          </section>
        </div>

        <section v-if="canViewAttendance" id="rekap-absensi" class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
          <div class="border-b border-outline-variant px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <h3 class="font-bold text-sm">Rekap Absensi Guru</h3>
              <p class="text-xs text-on-surface-variant mt-1">Rekap bulanan kehadiran guru.</p>
            </div>
            <input v-model="attendanceMonth" type="month" class="input w-full sm:w-44" @change="loadAttendance" />
          </div>

          <div v-if="attendanceLoading" class="p-6 text-center text-sm text-on-surface-variant">Memuat absensi...</div>
          <template v-else>
            <div class="p-4 grid grid-cols-2 sm:grid-cols-4 gap-2">
              <div v-for="item in attendanceSummary" :key="item.key" class="rounded-lg border border-outline-variant bg-surface-container-low p-3">
                <p class="text-xs text-on-surface-variant">{{ item.label }}</p>
                <p class="font-bold text-lg">{{ item.value }}</p>
              </div>
            </div>

            <p v-if="attendanceError" class="px-4 pb-3 text-error text-xs">{{ attendanceError }}</p>

            <div v-if="attendanceRecords.length" class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-surface-container-low">
                  <tr>
                    <th class="p-3 text-left">Tanggal</th>
                    <th class="p-3 text-left">Masuk</th>
                    <th class="p-3 text-left">Keluar</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Catatan</th>
                    <th v-if="teacher.permissions.manage_attendance" class="p-3 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="record in attendanceRecords" :key="record.id" class="border-t border-outline-variant">
                    <td class="p-3">{{ formatDate(record.date) }}</td>
                    <td class="p-3">{{ timeValue(record.check_in) }}</td>
                    <td class="p-3">{{ timeValue(record.check_out) }}</td>
                    <td class="p-3">
                      <span class="badge" :class="attendanceClass(record.status)">{{ record.status }}</span>
                    </td>
                    <td class="p-3 text-xs max-w-64">{{ record.notes || '-' }}</td>
                    <td v-if="teacher.permissions.manage_attendance" class="p-3 text-right">
                      <button type="button" class="btn btn--ghost btn--sm" @click="openAttendanceForm(record)">Edit</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="px-4 pb-4 text-sm text-on-surface-variant">Belum ada catatan absensi pada bulan ini.</p>

            <div v-if="editingAttendanceId" class="border-t border-outline-variant p-4 space-y-3 bg-surface-container-low">
              <div class="flex items-center justify-between gap-2">
                <h4 class="font-bold text-sm">Edit Absensi</h4>
                <button type="button" class="btn btn--ghost btn--sm" @click="editingAttendanceId = null">Batal</button>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <label class="space-y-1 text-xs font-bold">Tanggal
                  <input v-model="attendanceForm.date" type="date" class="input" />
                </label>
                <label class="space-y-1 text-xs font-bold">Masuk
                  <input v-model="attendanceForm.check_in" type="time" class="input" />
                </label>
                <label class="space-y-1 text-xs font-bold">Keluar
                  <input v-model="attendanceForm.check_out" type="time" class="input" />
                </label>
                <label class="space-y-1 text-xs font-bold">Status
                  <select v-model="attendanceForm.status" class="input">
                    <option value="hadir">Hadir</option>
                    <option value="izin">Izin</option>
                    <option value="sakit">Sakit</option>
                    <option value="alfa">Alfa</option>
                    <option value="cuti">Cuti</option>
                  </select>
                </label>
                <label class="space-y-1 text-xs font-bold sm:col-span-2 lg:col-span-4">Catatan
                  <textarea v-model="attendanceForm.notes" class="input min-h-20" />
                </label>
              </div>
              <p v-if="attendanceFormError" class="text-error text-xs">{{ attendanceFormError }}</p>
              <button type="button" class="btn btn--primary btn--sm" :disabled="attendanceSaving" @click="saveAttendance">
                {{ attendanceSaving ? 'Menyimpan...' : 'Simpan Absensi' }}
              </button>
            </div>
          </template>
        </section>
      </template>

      <Transition name="toast">
        <div v-if="toast" class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-full text-sm font-bold border border-outline-variant shadow-sm" :class="toastType === 'error' ? 'bg-error-container text-on-error-container' : 'bg-primary-fixed text-on-surface'">
          {{ toast }}
        </div>
      </Transition>
    </div>
  </AppShell>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppShell from '../../Components/AppShell.vue'
import BackButton from '../../Components/BackButton.vue'
import { useAuth } from '../../composables/useAuth.js'

const route = useRoute()
const { can } = useAuth()
const teacher = ref(null)
const options = ref({ units: [], subjects: [], classrooms: [], academic_year: '2026/2027' })
const loading = ref(false)
const error = ref('')
const toast = ref('')
const toastType = ref('success')

const showProfileForm = ref(false)
const profileForm = ref({ name: '', email: '', phone: '', nip: '', is_active: true, password: '' })
const profileError = ref('')
const profileSaving = ref(false)
const photoUploading = ref(false)

const showUnitsForm = ref(false)
const unitsForm = ref({ selected: [], jabatan: {} })
const unitsError = ref('')
const unitsSaving = ref(false)

const showAssignmentForm = ref(false)
const assignmentYear = ref('2026/2027')
const assignmentRows = ref([])
const waliClassroomIds = ref([])
const assignmentError = ref('')
const assignmentSaving = ref(false)
let rowKey = 0

const attendanceMonth = ref(new Date().toISOString().slice(0, 7))
const attendanceRecords = ref([])
const attendanceData = ref({ summary: { total: 0, hadir: 0, izin: 0, sakit: 0, alfa: 0 } })
const attendanceLoading = ref(false)
const attendanceError = ref('')
const editingAttendanceId = ref(null)
const attendanceForm = ref({ date: '', check_in: '', check_out: '', status: 'hadir', notes: '' })
const attendanceFormError = ref('')
const attendanceSaving = ref(false)

const isPrincipal = computed(() => route.path.startsWith('/app/kepala'))
const canViewAttendance = computed(() => can('attendance.teacher.view'))
const backFallback = computed(() => (isPrincipal.value ? '/app/kepala/guru' : '/app/admin/teachers'))
const assignableClassrooms = computed(() => {
  const codes = (teacher.value?.units || []).map((unit) => unit.code)
  return options.value.classrooms.filter((classroom) => codes.includes(classroom.unit))
})
const attendanceSummary = computed(() => {
  const summary = attendanceData.value.summary || {}
  return [
    { key: 'total', label: 'Total', value: summary.total || 0 },
    { key: 'hadir', label: 'Hadir', value: summary.hadir || 0 },
    { key: 'izin', label: 'Izin', value: summary.izin || 0 },
    { key: 'sakit', label: 'Sakit', value: summary.sakit || 0 },
  ]
})

function notify(message, type = 'success') {
  toast.value = message
  toastType.value = type
  setTimeout(() => {
    toast.value = ''
  }, 3200)
}

async function readError(response, fallback) {
  const payload = await response.json().catch(() => ({}))
  return payload.message || Object.values(payload.errors || {}).flat().join(', ') || fallback
}

async function loadTeacher() {
  const response = await fetch(`/api/teacher-directory/${route.params.id}`)
  if (!response.ok) throw new Error(await readError(response, 'Gagal memuat detail guru.'))
  teacher.value = await response.json()
}

async function loadOptions() {
  try {
    const response = await fetch('/api/teacher-directory/options')
    if (!response.ok) return
    options.value = await response.json()
  } catch {
    // Options are only needed for editing; the profile remains readable.
  }
}

async function loadAttendance() {
  if (!canViewAttendance.value) return

  attendanceLoading.value = true
  attendanceError.value = ''

  try {
    const response = await fetch(`/api/teacher-directory/${route.params.id}/attendance?month=${attendanceMonth.value}`)
    if (!response.ok) throw new Error(await readError(response, 'Gagal memuat absensi.'))
    const payload = await response.json()
    attendanceRecords.value = payload.records || []
    attendanceData.value = payload
  } catch (loadError) {
    attendanceError.value = loadError.message
    attendanceRecords.value = []
  } finally {
    attendanceLoading.value = false
  }
}

async function loadPage() {
  loading.value = true
  error.value = ''

  try {
    await loadTeacher()
  } catch (loadError) {
    error.value = loadError.message
  } finally {
    loading.value = false
  }

  if (teacher.value) {
    await Promise.all([loadOptions(), canViewAttendance.value ? loadAttendance() : Promise.resolve()])
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

function attendanceClass(status) {
  if (status === 'hadir') return 'bg-primary-fixed text-on-surface'
  if (status === 'alfa') return 'bg-error-container text-on-error-container'
  return 'bg-surface-container-high text-on-surface'
}

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(String(value).slice(0, 10))
  if (Number.isNaN(date.getTime())) return String(value)
  return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(date)
}

function timeValue(value) {
  return value ? String(value).slice(0, 5) : '-'
}

function openProfileForm() {
  profileForm.value = {
    name: teacher.value.name || '',
    email: teacher.value.email || '',
    phone: teacher.value.phone || '',
    nip: teacher.value.nip || '',
    is_active: Boolean(teacher.value.is_active),
    password: '',
  }
  profileError.value = ''
  showProfileForm.value = true
}

async function saveProfile() {
  profileError.value = ''

  if (!profileForm.value.name.trim() || !profileForm.value.email.trim()) {
    profileError.value = 'Nama dan email wajib diisi.'
    return
  }

  profileSaving.value = true

  try {
    const payload = {
      name: profileForm.value.name.trim(),
      email: profileForm.value.email.trim(),
      phone: profileForm.value.phone.trim() || null,
      nip: profileForm.value.nip.trim() || null,
      is_active: Boolean(profileForm.value.is_active),
    }

    if (profileForm.value.password) payload.password = profileForm.value.password

    const response = await fetch(`/api/teacher-directory/${teacher.value.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })

    if (!response.ok) throw new Error(await readError(response, 'Gagal menyimpan profil guru.'))

    teacher.value = await response.json()
    showProfileForm.value = false
    notify('Profil guru diperbarui.')
  } catch (saveError) {
    profileError.value = saveError.message
  } finally {
    profileSaving.value = false
  }
}

async function uploadPhoto(event) {
  const file = event.target.files?.[0]
  if (!file) return

  photoUploading.value = true

  try {
    const body = new FormData()
    body.append('file', file)

    const response = await fetch(`/api/teacher-directory/${teacher.value.id}/photo`, {
      method: 'POST',
      body,
    })

    if (!response.ok) throw new Error(await readError(response, 'Gagal mengunggah foto.'))
    teacher.value = await response.json()
    notify('Foto guru diperbarui.')
  } catch (uploadError) {
    notify(uploadError.message, 'error')
  } finally {
    photoUploading.value = false
    event.target.value = ''
  }
}

function openUnitsForm() {
  const selected = []
  const jabatan = {}

  for (const unit of teacher.value.units) {
    selected.push(unit.code)
    jabatan[unit.code] = unit.jabatan || ''
  }

  unitsForm.value = { selected, jabatan }
  unitsError.value = ''
  showUnitsForm.value = true
}

async function saveUnits() {
  unitsError.value = ''
  unitsSaving.value = true

  try {
    const payload = {
      units: unitsForm.value.selected.map((code) => ({
        code,
        jabatan: unitsForm.value.jabatan[code]?.trim() || null,
      })),
    }

    const response = await fetch(`/api/teacher-directory/${teacher.value.id}/units`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })

    if (!response.ok) throw new Error(await readError(response, 'Gagal menyimpan unit guru.'))

    teacher.value = await response.json()
    showUnitsForm.value = false
    notify('Unit dan jabatan guru diperbarui.')
    await loadAttendance()
  } catch (saveError) {
    unitsError.value = saveError.message
  } finally {
    unitsSaving.value = false
  }
}

function addAssignmentRow(subjectId = '', classroomId = '') {
  assignmentRows.value.push({ key: ++rowKey, subject_id: subjectId ? String(subjectId) : '', classroom_id: classroomId ? String(classroomId) : '' })
}

function removeAssignmentRow(index) {
  assignmentRows.value.splice(index, 1)
}

function syncAssignmentRows() {
  assignmentRows.value = []
  const filled = (teacher.value?.assignments || []).filter((assignment) => assignment.academic_year === assignmentYear.value)

  for (const assignment of filled) {
    addAssignmentRow(assignment.subject_id, assignment.classroom_id)
  }

  if (!assignmentRows.value.length) addAssignmentRow()
}

function openAssignmentForm() {
  assignmentYear.value = options.value.academic_year || '2026/2027'
  waliClassroomIds.value = (teacher.value.wali_classrooms || [])
    .map((classroom) => classroom.id)
    .filter((id) => assignableClassrooms.value.some((classroom) => classroom.id === id))
  assignmentError.value = ''
  syncAssignmentRows()
  showAssignmentForm.value = true
}

async function saveAssignments() {
  assignmentError.value = ''
  const assignments = assignmentRows.value
    .filter((row) => row.subject_id && row.classroom_id)
    .map((row) => ({ subject_id: Number(row.subject_id), classroom_id: Number(row.classroom_id) }))

  if (assignmentRows.value.some((row) => (!row.subject_id && row.classroom_id) || (row.subject_id && !row.classroom_id))) {
    assignmentError.value = 'Lengkapi mapel dan kelas pada setiap baris, atau hapus baris yang kosong.'
    return
  }

  assignmentSaving.value = true

  try {
    const response = await fetch(`/api/teacher-directory/${teacher.value.id}/assignments`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        academic_year: assignmentYear.value,
        assignments,
        wali_classroom_ids: waliClassroomIds.value.map(Number),
      }),
    })

    if (!response.ok) throw new Error(await readError(response, 'Gagal menyimpan penugasan guru.'))

    teacher.value = await response.json()
    showAssignmentForm.value = false
    notify('Penugasan guru diperbarui.')
  } catch (saveError) {
    assignmentError.value = saveError.message
  } finally {
    assignmentSaving.value = false
  }
}

function openAttendanceForm(record) {
  editingAttendanceId.value = record.id
  attendanceFormError.value = ''
  attendanceForm.value = {
    date: String(record.date).slice(0, 10),
    check_in: timeValue(record.check_in) === '-' ? '' : timeValue(record.check_in),
    check_out: timeValue(record.check_out) === '-' ? '' : timeValue(record.check_out),
    status: record.status || 'hadir',
    notes: record.notes || '',
  }
}

async function saveAttendance() {
  attendanceFormError.value = ''
  attendanceSaving.value = true

  try {
    const response = await fetch(`/api/teacher-directory/${teacher.value.id}/attendance/${editingAttendanceId.value}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        date: attendanceForm.value.date,
        check_in: attendanceForm.value.check_in || null,
        check_out: attendanceForm.value.check_out || null,
        status: attendanceForm.value.status,
        notes: attendanceForm.value.notes || null,
      }),
    })

    if (!response.ok) throw new Error(await readError(response, 'Gagal menyimpan absensi guru.'))

    editingAttendanceId.value = null
    await loadAttendance()
    notify('Absensi guru diperbarui.')
  } catch (saveError) {
    attendanceFormError.value = saveError.message
  } finally {
    attendanceSaving.value = false
  }
}

watch(() => route.params.id, () => loadPage())

onMounted(loadPage)
</script>
