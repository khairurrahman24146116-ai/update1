<template>
  <AppShell title="Dashboard Guru">
    <div class="space-y-4">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 class="font-headline-md text-headline-md">Kelas Hari Ini</h2>
          <p class="text-sm text-on-surface-variant">{{ dayLabel }}, {{ formatDate(data.date) }}</p>
        </div>
        <span v-if="data.summary.unit_labels.length" class="badge bg-primary-fixed text-on-surface">
          {{ data.summary.unit_labels.join(' • ') }}
        </span>
      </div>

      <div v-if="loading" class="p-8 text-center text-sm text-on-surface-variant">Memuat dashboard...</div>
      <div v-else-if="error" class="bg-error-container text-on-error-container rounded-xl border border-outline-variant p-4 text-sm">{{ error }}</div>

      <template v-else>
        <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm text-on-surface-variant">Jadwal mengajar yang harus dilaksanakan hari ini.</p>
            <router-link to="/app/guru/absensi" class="btn btn--primary btn--sm">Buka Absensi</router-link>
          </div>
          <div v-if="data.today_schedules.length" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div v-for="schedule in data.today_schedules" :key="schedule.id" class="border border-outline-variant rounded-lg p-3 bg-surface-container-low space-y-1">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="font-bold">{{ schedule.subject_name }}</span>
                <span class="badge" :class="jenisClass(schedule.subject_jenis)">{{ jenisLabel(schedule.subject_jenis) }}</span>
              </div>
              <p class="text-sm">{{ schedule.classroom_name }} <span class="text-on-surface-variant">• {{ schedule.classroom_label }}</span></p>
              <p class="text-xs text-on-surface-variant">{{ schedule.start_time }} - {{ schedule.end_time }}</p>
            </div>
          </div>
          <p v-else class="text-sm text-on-surface-variant">Tidak ada jadwal mengajar hari ini.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <router-link to="/app/guru/absensi" class="p-5 border border-outline-variant rounded-xl shadow-sm bg-white hover:shadow-md hover:-translate-y-0.5 transition-[transform,box-shadow] group">
            <span class="w-10 h-10 rounded-full bg-secondary-container border border-outline-variant flex items-center justify-center"><span class="material-symbols-outlined !w-5 !h-5 text-primary">fact_check</span></span>
            <div class="font-bold mt-3">Absensi Mengajar</div>
            <div class="text-xs text-on-surface-variant mt-1">{{ data.summary.today_sessions }} sesi hari ini</div>
            <div class="flex flex-wrap gap-1 mt-3">
              <span v-for="label in todayLabels" :key="label" class="badge bg-surface-container-high text-on-surface">{{ label }}</span>
              <span v-if="!todayLabels.length" class="text-xs text-on-surface-variant">Belum ada sesi</span>
            </div>
            <div class="text-xs font-bold text-primary mt-3 group-hover:underline">Buka</div>
          </router-link>

          <router-link to="/app/guru/jadwal" class="p-5 border border-outline-variant rounded-xl shadow-sm bg-white hover:shadow-md hover:-translate-y-0.5 transition-[transform,box-shadow] group">
            <span class="w-10 h-10 rounded-full bg-primary-fixed border border-outline-variant flex items-center justify-center"><span class="material-symbols-outlined !w-5 !h-5 text-primary">calendar_month</span></span>
            <div class="font-bold mt-3">Jadwal Mengajar</div>
            <div class="text-xs text-on-surface-variant mt-1">{{ data.summary.weekly_sessions }} sesi mingguan</div>
            <div class="flex flex-wrap gap-1 mt-3">
              <span v-for="label in weeklyLabels" :key="label" class="badge bg-surface-container-high text-on-surface">{{ label }}</span>
              <span v-if="!weeklyLabels.length" class="text-xs text-on-surface-variant">Belum ada jadwal</span>
            </div>
            <div class="text-xs font-bold text-primary mt-3 group-hover:underline">Lihat</div>
          </router-link>

          <router-link to="/app/guru/nilai" class="p-5 border border-outline-variant rounded-xl shadow-sm bg-white hover:shadow-md hover:-translate-y-0.5 transition-[transform,box-shadow] group">
            <span class="w-10 h-10 rounded-full bg-tertiary-fixed border border-outline-variant flex items-center justify-center"><span class="material-symbols-outlined !w-5 !h-5 text-primary">edit_note</span></span>
            <div class="font-bold mt-3">Input Nilai</div>
            <div class="text-xs text-on-surface-variant mt-1">{{ data.summary.assigned_pairs }} kelas + mapel</div>
            <div class="flex flex-wrap gap-1 mt-3">
              <span v-for="label in scoreLabels" :key="label" class="badge bg-surface-container-high text-on-surface">{{ label }}</span>
              <span v-if="!scoreLabels.length" class="text-xs text-on-surface-variant">Belum ada penugasan</span>
            </div>
            <div class="text-xs font-bold text-primary mt-3 group-hover:underline">Buka</div>
          </router-link>

          <router-link v-if="canManageAkhlak && welfare.has_lpi" to="/app/guru/akhlak" class="p-5 border border-outline-variant rounded-xl shadow-sm bg-white hover:shadow-md hover:-translate-y-0.5 transition-[transform,box-shadow] group">
            <span class="w-10 h-10 rounded-full bg-secondary-container border border-outline-variant flex items-center justify-center"><span class="material-symbols-outlined !w-5 !h-5 text-primary">diversity_3</span></span>
            <div class="font-bold mt-3">Kesejahteraan Santri</div>
            <div class="text-xs text-on-surface-variant mt-1">{{ welfare.students.length }} santri LPI</div>
            <div class="flex flex-wrap gap-1 mt-3">
              <span v-for="classroom in welfare.classrooms.slice(0, 2)" :key="classroom.id" class="badge bg-surface-container-high text-on-surface">{{ classroom.name }}</span>
              <span v-if="welfare.classrooms.length > 2" class="text-xs text-on-surface-variant">+{{ welfare.classrooms.length - 2 }} kelas</span>
            </div>
            <div class="text-xs font-bold text-primary mt-3 group-hover:underline">Buka</div>
          </router-link>
        </div>

        <section class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
          <div class="border-b border-outline-variant px-4 py-3 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-bold text-sm">Kelas & Mapel Saya</h3>
            <span class="text-xs text-on-surface-variant">{{ data.assignments.length }} penugasan</span>
          </div>
          <div v-if="data.assignments.length" class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-surface-container-low">
                <tr>
                  <th class="p-3 text-left">Mapel</th>
                  <th class="p-3 text-left">Jenis</th>
                  <th class="p-3 text-left">Kelas</th>
                  <th class="p-3 text-left">Tahun</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="assignment in data.assignments" :key="`${assignment.classroom_id}:${assignment.subject_id}`" class="border-t border-outline-variant">
                  <td class="p-3 font-bold">{{ assignment.subject_name }}</td>
                  <td class="p-3"><span class="badge" :class="jenisClass(assignment.subject_jenis)">{{ jenisLabel(assignment.subject_jenis) }}</span></td>
                  <td class="p-3">{{ assignment.classroom_name }} <span class="text-xs text-on-surface-variant">• {{ assignment.classroom_label }}</span></td>
                  <td class="p-3 text-xs text-on-surface-variant">{{ assignment.academic_year || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="p-4 text-sm text-on-surface-variant">Belum ada penugasan kelas dan mapel.</p>
        </section>
      </template>
    </div>
  </AppShell>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import AppShell from '../../Components/AppShell.vue'
import { useAuth } from '../../composables/useAuth.js'

const data = ref({
  date: '',
  day_label: '',
  today_schedules: [],
  weekly_schedules: [],
  assignments: [],
  summary: { today_sessions: 0, weekly_sessions: 0, assigned_pairs: 0, unit_labels: [] },
})
const loading = ref(true)
const error = ref('')
const welfare = ref({ has_lpi: false, can_manage_akhlak: false, classrooms: [], students: [] })
const { user } = useAuth()
const canManageAkhlak = computed(() => user.value?.can_manage_akhlak === true)

const dayLabel = computed(() => data.value.day_label || '-')
const todayLabels = computed(() => uniqueLabels(data.value.today_schedules.map((item) => item.classroom_label)))
const weeklyLabels = computed(() => uniqueLabels(data.value.weekly_schedules.map((item) => item.classroom_label)))
const scoreLabels = computed(() => uniqueLabels(data.value.assignments.map((item) => item.classroom_label)))

function uniqueLabels(labels) {
  return [...new Set(labels.filter((label) => label && label !== '-'))]
}

function jenisLabel(jenis) {
  return jenis === 'non_formal' ? 'Non Formal' : 'Formal'
}

function jenisClass(jenis) {
  return jenis === 'non_formal' ? 'bg-tertiary-fixed text-on-surface' : 'bg-primary-fixed text-on-surface'
}

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(`${value}T00:00:00`)
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(date)
}

async function load() {
  loading.value = true
  error.value = ''

  try {
    const welfareRequest = canManageAkhlak.value
      ? fetch('/api/welfare/meta')
        .then((response) => (response.ok ? response.json() : null))
        .catch(() => null)
      : Promise.resolve(null)
    const [response, welfarePayload] = await Promise.all([fetch('/api/guru/dashboard'), welfareRequest])
    const payload = await response.json().catch(() => ({}))
    if (!response.ok) throw new Error(payload.message || 'Gagal memuat dashboard guru.')
    data.value = payload
    welfare.value = welfarePayload || { has_lpi: false, can_manage_akhlak: false, classrooms: [], students: [] }
  } catch (loadError) {
    error.value = loadError.message
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
