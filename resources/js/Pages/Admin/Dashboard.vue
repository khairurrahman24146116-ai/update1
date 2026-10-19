<template>
<AppShell title="Dashboard Admin">
  <div class="relative w-full md:max-w-md">
    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px] pointer-events-none">search</span>
    <input v-model="query" type="search" placeholder="Cari modul manajemen..." class="input pl-11" />
  </div>
  <div class="flex items-center gap-2 flex-wrap">
    <router-link to="/app/admin/settings" class="btn btn--primary btn--sm">
      <span class="material-symbols-outlined !w-4 !h-4">settings</span> Pengaturan
    </router-link>
  </div>

  <div class="relative bg-white p-6 md:p-8 rounded-xl border border-outline-variant shadow-card flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4 overflow-hidden">
    <div class="absolute -right-8 -top-8 w-44 h-44 opacity-5 pointer-events-none text-primary"><span class="material-symbols-outlined !w-[170px] !h-[170px] text-[170px] select-none">dashboard</span></div>
    <div class="space-y-2 max-w-3xl relative z-10">
      <p class="font-body-sm text-body-sm text-on-surface-variant">Ringkasan data sistem yang diambil langsung dari basis data sekolah.</p>
    </div>
    <router-link to="/app/admin/settings" class="btn btn--primary relative z-10">Buka Pengaturan</router-link>
  </div>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div v-for="s in stats" :key="s.label" class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm">
      <div class="text-label-sm text-label-sm text-on-surface-variant" style="font-variant-numeric: tabular-nums">{{ s.code }}</div>
      <div class="text-2xl font-black mt-1" style="font-variant-numeric: tabular-nums">{{ s.value }}</div>
      <div class="text-xs font-bold">{{ s.label }}</div>
      <div class="text-xs text-on-surface-variant">{{ s.desc }}</div>
    </div>
  </div>

  <div>
    <h2 class="font-headline-md text-headline-md text-on-surface mb-3 flex items-center gap-2"><span class="material-symbols-outlined text-[18px] text-primary">folder_special</span> Kelola Data Manajemen</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <router-link v-for="m in filteredManajemen" :key="m.to" :to="m.to" class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-[transform,box-shadow] group">
        <span class="w-10 h-10 rounded-full flex items-center justify-center border border-outline-variant" :class="m.bg"><span class="material-symbols-outlined !w-5 !h-5 text-primary">{{ m.icon }}</span></span>
        <div class="font-bold text-sm mt-3">{{ m.label }}</div>
        <div class="text-xs text-on-surface-variant mt-1">{{ m.desc }}</div>
        <div class="text-xs font-bold text-primary mt-3 group-hover:underline">Kelola →</div>
      </router-link>
    </div>
  </div>

  <div>
    <h2 class="font-headline-md text-headline-md text-on-surface mb-3 flex items-center gap-2"><span class="material-symbols-outlined text-[18px] text-primary">public</span> Pengaturan & Konten Publik</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <router-link v-for="m in filteredKonten" :key="m.to" :to="m.to" class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-[transform,box-shadow] group">
        <span class="w-10 h-10 rounded-full flex items-center justify-center border border-outline-variant" :class="m.bg"><span class="material-symbols-outlined !w-5 !h-5 text-primary">{{ m.icon }}</span></span>
        <div class="font-bold text-sm mt-3">{{ m.label }}</div>
        <div class="text-xs text-on-surface-variant mt-1">{{ m.desc }}</div>
        <div class="text-xs font-bold text-primary mt-3 group-hover:underline">Buka →</div>
      </router-link>
    </div>
  </div>

  <div class="bg-white border border-outline-variant rounded-xl p-4 shadow-md flex flex-col sm:flex-row items-center justify-between gap-4">
    <div class="flex items-center gap-3"><span class="w-10 h-10 rounded-full bg-primary-fixed border border-outline-variant flex items-center justify-center"><span class="material-symbols-outlined text-[20px] text-primary">lock</span></span><div><span class="font-bold block text-sm">Akses Admin</span><span class="text-xs text-on-surface-variant">Modul terlindungi oleh peran admin</span></div></div>
    <router-link to="/app/admin/settings" class="btn btn--primary">Buka Pengaturan Sistem</router-link>
  </div>
</AppShell>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import AppShell from '../../Components/AppShell.vue'
import { useAuth } from '../../composables/useAuth.js'
const { can } = useAuth()
const query = ref('')
const stats = ref([
  { code:'SANTRI TERDAFTAR', value:'—', label:'Santri', desc:'Terdaftar di SIA' },
  { code:'GURU AKTIF', value:'—', label:'Guru', desc:'Asatidz & Tendik' },
  { code:'AKUN PORTAL', value:'—', label:'Akun Portal', desc:'Semua peran aktif' },
  { code:'JADWAL MAPEL', value:'—', label:'Jadwal Mapel', desc:'Pertemuan terjadwal' },
])

onMounted(async () => {
  if (can('students.view')) {
    try {
      const st = await fetch('/api/students?per_page=1').then((r) => (r.ok ? r.json() : null))
      if (st && typeof st.total === 'number') stats.value[0].value = st.total.toLocaleString('id-ID')
    } catch {}
  }
  if (can('schedules.view')) {
    try {
      const jd = await fetch('/api/schedules?per_page=1').then((r) => (r.ok ? r.json() : null))
      if (jd && typeof jd.total === 'number') stats.value[3].value = jd.total.toLocaleString('id-ID')
    } catch {}
  }
  if (can('users.view')) {
    try {
      const acc = await fetch('/api/users/stats').then((r) => (r.ok ? r.json() : null))
      if (acc) {
        stats.value[2].value = Number(acc.total).toLocaleString('id-ID')
        stats.value[1].value = Number(acc.guru).toLocaleString('id-ID')
      }
    } catch {}
  }
})
const manajemen = [
  { to:'/app/admin/students?status=aktif', label:'Data Santri', desc:'Kelola santri aktif & detail', icon:'school', bg:'bg-primary-fixed', permission:'students.update' },
  { to:'/app/admin/kelas', label:'Kelas', desc:'Master kelas & tahun ajaran', icon:'meeting_room', bg:'bg-surface-container', permission:'classrooms.update' },
  { to:'/app/admin/mapel', label:'Mata Pelajaran', desc:'Master mapel & kode', icon:'menu_book', bg:'bg-surface-container-low', permission:'subjects.update' },
  { to:'/app/admin/teachers', label:'Data Guru', desc:'Kelola Asatidz & status akun', icon:'person', bg:'bg-secondary-container', permission:'teachers.update' },
  { to:'/app/bendahara', label:'Keuangan & SPP', desc:'Pemasukan, pengeluaran, tagihan & laporan', icon:'account_balance_wallet', bg:'bg-primary-fixed', permission:'finance.view' },
  { to:'/app/admin/import-siswa', label:'Import Siswa', desc:'Template, preview & import XLSX', icon:'upload_file', bg:'bg-secondary-container', permission:'students.import' },
  { to:'/app/admin/schedules', label:'Jadwal Pelajaran', desc:'Kelola jadwal mengajar harian', icon:'calendar_month', bg:'bg-primary-fixed', permission:'schedules.update' },
]
const konten = [
  { to:'/app/admin/homepage', label:'Pengaturan Beranda', desc:'Hero, logo, media & waktu salat', icon:'home', bg:'bg-secondary-container', permission:'settings.homepage.manage' },
  { to:'/app/admin/structure', label:'Kelola Struktur', desc:'Susun struktur LPI, SMA & SMP per unit', icon:'account_tree', bg:'bg-tertiary-fixed', permission:'content.structure.update' },
  { to:'/app/admin/settings?tab=landing', label:'Landing Page Content', desc:'Hero, sambutan & section', icon:'language', bg:'bg-surface-container-low', permission:'settings.landing.manage' },
  { to:'/app/admin/settings?tab=berita', label:'Berita & Kategori', desc:'Artikel publish/draft', icon:'newspaper', bg:'bg-primary-fixed', permission:'content.news.update' },
  { to:'/app/admin/settings?tab=galeri', label:'Galeri & Album', desc:'Foto & video per album', icon:'photo_library', bg:'bg-tertiary-fixed', permission:'content.gallery.update' },
  { to:'/app/admin/settings?tab=ppdb', label:'PPDB & Pendaftar', desc:'Gelombang & status seleksi', icon:'edit_note', bg:'bg-primary-fixed', permission:'ppdb.waves.update' },
  { to:'/app/admin/settings?tab=akun', label:'Portal / Akun', desc:'Kelola akun lintas peran', icon:'group', bg:'bg-surface-container-high', permission:'users.update' },
]
const filteredManajemen = computed(() => filterModules(manajemen))
const filteredKonten = computed(() => filterModules(konten))
function filterModules(list) {
  const q = query.value.trim().toLowerCase()
  return list
    .filter((m) => !m.permission || can(m.permission))
    .filter((m) => !q || m.label.toLowerCase().includes(q) || m.desc.toLowerCase().includes(q))
}
</script>
