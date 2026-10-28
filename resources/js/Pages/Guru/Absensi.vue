<template>
  <AppShell title="Absensi Mengajar">
    <BackButton fallback="/app/guru" />
    <div class="flex flex-wrap gap-3 items-center">
      <select v-model="scheduleId" class="input w-64" @change="selectSchedule">
        <option value="">Pilih sesi</option>
        <option v-for="s in daySchedules" :key="s.id" :value="s.id">{{ s.subject?.name }} - {{ s.classroom?.name }} ({{ s.start_time }}-{{ s.end_time }})</option>
      </select>
      <input v-model="date" type="date" class="input w-48" @change="onDateChange" />
      <button @click="loadSchedules" class="btn btn--primary btn--sm">Muat</button>
    </div>

    <div v-if="loading" class="p-8 text-center text-sm text-on-surface-variant">Memuat...</div>
    <EmptyState v-else-if="!schedule" icon="event_busy" :title="daySchedules.length ? 'Pilih sesi' : 'Tidak ada jadwal pada tanggal ini'" :description="daySchedules.length ? 'Pilih sesi mengajar di atas.' : 'Pilih tanggal lain atau hubungi admin untuk penjadwalan.'" />

    <div v-else class="space-y-6">
      <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm space-y-1">
        <div class="font-bold">{{ schedule.subject?.name }} - {{ schedule.classroom?.name }}</div>
        <div class="text-sm text-on-surface-variant">{{ labelDay(schedule.day_of_week) }} {{ schedule.start_time }}-{{ schedule.end_time }}</div>
      </div>

      <div class="flex flex-wrap gap-3">
        <button v-if="!attToday?.check_in" @click="doCheckIn" class="btn btn--secondary">Check-in</button>
        <span v-else class="badge bg-primary-fixed text-on-surface">Check-in {{ attToday.check_in }}</span>
        <button v-if="attToday?.check_in && !attToday.check_out" @click="doCheckOut" class="btn btn--ghost">Check-out</button>
        <span v-if="attToday?.check_out" class="badge bg-error-container text-on-error-container">Check-out {{ attToday.check_out }}</span>
      </div>

      <div class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
        <div class="px-3 pt-2 sm:hidden text-xs text-on-surface-variant flex items-center gap-1">
          <span class="material-symbols-outlined !w-3.5 !h-3.5 text-[14px]">swipe</span>
          Geser tabel untuk lihat kolom lain
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-[700px] text-sm">
            <thead class="bg-primary text-on-primary">
              <tr>
                <th class="p-2 sm:p-3 text-left">No</th>
                <th class="p-2 sm:p-3 text-left">NIS</th>
                <th class="p-2 sm:p-3 text-left">Siswa</th>
                <th class="p-2 sm:p-3 text-left">Status</th>
                <th class="p-2 sm:p-3 text-left">Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, i) in sheet" :key="row.student.id" class="border-t border-outline-variant transition-colors hover:bg-surface-container-low">
                <td class="p-2 sm:p-3 text-xs sm:text-sm">{{ i + 1 }}</td>
                <td class="p-2 sm:p-3 text-xs sm:text-sm whitespace-nowrap">{{ row.student.nis }}</td>
                <td class="p-2 sm:p-3 text-xs sm:text-sm">{{ row.student.name }}</td>
                <td class="p-2 sm:p-3">
                  <div class="flex flex-wrap items-center gap-1">
                    <button type="button" @click="row.attendance.status = 'hadir'" :aria-pressed="row.attendance.status === 'hadir'" class="px-2 py-1 rounded-full text-xs font-bold transition-colors duration-200" :class="row.attendance.status === 'hadir' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-700 hover:bg-green-200'">Hadir</button>
                    <button type="button" @click="row.attendance.status = 'izin'" :aria-pressed="row.attendance.status === 'izin'" class="px-2 py-1 rounded-full text-xs font-bold transition-colors duration-200" :class="row.attendance.status === 'izin' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-700 hover:bg-blue-200'">Izin</button>
                    <button type="button" @click="row.attendance.status = 'sakit'" :aria-pressed="row.attendance.status === 'sakit'" class="px-2 py-1 rounded-full text-xs font-bold transition-colors duration-200" :class="row.attendance.status === 'sakit' ? 'bg-amber-600 text-white' : 'bg-amber-100 text-amber-700 hover:bg-amber-200'">Sakit</button>
                    <button type="button" @click="row.attendance.status = 'alfa'" :aria-pressed="row.attendance.status === 'alfa'" class="px-2 py-1 rounded-full text-xs font-bold transition-colors duration-200" :class="row.attendance.status === 'alfa' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-700 hover:bg-red-200'">Alfa</button>
                  </div>
                </td>
                <td class="p-2 sm:p-3"><input v-model="row.attendance.notes" class="input h-8 sm:h-9 w-36 sm:w-40 text-xs sm:text-sm" placeholder="Ket..." /></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="p-4 flex gap-3 border-t border-outline-variant">
          <button @click="save" class="btn btn--primary" :disabled="saving || !sheet.length">Simpan Absensi</button>
          <span v-if="saving" class="px-4 py-2 text-sm text-on-surface-variant">Menyimpan...</span>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import BackButton from '../../Components/BackButton.vue'
import EmptyState from '../../Components/EmptyState.vue'
import AppShell from '../../Components/AppShell.vue'
import { useAuth } from '../../composables/useAuth.js'
const { user } = useAuth()
const params = new URLSearchParams(window.location.search)
const requestedDate = params.get('date')
const requestedSchedule = params.get('schedule')
const date = ref(/^\d{4}-\d{2}-\d{2}$/.test(requestedDate || '') ? requestedDate : todayLocal())
const schedules = ref([])
const scheduleId = ref('')
const attToday = ref(null)
const sheet = ref([])
const loading = ref(false)
const saving = ref(false)
const schedule = computed(() => schedules.value.find((item) => String(item.id) === String(scheduleId.value)) ?? null)
const daySchedules = computed(() => schedules.value.filter((item) => item.day_of_week === dayOfWeek(date.value)))

const dayMap = { mon:'Senin', tue:'Selasa', wed:'Rabu', thu:'Kamis', fri:'Jumat', sat:'Sabtu', sun:'Minggu' }
function labelDay(d) { return dayMap[d] ?? d }

function todayLocal() {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
}

function dayOfWeek(value) {
  const day = new Date(`${value}T00:00:00`).getDay()
  return ['sun','mon','tue','wed','thu','fri','sat'][day]
}

async function loadSchedules() {
  loading.value = true
  try {
    const r = await fetch('/api/schedules?per_page=100')
    const d = await r.json()
    if (!r.ok) throw new Error(d.message || 'Gagal memuat jadwal')
    schedules.value = d.data ?? d
    const requested = schedules.value.find((item) => String(item.id) === String(requestedSchedule))
    const current = daySchedules.value.find((item) => String(item.id) === String(scheduleId.value))
    const next = requested && requested.day_of_week === dayOfWeek(date.value) ? requested : (current ?? daySchedules.value[0] ?? null)
    scheduleId.value = next?.id ?? ''
    await loadSheet()
  } catch (e) {
    alert(e.message)
    schedules.value = []
    scheduleId.value = ''
    sheet.value = []
  } finally { loading.value = false }
}

async function selectSchedule() {
  await loadSheet()
}

async function onDateChange() {
  const current = daySchedules.value.find((item) => String(item.id) === String(scheduleId.value))
  if (!current) scheduleId.value = daySchedules.value[0]?.id ?? ''
  sheet.value = []
  await loadSheet()
}

async function loadSheet() {
  if (!schedule.value) {
    sheet.value = []
    return
  }

  const r = await fetch(`/api/schedules/${schedule.value.id}/sheet?date=${date.value}`)
  const data = await r.json()
  if (!r.ok) {
    alert(data.message || 'Gagal memuat absensi')
    sheet.value = []
    return
  }
  sheet.value = data.map((row) => ({
    student: row.student,
    attendance: { status: row.attendance?.status ?? 'alfa', notes: row.attendance?.notes ?? '' },
  }))
  await loadTeacherAttendance()
}

async function loadTeacherAttendance() {
  const r = await fetch(`/api/teacher-attendances?date=${date.value}`)
  if (!r.ok) return
  const d = await r.json()
  attToday.value = (d.data ?? d)[0] ?? null
}

async function doCheckIn() {
  const r = await fetch('/api/teacher-attendances/check-in', { method: 'POST' })
  if (!r.ok) return alert('Gagal check-in')
  await loadTeacherAttendance()
}

async function doCheckOut() {
  const r = await fetch('/api/teacher-attendances/check-out', { method: 'POST' })
  if (!r.ok) return alert('Gagal check-out')
  await loadTeacherAttendance()
}

async function save() {
  if (!schedule.value) return
  saving.value = true
  try {
    const r = await fetch(`/api/schedules/${schedule.value.id}/attendances?date=${date.value}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ rows: sheet.value.map(row => ({ student_id: row.student.id, status: row.attendance.status, notes: row.attendance.notes })) })
    })
    if (!r.ok) {
      const data = await r.json().catch(() => ({}))
      throw new Error(Object.values(data.errors ?? {}).flat().join('\n') || data.message || 'Gagal menyimpan')
    }
    alert('Absensi tersimpan')
    await loadSheet()
  } catch (e) { alert(e.message) }
  finally { saving.value = false }
}

onMounted(loadSchedules)
</script>
