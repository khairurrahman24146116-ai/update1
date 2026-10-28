<template>
  <AppShell title="Akhlak & Izin Anak">
    <div v-if="loading" class="p-8 text-center text-sm text-on-surface-variant">Memuat data kesejahteraan anak...</div>
    <div v-else-if="error" class="bg-error-container text-on-error-container rounded-xl border border-outline-variant p-4 text-sm">{{ error }}</div>
    <EmptyState v-else-if="!students.length" icon="child_care" title="Belum ada data anak" description="Belum ada anak LPI yang terhubung ke akun wali murid ini." />
    <div v-else class="space-y-4">
      <div class="bg-white rounded-xl border border-outline-variant shadow-sm p-4 flex flex-wrap items-end gap-3">
        <label class="flex-1 min-w-48">
          <span class="text-xs font-bold text-on-surface-variant">Pilih Anak</span>
          <select v-model="selectedStudentId" class="input mt-1" @change="loadOverview">
            <option v-for="student in students" :key="student.id" :value="student.id">{{ student.name }} - {{ student.classroom_name }}</option>
          </select>
        </label>
      </div>

      <template v-if="eligibility">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
          <div class="bg-white rounded-xl border border-outline-variant shadow-sm p-4">
            <div class="text-xs text-on-surface-variant">Status Kelayakan</div>
            <div class="text-xl font-black mt-1" :class="statusTextClass(eligibility.status_class)">{{ eligibility.status_label }}</div>
            <div class="text-xs text-on-surface-variant mt-1">{{ eligibility.period.academic_year }} - {{ eligibility.period.semester }}</div>
          </div>
          <div class="bg-white rounded-xl border border-outline-variant shadow-sm p-4">
            <div class="text-xs text-on-surface-variant">Kehadiran</div>
            <div class="text-2xl font-black mt-1">{{ eligibility.attendance.percent }}%</div>
            <div class="text-xs text-on-surface-variant mt-1">{{ eligibility.attendance.hadir }} dari {{ eligibility.attendance.total }} pertemuan</div>
          </div>
          <div class="bg-white rounded-xl border border-outline-variant shadow-sm p-4">
            <div class="text-xs text-on-surface-variant">Poin Pelanggaran</div>
            <div class="text-2xl font-black mt-1">{{ eligibility.violations.points }}</div>
            <div class="text-xs text-on-surface-variant mt-1">Batas peringatan {{ eligibility.violations.warning_points }}</div>
          </div>
          <div class="bg-white rounded-xl border border-outline-variant shadow-sm p-4">
            <div class="text-xs text-on-surface-variant">Izin Bulan Ini</div>
            <div class="text-2xl font-black mt-1">{{ eligibility.permits.month }} / {{ eligibility.permits.month_limit }}</div>
            <div class="text-xs text-on-surface-variant mt-1">Semester {{ eligibility.permits.semester }} / {{ eligibility.permits.semester_limit }}</div>
          </div>
        </div>

        <div v-if="eligibility.reasons.length" class="bg-white rounded-xl border border-outline-variant shadow-sm p-4">
          <h3 class="font-bold mb-2">Catatan Kelayakan</h3>
          <ul class="space-y-2">
            <li v-for="reason in eligibility.reasons" :key="reason.code" class="text-sm flex items-start gap-2">
              <span class="material-symbols-outlined text-[18px]" :class="statusTextClass(reason.severity)">{{ reason.severity === 'danger' ? 'error' : 'warning' }}</span>
              <span>{{ reason.label }}</span>
            </li>
          </ul>
        </div>
      </template>

      <section class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="border-b border-outline-variant px-4 py-3">
          <h3 class="font-bold text-sm">Riwayat Akhlak</h3>
        </div>
        <div v-if="akhlak.length" class="divide-y divide-outline-variant">
          <article v-for="record in akhlak" :key="record.id" class="p-4 space-y-1">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="font-bold text-sm">{{ record.type_label }}<span v-if="record.severity_label"> - {{ record.severity_label }}</span></div>
              <span class="badge" :class="record.status === 'active' ? 'bg-primary-fixed text-on-surface' : 'bg-surface-container-high text-on-surface-variant'">{{ record.status_label }}</span>
            </div>
            <div class="text-xs text-on-surface-variant">{{ formatDate(record.occurred_at) }}<span v-if="record.points"> - {{ record.points }} poin</span></div>
            <p class="text-sm">{{ record.description }}</p>
            <p v-if="record.correction_reason" class="text-xs text-on-surface-variant">Catatan koreksi: {{ record.correction_reason }}</p>
          </article>
        </div>
        <p v-else class="p-4 text-sm text-on-surface-variant">Belum ada catatan akhlak.</p>
      </section>

      <section class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="border-b border-outline-variant px-4 py-3">
          <h3 class="font-bold text-sm">Riwayat Izin</h3>
        </div>
        <div v-if="permits.length" class="divide-y divide-outline-variant">
          <article v-for="permit in permits" :key="permit.id" class="p-4 space-y-1">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="font-bold text-sm">{{ permit.type_label }}</div>
              <span class="badge" :class="permitStatusClass(permit.status)">{{ permit.status_label }}</span>
            </div>
            <div class="text-xs text-on-surface-variant">{{ formatDate(permit.start_date) }} s.d. {{ formatDate(permit.end_date) }}</div>
            <p class="text-sm">{{ permit.reason }}</p>
          </article>
        </div>
        <p v-else class="p-4 text-sm text-on-surface-variant">Belum ada pengajuan izin.</p>
      </section>
    </div>
  </AppShell>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import AppShell from '../../Components/AppShell.vue'
import EmptyState from '../../Components/EmptyState.vue'

const loading = ref(true)
const error = ref('')
const students = ref([])
const selectedStudentId = ref('')
const eligibility = ref(null)
const akhlak = ref([])
const permits = ref([])

onMounted(loadOverview)

async function loadOverview() {
  loading.value = true
  error.value = ''
  try {
    const params = new URLSearchParams()
    if (selectedStudentId.value) params.set('student_id', selectedStudentId.value)
    const response = await fetch(`/api/welfare/wali/overview?${params.toString()}`)
    const payload = await response.json().catch(() => ({}))
    if (!response.ok) throw new Error(payload.message || 'Gagal memuat data anak.')
    students.value = payload.students || []
    selectedStudentId.value = payload.selected_student_id || ''
    eligibility.value = payload.eligibility
    akhlak.value = payload.akhlak || []
    permits.value = payload.permits || []
  } catch (loadError) {
    error.value = loadError.message
  } finally {
    loading.value = false
  }
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
</script>
