<template>
  <AppShell title="Absensi Mengajar">
    <BackButton fallback="/app/guru" />
    <div class="flex flex-wrap gap-3 items-center">
      <input v-model="date" type="date" class="input w-48" />
      <button @click="loadSchedule" class="btn btn--primary btn--sm">Tampilkan</button>
    </div>

    <div v-if="loading" class="p-8 text-center text-sm text-on-surface-variant">Memuat...</div>
    <EmptyState v-else-if="!schedule" icon="event_busy" title="Tidak ada jadwal hari ini" description="Pilih tanggal lain atau hubungi admin untuk penjadwalan." />

    <div v-else class="space-y-6">
      <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm space-y-1">
        <div class="font-bold">{{ schedule.subject?.name }} — {{ schedule.classroom?.name }}</div>
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
          <button @click="save" class="btn btn--primary">Simpan Absensi</button>
          <span v-if="saving" class="px-4 py-2 text-sm text-on-surface-variant">Menyimpan...</span>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import BackButton from '../../Components/BackButton.vue'
import EmptyState from '../../Components/EmptyState.vue'
import AppShell from '../../Components/AppShell.vue'
import { useAuth } from '../../composables/useAuth.js'
const { user } = useAuth()
const date = ref(new Date().toISOString().slice(0, 10))
const schedule = ref(null)
const attToday = ref(null)
const sheet = ref([])
const loading = ref(false)
const saving = ref(false)

const dayMap = { mon:'Senin', tue:'Selasa', wed:'Rabu', thu:'Kamis', fri:'Jumat', sat:'Sabtu', sun:'Minggu' }
function labelDay(d) { return dayMap[d] ?? d }

async function loadSchedule() {
  loading.value = true
  try {
    const r = await fetch(`/api/schedules?teacher_id=${user.id}&day=${dayOfWeek()}`)
    const d = await r.json()
    schedule.value = (d.data ?? d)[0] ?? null
    if (schedule.value) await loadSheet()
  } finally { loading.value = false }
}

async function loadSheet() {
  const r = await fetch(`/api/schedules/${schedule.value.id}/sheet?date=${date.value}`)
  sheet.value = await r.json()
  if (!sheet.value.attendance) {
    sheet.value = sheet.value.map(r => ({ student: r.student, attendance: r.attendance ?? { status: 'alfa', notes: '' } }))
  }
  const r2 = await fetch(`/api/teacher-attendances?date=${date.value}&teacher_id=${user.id}`)
  const d2 = await r2.json()
  attToday.value = (d2.data ?? d2)[0] ?? null
}

function dayOfWeek() {
  return ['sun','mon','tue','wed','thu','fri','sat'][new Date().getDay()]
}

async function doCheckIn() {
  await fetch('/api/teacher-attendances/check-in', { method: 'POST' })
  await loadSheet()
}
async function doCheckOut() {
  await fetch('/api/teacher-attendances/check-out', { method: 'POST' })
  await loadSheet()
}
async function save() {
  saving.value = true
  try {
    await fetch(`/api/schedules/${schedule.value.id}/attendances?date=${date.value}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ rows: sheet.value.map(r => ({ student_id: r.student.id, status: r.attendance.status, notes: r.attendance.notes })) })
    })
    alert('Absensi tersimpan')
  } catch (e) { alert('Gagal menyimpan') }
  finally { saving.value = false }
}

onMounted(loadSchedule)
</script>
