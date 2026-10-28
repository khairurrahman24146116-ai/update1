<template>
  <AppShell title="Jadwal Pelajaran">
    <BackButton fallback="/app/admin" />
    <div class="flex flex-wrap items-center gap-3">
      <input v-model="search" placeholder="Cari mapel..." class="input w-56" />
      <select v-model="filterDay" class="input w-44">
        <option value="">Semua Hari</option>
        <option v-for="d in days" :key="d" :value="d">{{ labelDay(d) }}</option>
      </select>
      <button @click="load" class="btn btn--primary btn--sm">Cari</button>
      <button v-if="isAdmin" @click="openForm" class="btn btn--ghost btn--sm">+ Tambah Jadwal</button>
    </div>

    <div v-if="loading" class="p-8 text-center text-sm text-on-surface-variant">Memuat data...</div>
    <EmptyState v-else-if="!list.length" icon="calendar_month" title="Tidak ada jadwal" description="Belum ada jadwal mengajar yang terdaftar." />
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="(s, i) in list" :key="s.id" class="stagger-item bg-white p-5 rounded-xl border border-outline-variant shadow-sm space-y-2 hover:shadow-md hover:-translate-y-0.5 transition-[transform,box-shadow]" :style="{ animationDelay: `${Math.min(i, 8) * 80}ms` }">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span class="font-bold">{{ s.subject?.name ?? '-' }}</span>
          <span class="flex items-center gap-2">
            <span v-if="s.subject?.jenis" class="badge" :class="jenisClass(s.subject.jenis)">{{ jenisLabel(s.subject.jenis) }}</span>
            <span class="text-xs text-on-surface-variant">{{ labelDay(s.day_of_week) }}</span>
          </span>
        </div>
        <div class="text-sm text-on-surface-variant">{{ s.classroom?.name ?? '-' }} • {{ s.start_time }}-{{ s.end_time }}</div>
        <div class="text-xs text-on-surface-variant">Guru: {{ s.teacher?.name ?? '-' }}</div>
        <div class="flex gap-2 pt-2">
          <router-link v-if="user?.role === 'guru'" :to="`/app/guru/absensi?schedule=${s.id}&date=${today}`" class="btn btn--secondary btn--sm">Absen</router-link>
          <button v-if="isAdmin" @click="openEdit(s)" class="btn btn--ghost btn--sm">Edit</button>
          <button v-if="isAdmin" @click="remove(s.id)" class="btn btn--destructive btn--sm">Hapus</button>
        </div>
      </div>
    </div>

    <!-- Modal Create/Edit Schedule -->
    <Transition name="modal">
    <div v-if="showForm" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
      <div class="modal-panel bg-white rounded-xl border border-outline-variant shadow-md w-full max-w-lg space-y-4 p-6">
        <h2 class="font-headline-md text-headline-md">{{ editingId ? 'Edit Jadwal' : 'Tambah Jadwal' }}</h2>
        <form @submit.prevent="submitForm" class="space-y-3">
          <div>
            <label class="block text-xs font-bold mb-1">Kelas</label>
            <select v-model="form.classroom_id" class="input" @change="syncSubjectForClassroom">
              <option value="">Pilih Kelas</option>
              <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ classroomLabel(c) }}</option>
            </select>
            <p v-if="errors.classroom_id" class="text-error text-xs mt-1">{{ errors.classroom_id[0] }}</p>
          </div>
          <div>
            <label class="block text-xs font-bold mb-1">Mapel</label>
            <select v-model="form.subject_id" class="input" :disabled="!form.classroom_id">
              <option value="">Pilih Mapel</option>
              <option v-for="s in availableSubjects" :key="s.id" :value="s.id">{{ s.name }} ({{ jenisLabel(s.jenis) }})</option>
            </select>
            <p v-if="selectedSubject" class="mt-1">
              <span class="badge" :class="jenisClass(selectedSubject.jenis)">{{ jenisLabel(selectedSubject.jenis) }}</span>
            </p>
            <p v-else-if="form.classroom_id" class="text-xs text-on-surface-variant mt-1">Tidak ada mapel aktif yang cocok untuk kelas ini.</p>
            <p v-if="errors.subject_id" class="text-error text-xs mt-1">{{ errors.subject_id[0] }}</p>
          </div>
          <div>
            <label class="block text-xs font-bold mb-1">Guru</label>
            <select v-model="form.teacher_id" class="input">
              <option value="">Pilih Guru</option>
              <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
            <p v-if="errors.teacher_id" class="text-error text-xs mt-1">{{ errors.teacher_id[0] }}</p>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold mb-1">Hari</label>
              <select v-model="form.day_of_week" class="input">
                <option v-for="d in days" :key="d" :value="d">{{ labelDay(d) }}</option>
              </select>
              <p v-if="errors.day_of_week" class="text-error text-xs mt-1">{{ errors.day_of_week[0] }}</p>
            </div>
            <div>
              <label class="block text-xs font-bold mb-1">Tahun Ajaran</label>
              <input v-model="form.academic_year" type="text" placeholder="2026/2027" class="input" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold mb-1">Mulai</label>
              <input v-model="form.start_time" type="time" class="input" />
              <p v-if="errors.start_time" class="text-error text-xs mt-1">{{ errors.start_time[0] }}</p>
            </div>
            <div>
              <label class="block text-xs font-bold mb-1">Selesai</label>
              <input v-model="form.end_time" type="time" class="input" />
              <p v-if="errors.end_time" class="text-error text-xs mt-1">{{ errors.end_time[0] }}</p>
            </div>
          </div>
          <div class="flex gap-3 pt-2">
            <button type="submit" class="btn btn--primary">Simpan</button>
            <button type="button" @click="closeForm" class="btn btn--ghost">Batal</button>
          </div>
        </form>
      </div>
    </div>
    </Transition>
  </AppShell>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import BackButton from '../../Components/BackButton.vue'
import EmptyState from '../../Components/EmptyState.vue'
import AppShell from '../../Components/AppShell.vue'
import { useAuth } from '../../composables/useAuth.js'
import { isAdminUser } from '../../permissions.js'
const { user } = useAuth()
const isAdmin = computed(() => isAdminUser(user.value))

const search = ref('')
const filterDay = ref('')
const today = new Date().toISOString().slice(0, 10)
const list = ref([])
const loading = ref(false)
const showForm = ref(false)
const errors = ref({})
const days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']
const classrooms = ref([])
const subjects = ref([])
const teachers = ref([])
const editingId = ref(null)

function labelDay(d) {
  return { mon:'Senin', tue:'Selasa', wed:'Rabu', thu:'Kamis', fri:'Jumat', sat:'Sabtu', sun:'Minggu' }[d] ?? d
}

const form = ref({
  classroom_id: '', subject_id: '', teacher_id: '',
  day_of_week: 'mon', start_time: '08:00', end_time: '09:30', academic_year: ''
})

const selectedClassroom = computed(() => classrooms.value.find((classroom) => String(classroom.id) === String(form.value.classroom_id)) ?? null)
const selectedSubject = computed(() => subjects.value.find((subject) => String(subject.id) === String(form.value.subject_id)) ?? null)
const availableSubjects = computed(() => {
  if (!selectedClassroom.value) return []

  const matches = subjects.value.filter((subject) => subject.is_active && subjectMatches(subject, selectedClassroom.value))
  const current = selectedSubject.value
  if (editingId.value && current && !matches.some((subject) => subject.id === current.id)) {
    return [current, ...matches]
  }

  return matches
})

function jenisLabel(jenis) {
  return jenis === 'non_formal' ? 'Non Formal' : 'Formal'
}

function jenisClass(jenis) {
  return jenis === 'non_formal' ? 'bg-tertiary-fixed text-on-surface' : 'bg-primary-fixed text-on-surface'
}

function classroomLabel(classroom) {
  if (!classroom) return '-'
  const unit = String(classroom.unit ?? '').toLowerCase()
  if (unit === 'lpi') return `LPI - ${classroom.jenjang}`
  if (unit === 'smp') return 'SMP'
  return 'SMA'
}

function subjectMatches(subject, classroom) {
  if (!subject.scopes?.length) return true

  const unit = String(classroom.unit ?? '').toLowerCase()
  const jenjang = String(classroom.jenjang ?? '').toLowerCase()

  return subject.scopes.some((scope) => String(scope.unit).toLowerCase() === unit && String(scope.jenjang).toLowerCase() === jenjang)
}

function syncSubjectForClassroom() {
  if (!form.value.subject_id) return
  if (!availableSubjects.value.some((subject) => String(subject.id) === String(form.value.subject_id))) {
    form.value.subject_id = ''
  }
}

async function loadOptions() {
  try {
    const [cr, sr, tr] = await Promise.all([
      fetch('/api/classrooms?enabled_units=1&per_page=100').then(r => r.json()),
      fetch('/api/subjects?per_page=100').then(r => r.json()),
      fetch('/api/teachers').then(r => r.json())
    ])
    classrooms.value = cr.data ?? cr
    subjects.value = sr.data ?? sr
    teachers.value = tr.data ?? tr
  } catch {}
}

async function load() {
  loading.value = true
  try {
    const q = new URLSearchParams()
    if (search.value) q.set('search', search.value)
    if (filterDay.value) q.set('day', filterDay.value)
    const r = await fetch(`/api/schedules?${q}`)
    const d = await r.json()
    list.value = d.data ?? d
  } finally { loading.value = false }
}

async function openForm() {
  errors.value = {}
  editingId.value = null
  form.value = { classroom_id:'', subject_id:'', teacher_id:'', day_of_week:'mon', start_time:'08:00', end_time:'09:30', academic_year:'' }
  await loadOptions()
  showForm.value = true
}

async function openEdit(s) {
  errors.value = {}
  editingId.value = s.id
  form.value = {
    classroom_id: s.classroom_id, subject_id: s.subject_id, teacher_id: s.teacher_id,
    day_of_week: s.day_of_week, start_time: String(s.start_time).slice(0,5), end_time: String(s.end_time).slice(0,5), academic_year: s.academic_year ?? ''
  }
  await loadOptions()
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  errors.value = {}
  editingId.value = null
  form.value = { classroom_id:'', subject_id:'', teacher_id:'', day_of_week:'mon', start_time:'08:00', end_time:'09:30', academic_year:'' }
}

async function submitForm() {
  errors.value = {}
  const payload = { ...form.value, classroom_id: Number(form.value.classroom_id), subject_id: Number(form.value.subject_id), teacher_id: Number(form.value.teacher_id) }
  const url = editingId.value ? `/api/schedules/${editingId.value}` : '/api/schedules'
  const method = editingId.value ? 'PUT' : 'POST'
  const r = await fetch(url, {
    method,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  const body = await r.json().catch(() => ({}))
  if (r.status === 422) {
    errors.value = body.errors ?? body ?? {}
    return
  }
  if (r.status === 403) { alert('Tidak diizinkan'); return }
  if (!r.ok) { alert(body.message || 'Gagal menyimpan'); return }
  closeForm()
  await load()
}

async function remove(id) {
  if (!confirm('Hapus jadwal?')) return
  await fetch(`/api/schedules/${id}`, { method: 'DELETE' })
  await load()
}

onMounted(load)
</script>
