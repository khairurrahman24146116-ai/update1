<template>
  <AppShell title="Kesejahteraan Santri">
    <div v-if="loadingMeta" class="p-8 text-center text-sm text-on-surface-variant">Memuat data kesejahteraan...</div>
    <EmptyState
      v-else-if="!meta.has_lpi"
      icon="diversity_3"
      title="Belum ada kelas LPI"
      description="Modul kesejahteraan santri hanya tersedia untuk kelas LPI yang menjadi tanggung jawab Anda."
    />
    <div v-else class="space-y-4">
      <div v-if="error" class="bg-error-container text-on-error-container rounded-xl border border-outline-variant p-4 text-sm">{{ error }}</div>

      <div class="flex flex-wrap gap-2 border-b border-outline-variant pb-2">
        <button v-for="tab in tabs" :key="tab.key" type="button" class="btn btn--sm" :class="activeTab === tab.key ? 'btn--primary' : 'btn--ghost'" @click="switchTab(tab.key)">
          {{ tab.label }}
        </button>
      </div>

      <section v-if="activeTab === 'akhlak'" class="space-y-4">
        <div class="bg-white rounded-xl border border-outline-variant shadow-sm p-4 space-y-3">
          <div class="flex flex-wrap items-end gap-3">
            <label class="flex-1 min-w-40">
              <span class="text-xs font-bold text-on-surface-variant">Kelas</span>
              <select v-model="filters.classroom_id" class="input mt-1">
                <option value="">Semua kelas LPI</option>
                <option v-for="classroom in meta.classrooms" :key="classroom.id" :value="classroom.id">{{ classroom.name }} - {{ classroom.unit_label }}</option>
              </select>
            </label>
            <label class="flex-1 min-w-40">
              <span class="text-xs font-bold text-on-surface-variant">Santri</span>
              <select v-model="filters.student_id" class="input mt-1">
                <option value="">Semua santri</option>
                <option v-for="student in filteredStudents" :key="student.id" :value="student.id">{{ student.name }} - {{ student.classroom_name }}</option>
              </select>
            </label>
            <label class="min-w-32">
              <span class="text-xs font-bold text-on-surface-variant">Jenis</span>
              <select v-model="filters.type" class="input mt-1">
                <option value="">Semua</option>
                <option value="pelanggaran">Pelanggaran</option>
                <option value="apresiasi">Apresiasi</option>
              </select>
            </label>
            <button type="button" class="btn btn--primary btn--sm" @click="loadAkhlak">Terapkan</button>
            <button v-if="meta.permissions.manage" type="button" class="btn btn--primary btn--sm" @click="openAkhlakForm()">
              <span class="material-symbols-outlined !w-4 !h-4">add</span> Catat Akhlak
            </button>
          </div>
        </div>

        <form v-if="showAkhlakForm" class="bg-white rounded-xl border border-outline-variant shadow-sm p-4 space-y-3" @submit.prevent="saveAkhlak">
          <div class="flex items-center justify-between gap-2">
            <h3 class="font-bold">{{ editing ? 'Koreksi Catatan Akhlak' : 'Catat Akhlak Santri' }}</h3>
            <button type="button" class="btn btn--ghost btn--sm" @click="closeAkhlakForm">Tutup</button>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Santri</span>
              <select v-model="akhlakForm.student_id" class="input mt-1" :disabled="Boolean(editing)" required>
                <option value="" disabled>Pilih santri</option>
                <option v-for="student in formStudents" :key="student.id" :value="student.id">{{ student.name }} - {{ student.classroom_name }}</option>
              </select>
            </label>
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Jenis</span>
              <select v-model="akhlakForm.type" class="input mt-1" required>
                <option value="pelanggaran">Pelanggaran</option>
                <option value="apresiasi">Apresiasi</option>
              </select>
            </label>
            <label v-if="akhlakForm.type === 'pelanggaran'">
              <span class="text-xs font-bold text-on-surface-variant">Tingkat</span>
              <select v-model="akhlakForm.severity" class="input mt-1" required>
                <option value="ringan">Ringan</option>
                <option value="sedang">Sedang</option>
                <option value="berat">Berat</option>
              </select>
            </label>
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Tanggal</span>
              <input v-model="akhlakForm.occurred_at" type="date" class="input mt-1" required />
            </label>
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Kategori</span>
              <input v-model="akhlakForm.category" type="text" class="input mt-1" placeholder="Contoh: Kedisiplinan" />
            </label>
          </div>
          <label class="block">
            <span class="text-xs font-bold text-on-surface-variant">Deskripsi</span>
            <textarea v-model="akhlakForm.description" class="input mt-1 min-h-24" required placeholder="Uraikan kejadian secara singkat."></textarea>
          </label>
          <label class="block">
            <span class="text-xs font-bold text-on-surface-variant">Catatan internal (tidak tampil ke wali)</span>
            <textarea v-model="akhlakForm.internal_note" class="input mt-1 min-h-20" placeholder="Opsional"></textarea>
          </label>
          <label v-if="editing" class="block">
            <span class="text-xs font-bold text-on-surface-variant">Alasan koreksi</span>
            <textarea v-model="akhlakForm.correction_reason" class="input mt-1 min-h-20" required placeholder="Wajib diisi untuk audit."></textarea>
          </label>
          <div class="flex justify-end gap-2">
            <button type="button" class="btn btn--ghost" @click="closeAkhlakForm">Batal</button>
            <button type="submit" class="btn btn--primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
          </div>
        </form>

        <div v-if="loadingAkhlak" class="p-6 text-center text-sm text-on-surface-variant">Memuat catatan akhlak...</div>
        <div v-else-if="akhlak.length" class="space-y-3">
          <article v-for="record in akhlak" :key="record.id" class="bg-white rounded-xl border border-outline-variant shadow-sm p-4 space-y-2">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div>
                <div class="font-bold">{{ record.student_name }} <span class="text-xs font-normal text-on-surface-variant">- {{ record.classroom_name }}</span></div>
                <div class="text-xs text-on-surface-variant">{{ formatDate(record.occurred_at) }} - {{ record.type_label }}<span v-if="record.severity_label"> - {{ record.severity_label }}</span></div>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <span v-if="record.points" class="badge bg-tertiary-fixed text-on-surface">{{ record.points }} poin</span>
                <span class="badge" :class="record.status === 'active' ? 'bg-primary-fixed text-on-surface' : 'bg-surface-container-high text-on-surface-variant'">{{ record.status_label }}</span>
              </div>
            </div>
            <p class="text-sm">{{ record.description }}</p>
            <p v-if="record.category" class="text-xs text-on-surface-variant">Kategori: {{ record.category }}</p>
            <p v-if="record.internal_note" class="text-xs text-on-surface-variant">Catatan internal: {{ record.internal_note }}</p>
            <p v-if="record.correction_reason" class="text-xs text-on-surface-variant">Alasan koreksi/pembatalan: {{ record.correction_reason }}</p>
            <div class="flex flex-wrap gap-2 pt-1">
              <button v-if="record.can_edit" type="button" class="btn btn--ghost btn--sm" @click="openAkhlakForm(record)">Koreksi</button>
              <button v-if="record.can_cancel" type="button" class="btn btn--ghost btn--sm text-error" @click="cancelAkhlak(record)">Batalkan</button>
            </div>
          </article>
        </div>
        <EmptyState v-else icon="checklist" title="Belum ada catatan" description="Belum ada catatan akhlak pada filter ini." />
      </section>

      <section v-if="activeTab === 'izin'" class="space-y-4">
        <div class="bg-white rounded-xl border border-outline-variant shadow-sm p-4 flex flex-wrap items-end gap-3">
          <label class="flex-1 min-w-40">
            <span class="text-xs font-bold text-on-surface-variant">Kelas</span>
            <select v-model="filters.classroom_id" class="input mt-1">
              <option value="">Semua kelas LPI</option>
              <option v-for="classroom in meta.classrooms" :key="classroom.id" :value="classroom.id">{{ classroom.name }} - {{ classroom.unit_label }}</option>
            </select>
          </label>
          <label class="min-w-32">
            <span class="text-xs font-bold text-on-surface-variant">Status</span>
            <select v-model="permitStatus" class="input mt-1">
              <option value="">Semua</option>
              <option value="pending">Menunggu</option>
              <option value="approved">Disetujui</option>
              <option value="rejected">Ditolak</option>
              <option value="cancelled">Dibatalkan</option>
            </select>
          </label>
          <button type="button" class="btn btn--primary btn--sm" @click="loadPermits">Terapkan</button>
          <button v-if="meta.permissions.manage" type="button" class="btn btn--primary btn--sm" @click="showPermitForm = !showPermitForm">
            <span class="material-symbols-outlined !w-4 !h-4">add</span> Ajukan Izin
          </button>
        </div>

        <form v-if="showPermitForm" class="bg-white rounded-xl border border-outline-variant shadow-sm p-4 space-y-3" @submit.prevent="savePermit">
          <h3 class="font-bold">Ajukan Izin Santri</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Santri</span>
              <select v-model="permitForm.student_id" class="input mt-1" required>
                <option value="" disabled>Pilih santri</option>
                <option v-for="student in filteredStudents" :key="student.id" :value="student.id">{{ student.name }} - {{ student.classroom_name }}</option>
              </select>
            </label>
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Jenis</span>
              <select v-model="permitForm.type" class="input mt-1" required>
                <option value="izin">Izin</option>
                <option value="sakit">Sakit</option>
              </select>
            </label>
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Mulai</span>
              <input v-model="permitForm.start_date" type="date" class="input mt-1" required />
            </label>
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Selesai</span>
              <input v-model="permitForm.end_date" type="date" class="input mt-1" required />
            </label>
          </div>
          <label class="block">
            <span class="text-xs font-bold text-on-surface-variant">Alasan</span>
            <textarea v-model="permitForm.reason" class="input mt-1 min-h-20" required placeholder="Alasan pengajuan izin."></textarea>
          </label>
          <div class="flex justify-end gap-2">
            <button type="button" class="btn btn--ghost" @click="showPermitForm = false">Batal</button>
            <button type="submit" class="btn btn--primary" :disabled="saving">{{ saving ? 'Mengirim...' : 'Ajukan' }}</button>
          </div>
        </form>

        <div v-if="loadingPermits" class="p-6 text-center text-sm text-on-surface-variant">Memuat izin santri...</div>
        <div v-else-if="permits.length" class="space-y-3">
          <article v-for="permit in permits" :key="permit.id" class="bg-white rounded-xl border border-outline-variant shadow-sm p-4 space-y-2">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div>
                <div class="font-bold">{{ permit.student_name }} <span class="text-xs font-normal text-on-surface-variant">- {{ permit.classroom_name }}</span></div>
                <div class="text-xs text-on-surface-variant">{{ permit.type_label }} - {{ formatDate(permit.start_date) }} s.d. {{ formatDate(permit.end_date) }}</div>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <span v-if="permit.is_override" class="badge bg-tertiary-fixed text-on-surface">Override</span>
                <span class="badge" :class="permitStatusClass(permit.status)">{{ permit.status_label }}</span>
              </div>
            </div>
            <p class="text-sm">{{ permit.reason }}</p>
            <p v-if="permit.decision_note" class="text-xs text-on-surface-variant">Catatan keputusan: {{ permit.decision_note }}</p>
            <p v-if="permit.override_reason" class="text-xs text-on-surface-variant">Alasan override: {{ permit.override_reason }}</p>
            <p class="text-xs text-on-surface-variant">Diajukan oleh {{ permit.requested_by_name || '-' }}<span v-if="permit.decided_by_name"> - diputuskan oleh {{ permit.decided_by_name }}</span></p>
            <div class="flex flex-wrap gap-2 pt-1">
              <button v-if="permit.can_approve" type="button" class="btn btn--primary btn--sm" @click="approvePermit(permit)">Setujui</button>
              <button v-if="permit.can_reject" type="button" class="btn btn--ghost btn--sm" @click="rejectPermit(permit)">Tolak</button>
              <button v-if="permit.can_cancel" type="button" class="btn btn--ghost btn--sm text-error" @click="cancelPermit(permit)">Batalkan</button>
            </div>
          </article>
        </div>
        <EmptyState v-else icon="event_busy" title="Belum ada izin" description="Belum ada pengajuan izin pada filter ini." />
      </section>

      <section v-if="activeTab === 'kelayakan'" class="space-y-4">
        <div class="bg-white rounded-xl border border-outline-variant shadow-sm p-4 flex flex-wrap items-end gap-3">
          <label class="flex-1 min-w-48">
            <span class="text-xs font-bold text-on-surface-variant">Santri</span>
            <select v-model="eligibilityStudentId" class="input mt-1" @change="loadEligibility(eligibilityStudentId)">
              <option value="">Pilih santri</option>
              <option v-for="student in meta.students" :key="student.id" :value="student.id">{{ student.name }} - {{ student.classroom_name }}</option>
            </select>
          </label>
          <button type="button" class="btn btn--primary btn--sm" @click="loadEligibility(eligibilityStudentId)">Muat Kelayakan</button>
        </div>

        <div v-if="loadingEligibility" class="p-6 text-center text-sm text-on-surface-variant">Menghitung kelayakan...</div>
        <template v-else-if="eligibility">
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-outline-variant shadow-sm p-4">
              <div class="text-xs text-on-surface-variant">Status Kelayakan</div>
              <div class="text-xl font-black mt-1" :class="statusTextClass(eligibility.status_class)">{{ eligibility.status_label }}</div>
              <div class="text-xs text-on-surface-variant mt-1">{{ eligibility.period.academic_year }} - {{ eligibility.period.semester }}</div>
            </div>
            <div class="bg-white rounded-xl border border-outline-variant shadow-sm p-4">
              <div class="text-xs text-on-surface-variant">Kehadiran</div>
              <div class="text-2xl font-black mt-1">{{ eligibility.attendance.percent }}%</div>
              <div class="text-xs text-on-surface-variant mt-1">{{ eligibility.attendance.hadir }} dari {{ eligibility.attendance.total }} pertemuan - minimum {{ eligibility.attendance.minimum_percent }}%</div>
            </div>
            <div class="bg-white rounded-xl border border-outline-variant shadow-sm p-4">
              <div class="text-xs text-on-surface-variant">Poin Pelanggaran</div>
              <div class="text-2xl font-black mt-1">{{ eligibility.violations.points }}</div>
              <div class="text-xs text-on-surface-variant mt-1">Peringatan {{ eligibility.violations.warning_points }} - maksimum {{ eligibility.violations.max_points }}</div>
            </div>
            <div class="bg-white rounded-xl border border-outline-variant shadow-sm p-4">
              <div class="text-xs text-on-surface-variant">Izin</div>
              <div class="text-2xl font-black mt-1">{{ eligibility.permits.month }} / {{ eligibility.permits.month_limit }}</div>
              <div class="text-xs text-on-surface-variant mt-1">Bulan ini - semester {{ eligibility.permits.semester }} / {{ eligibility.permits.semester_limit }}</div>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl border border-outline-variant shadow-sm p-4">
              <h3 class="font-bold mb-2">Faktor Penilaian</h3>
              <ul v-if="eligibility.reasons.length" class="space-y-2">
                <li v-for="reason in eligibility.reasons" :key="reason.code" class="text-sm flex items-start gap-2">
                  <span class="material-symbols-outlined text-[18px]" :class="statusTextClass(reason.severity)">{{ reason.severity === 'danger' ? 'error' : 'warning' }}</span>
                  <span>{{ reason.label }}</span>
                </li>
              </ul>
              <p v-else class="text-sm text-on-surface-variant">Tidak ada faktor risiko. Santri memenuhi seluruh batas.</p>
            </div>
            <div class="bg-white rounded-xl border border-outline-variant shadow-sm p-4">
              <h3 class="font-bold mb-2">Peringatan Sistem</h3>
              <ul v-if="eligibility.alerts.length" class="space-y-2">
                <li v-for="alert in eligibility.alerts" :key="alert.id" class="text-sm flex items-start gap-2">
                  <span class="material-symbols-outlined text-[18px]" :class="statusTextClass(alert.severity)">notifications</span>
                  <span>{{ alert.message }}</span>
                </li>
              </ul>
              <p v-else class="text-sm text-on-surface-variant">Tidak ada peringatan aktif.</p>
            </div>
          </div>
        </template>
        <EmptyState v-else icon="monitoring" title="Pilih santri" description="Pilih santri untuk melihat kelayakan." />
      </section>

      <section v-if="activeTab === 'pengaturan' && meta.permissions.settings" class="space-y-4">
        <form class="bg-white rounded-xl border border-outline-variant shadow-sm p-4 space-y-3" @submit.prevent="saveSettings">
          <h3 class="font-bold">Batas Kelayakan Santri</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Minimum kehadiran (%)</span>
              <input v-model.number="settingsForm.attendance_min_percent" type="number" min="0" max="100" step="0.01" class="input mt-1" required />
            </label>
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Maksimum izin per bulan</span>
              <input v-model.number="settingsForm.permit_max_per_month" type="number" min="0" max="31" class="input mt-1" required />
            </label>
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Maksimum izin per semester</span>
              <input v-model.number="settingsForm.permit_max_per_semester" type="number" min="0" max="100" class="input mt-1" required />
            </label>
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Poin peringatan</span>
              <input v-model.number="settingsForm.violation_warning_points" type="number" min="0" class="input mt-1" required />
            </label>
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Poin maksimum</span>
              <input v-model.number="settingsForm.violation_max_points" type="number" min="0" class="input mt-1" required />
            </label>
            <label class="flex items-center gap-2 mt-6">
              <input v-model="settingsForm.allow_permit_override" type="checkbox" class="h-4 w-4" />
              <span class="text-sm">Izinkan override izin dengan alasan</span>
            </label>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Poin ringan</span>
              <input v-model.number="settingsForm.point_ringan" type="number" min="0" class="input mt-1" required />
            </label>
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Poin sedang</span>
              <input v-model.number="settingsForm.point_sedang" type="number" min="0" class="input mt-1" required />
            </label>
            <label>
              <span class="text-xs font-bold text-on-surface-variant">Poin berat</span>
              <input v-model.number="settingsForm.point_berat" type="number" min="0" class="input mt-1" required />
            </label>
          </div>
          <div class="flex justify-end">
            <button type="submit" class="btn btn--primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan Pengaturan' }}</button>
          </div>
        </form>
      </section>
    </div>
  </AppShell>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import AppShell from '../../Components/AppShell.vue'
import EmptyState from '../../Components/EmptyState.vue'

const meta = ref({ has_lpi: false, classrooms: [], students: [], settings: {}, permissions: { manage: false, approve: false, settings: false } })
const activeTab = ref('akhlak')
const loadingMeta = ref(true)
const loadingAkhlak = ref(false)
const loadingPermits = ref(false)
const loadingEligibility = ref(false)
const saving = ref(false)
const error = ref('')

const filters = reactive({ classroom_id: '', student_id: '', type: '' })
const permitStatus = ref('')
const akhlak = ref([])
const permits = ref([])
const eligibility = ref(null)
const eligibilityStudentId = ref('')

const showAkhlakForm = ref(false)
const editing = ref(null)
const akhlakForm = reactive({
  student_id: '',
  type: 'pelanggaran',
  severity: 'ringan',
  category: '',
  description: '',
  occurred_at: today(),
  internal_note: '',
  correction_reason: '',
})

const showPermitForm = ref(false)
const permitForm = reactive({ student_id: '', type: 'izin', start_date: today(), end_date: today(), reason: '' })
const settingsForm = reactive({ ...defaultSettings() })

const tabs = computed(() => {
  const items = [
    { key: 'akhlak', label: 'Akhlak & Pelanggaran' },
    { key: 'izin', label: 'Izin Santri' },
    { key: 'kelayakan', label: 'Kelayakan' },
  ]
  if (meta.value.permissions.settings) items.push({ key: 'pengaturan', label: 'Pengaturan' })
  return items
})

const filteredStudents = computed(() => {
  const classroomId = Number(filters.classroom_id || 0)
  return meta.value.students.filter((student) => !classroomId || Number(student.classroom_id) === classroomId)
})

const formStudents = computed(() => {
  const classroomId = Number(filters.classroom_id || 0)
  return meta.value.students.filter((student) => !classroomId || Number(student.classroom_id) === classroomId)
})

watch(() => akhlakForm.type, (type) => {
  if (type === 'apresiasi') akhlakForm.severity = 'ringan'
})

onMounted(async () => {
  await loadMeta()
  if (meta.value.has_lpi) {
    await Promise.all([loadAkhlak(), loadPermits()])
  }
})

async function loadMeta() {
  loadingMeta.value = true
  error.value = ''
  try {
    const response = await fetch('/api/welfare/meta')
    const payload = await response.json().catch(() => ({}))
    if (!response.ok) throw new Error(payload.message || 'Gagal memuat data kesejahteraan.')
    meta.value = payload
    Object.assign(settingsForm, defaultSettings(), payload.settings || {})
    if (payload.students?.length) {
      eligibilityStudentId.value = payload.students[0].id
    }
  } catch (loadError) {
    error.value = loadError.message
  } finally {
    loadingMeta.value = false
  }
}

async function loadAkhlak() {
  loadingAkhlak.value = true
  error.value = ''
  try {
    const params = new URLSearchParams()
    if (filters.classroom_id) params.set('classroom_id', filters.classroom_id)
    if (filters.student_id) params.set('student_id', filters.student_id)
    if (filters.type) params.set('type', filters.type)
    params.set('per_page', '50')
    const response = await fetch(`/api/welfare/akhlak?${params.toString()}`)
    const payload = await response.json().catch(() => ({}))
    if (!response.ok) throw new Error(payload.message || 'Gagal memuat catatan akhlak.')
    akhlak.value = payload.data || []
  } catch (loadError) {
    error.value = loadError.message
  } finally {
    loadingAkhlak.value = false
  }
}

async function loadPermits() {
  loadingPermits.value = true
  error.value = ''
  try {
    const params = new URLSearchParams()
    if (filters.classroom_id) params.set('classroom_id', filters.classroom_id)
    if (permitStatus.value) params.set('status', permitStatus.value)
    params.set('per_page', '50')
    const response = await fetch(`/api/welfare/permits?${params.toString()}`)
    const payload = await response.json().catch(() => ({}))
    if (!response.ok) throw new Error(payload.message || 'Gagal memuat izin santri.')
    permits.value = payload.data || []
  } catch (loadError) {
    error.value = loadError.message
  } finally {
    loadingPermits.value = false
  }
}

async function loadEligibility(studentId) {
  if (!studentId) {
    eligibility.value = null
    return
  }
  loadingEligibility.value = true
  error.value = ''
  try {
    const response = await fetch(`/api/welfare/students/${studentId}/eligibility`)
    const payload = await response.json().catch(() => ({}))
    if (!response.ok) throw new Error(payload.message || 'Gagal menghitung kelayakan.')
    eligibility.value = payload
  } catch (loadError) {
    error.value = loadError.message
    eligibility.value = null
  } finally {
    loadingEligibility.value = false
  }
}

async function switchTab(tab) {
  activeTab.value = tab
  error.value = ''
  if (tab === 'akhlak') await loadAkhlak()
  if (tab === 'izin') await loadPermits()
  if (tab === 'kelayakan' && eligibilityStudentId.value) await loadEligibility(eligibilityStudentId.value)
}

function openAkhlakForm(record = null) {
  editing.value = record
  Object.assign(akhlakForm, {
    student_id: record?.student_id || '',
    type: record?.type || 'pelanggaran',
    severity: record?.severity || 'ringan',
    category: record?.category || '',
    description: record?.description || '',
    occurred_at: record?.occurred_at || today(),
    internal_note: record?.internal_note || '',
    correction_reason: '',
  })
  showAkhlakForm.value = true
}

function closeAkhlakForm() {
  showAkhlakForm.value = false
  editing.value = null
}

async function saveAkhlak() {
  saving.value = true
  error.value = ''
  try {
    const payload = {
      student_id: Number(akhlakForm.student_id),
      type: akhlakForm.type,
      severity: akhlakForm.type === 'pelanggaran' ? akhlakForm.severity : null,
      category: akhlakForm.category || null,
      description: akhlakForm.description,
      occurred_at: akhlakForm.occurred_at,
      internal_note: akhlakForm.internal_note || null,
    }
    const url = editing.value ? `/api/welfare/akhlak/${editing.value.id}` : '/api/welfare/akhlak'
    if (editing.value) payload.correction_reason = akhlakForm.correction_reason
    const response = await fetch(url, {
      method: editing.value ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    if (!response.ok) throw new Error(await responseError(response, 'Gagal menyimpan catatan akhlak.'))
    closeAkhlakForm()
    await loadAkhlak()
    if (eligibilityStudentId.value) await loadEligibility(eligibilityStudentId.value)
  } catch (saveError) {
    error.value = saveError.message
  } finally {
    saving.value = false
  }
}

async function cancelAkhlak(record) {
  const reason = window.prompt('Alasan pembatalan catatan akhlak:')
  if (!reason) return
  saving.value = true
  error.value = ''
  try {
    const response = await fetch(`/api/welfare/akhlak/${record.id}/cancel`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ reason }),
    })
    if (!response.ok) throw new Error(await responseError(response, 'Gagal membatalkan catatan.'))
    await loadAkhlak()
    if (eligibilityStudentId.value) await loadEligibility(eligibilityStudentId.value)
  } catch (cancelError) {
    error.value = cancelError.message
  } finally {
    saving.value = false
  }
}

async function savePermit() {
  saving.value = true
  error.value = ''
  try {
    const response = await fetch('/api/welfare/permits', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        student_id: Number(permitForm.student_id),
        type: permitForm.type,
        start_date: permitForm.start_date,
        end_date: permitForm.end_date,
        reason: permitForm.reason,
      }),
    })
    if (!response.ok) throw new Error(await responseError(response, 'Gagal mengajukan izin.'))
    showPermitForm.value = false
    Object.assign(permitForm, { student_id: '', type: 'izin', start_date: today(), end_date: today(), reason: '' })
    await loadPermits()
  } catch (saveError) {
    error.value = saveError.message
  } finally {
    saving.value = false
  }
}

async function approvePermit(permit) {
  saving.value = true
  error.value = ''
  try {
    let response = await fetch(`/api/welfare/permits/${permit.id}/approve`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({}),
    })
    if (response.status === 422) {
      const payload = await response.json().catch(() => ({}))
      if (payload.errors?.override_reason) {
        const reason = window.prompt('Izin melebihi batas. Alasan override wajib diisi:')
        if (!reason) return
        response = await fetch(`/api/welfare/permits/${permit.id}/approve`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ override_reason: reason }),
        })
      }
    }
    if (!response.ok) throw new Error(await responseError(response, 'Gagal menyetujui izin.'))
    await loadPermits()
    if (eligibilityStudentId.value) await loadEligibility(eligibilityStudentId.value)
  } catch (approveError) {
    error.value = approveError.message
  } finally {
    saving.value = false
  }
}

async function rejectPermit(permit) {
  const note = window.prompt('Catatan penolakan (opsional):', '')
  if (note === null) return
  saving.value = true
  error.value = ''
  try {
    const response = await fetch(`/api/welfare/permits/${permit.id}/reject`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ decision_note: note || null }),
    })
    if (!response.ok) throw new Error(await responseError(response, 'Gagal menolak izin.'))
    await loadPermits()
  } catch (rejectError) {
    error.value = rejectError.message
  } finally {
    saving.value = false
  }
}

async function cancelPermit(permit) {
  const note = window.prompt('Catatan pembatalan (opsional):', '')
  if (note === null) return
  saving.value = true
  error.value = ''
  try {
    const response = await fetch(`/api/welfare/permits/${permit.id}/cancel`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ decision_note: note || null }),
    })
    if (!response.ok) throw new Error(await responseError(response, 'Gagal membatalkan izin.'))
    await loadPermits()
    if (eligibilityStudentId.value) await loadEligibility(eligibilityStudentId.value)
  } catch (cancelError) {
    error.value = cancelError.message
  } finally {
    saving.value = false
  }
}

async function saveSettings() {
  saving.value = true
  error.value = ''
  try {
    const response = await fetch('/api/welfare/settings', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(settingsForm),
    })
    if (!response.ok) throw new Error(await responseError(response, 'Gagal menyimpan pengaturan.'))
    const payload = await response.json()
    Object.assign(settingsForm, defaultSettings(), payload)
  } catch (saveError) {
    error.value = saveError.message
  } finally {
    saving.value = false
  }
}

async function responseError(response, fallback) {
  const payload = await response.json().catch(() => ({}))
  if (payload.errors) {
    const messages = Object.values(payload.errors).flat().filter(Boolean)
    if (messages.length) return messages.join(' ')
  }
  return payload.message || fallback
}

function permitStatusClass(status) {
  return {
    approved: 'bg-primary-fixed text-on-surface',
    pending: 'bg-tertiary-fixed text-on-surface',
    rejected: 'bg-error-container text-on-error-container',
    cancelled: 'bg-surface-container-high text-on-surface-variant',
  }[status] || 'bg-surface-container-high text-on-surface-variant'
}

function statusTextClass(level) {
  return {
    success: 'text-primary',
    warning: 'text-tertiary',
    danger: 'text-error',
  }[level] || 'text-on-surface-variant'
}

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(`${value}T00:00:00`)
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(date)
}

function today() {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
}

function defaultSettings() {
  return {
    attendance_min_percent: 75,
    permit_max_per_month: 3,
    permit_max_per_semester: 12,
    violation_warning_points: 20,
    violation_max_points: 50,
    point_ringan: 5,
    point_sedang: 15,
    point_berat: 30,
    allow_permit_override: true,
  }
}
</script>
