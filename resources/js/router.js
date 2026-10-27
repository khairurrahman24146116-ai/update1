import { createRouter, createWebHistory } from 'vue-router'
import Landing from './Pages/Landing.vue'
import Profil from './Pages/Profil.vue'
import Fasilitas from './Pages/Fasilitas.vue'
import Prestasi from './Pages/Prestasi.vue'
import Kontak from './Pages/Kontak.vue'
import Login from './Pages/Login.vue'
import PPDB from './Pages/PPDB.vue'
import AdminDashboard from './Pages/Admin/Dashboard.vue'
import GuruDashboard from './Pages/Guru/Dashboard.vue'
import BendaharaDashboard from './Pages/Bendahara/Dashboard.vue'
import BendaharaTransactions from './Pages/Bendahara/Transactions.vue'
import BendaharaSpp from './Pages/Bendahara/Spp.vue'
import BendaharaPayments from './Pages/Bendahara/Payments.vue'
import BendaharaReports from './Pages/Bendahara/Reports.vue'
import BendaharaPreview from './Pages/Bendahara/Preview.vue'
import WaliMuridDashboard from './Pages/WaliMurid/Dashboard.vue'
import OperatorAkun from './Pages/Operator/Akun.vue'
import PendingAccess from './Pages/PendingAccess.vue'
import AdminSettings from './Pages/Admin/Settings.vue'
import AdminHomepageSettings from './Pages/Admin/HomepageSettings.vue'
import AdminOrganization from './Pages/Admin/Organization.vue'
import AdminStructure from './Pages/Admin/Structure.vue'
import AdminStudents from './Pages/Admin/Students.vue'
import AdminTeachers from './Pages/Admin/Teachers.vue'
import TeacherDetail from './Pages/Admin/TeacherDetail.vue'
import AdminFinance from './Pages/Admin/Finance.vue'
import AdminSchedules from './Pages/Admin/Schedules.vue'
import AdminActivityLogs from './Pages/Admin/ActivityLogs.vue'
import AdminKelas from './Pages/Admin/Kelas.vue'
import AdminMapel from './Pages/Admin/Mapel.vue'
import ImportSiswa from './Pages/Admin/ImportSiswa.vue'
import GuruAbsensi from './Pages/Guru/Absensi.vue'
import GuruImportAbsensi from './Pages/Guru/ImportAbsensi.vue'
import GuruJadwal from './Pages/Guru/Jadwal.vue'
import GuruNilai from './Pages/Guru/Nilai.vue'
import WaliNilaiAnak from './Pages/WaliMurid/NilaiAnak.vue'
import WaliSurat from './Pages/WaliMurid/Surat.vue'
import WaliPertemuan from './Pages/WaliMurid/Pertemuan.vue'
import BeritaDetail from './Pages/BeritaDetail.vue'
import GaleriDetail from './Pages/GaleriDetail.vue'
import BeritaList from './Pages/BeritaList.vue'
import GaleriList from './Pages/GaleriList.vue'
import LpiBeranda from './Pages/Lpi/LpiBeranda.vue'
import LpiTentang from './Pages/Lpi/LpiTentang.vue'
import LpiProgram from './Pages/Lpi/LpiProgram.vue'
import LpiLayanan from './Pages/Lpi/LpiLayanan.vue'
import LpiStruktur from './Pages/Lpi/LpiStruktur.vue'
import SmaStruktur from './Pages/Sma/SmaStruktur.vue'
import SmpStruktur from './Pages/Sma/SmpStruktur.vue'
import SmaVisiMisi from './Pages/Sma/SmaVisiMisi.vue'
import SmaKurikulum from './Pages/Sma/SmaKurikulum.vue'
import LpiAkademikKurikulum from './Pages/Lpi/LpiAkademikKurikulum.vue'
import LpiAkademikKalender from './Pages/Lpi/LpiAkademikKalender.vue'
import NotFound from './Pages/NotFound.vue'
import { LOGIN_PATH } from './site-paths.js'
import { ADMIN_MANAGE_PERMISSIONS } from './permissions.js'
import { refreshUserSession } from './composables/useAuth.js'

const roleMap = { admin_utama: '/app/admin', operator: '/app/operator/akun', pending: '/app/pending', kepala_sekolah: '/app/kepala/guru', guru: '/app/guru', bendahara: '/app/bendahara', bendahara_lpi: '/app/bendahara', bendahara_sma: '/app/bendahara', wali_murid: '/app/wali-murid' }
const financeRoles = ['admin_utama', 'bendahara_lpi', 'bendahara_sma']
function currentUser() {
  try { return JSON.parse(localStorage.getItem('madani_user') || 'null') } catch { return null }
}
function currentRole() {
  return currentUser()?.role || null
}
function denyAccess() {
  return { path: roleMap[currentRole()] || LOGIN_PATH, query: { denied: '1' } }
}
let sessionSynced = false
let sessionSyncPromise = null
async function syncSessionOnce() {
  if (sessionSynced) return
  if (!localStorage.getItem('madani_token')) {
    sessionSynced = true
    return
  }
  if (!sessionSyncPromise) {
    sessionSyncPromise = refreshUserSession()
      .catch(() => null)
      .finally(() => { sessionSyncPromise = null })
  }
  await sessionSyncPromise
  sessionSynced = true
}

const routes = [
    { path: '/', component: LpiBeranda },
    { path: '/tentang', component: LpiTentang },
    { path: '/program', component: LpiProgram },
    { path: '/akademik/kurikulum', component: LpiAkademikKurikulum },
    { path: '/akademik/kalender', component: LpiAkademikKalender },
    { path: '/struktur', component: LpiStruktur },
    { path: '/layanan', component: LpiLayanan },
    { path: '/sma', component: Landing },
    { path: '/sma/profil', component: Profil },
    { path: '/sma/visi-misi', component: SmaVisiMisi },
    { path: '/sma/kurikulum', component: SmaKurikulum },
    { path: '/sma/struktur', component: SmaStruktur },
    { path: '/smp/struktur', component: SmpStruktur },
    { path: '/sma/fasilitas', component: Fasilitas },
    { path: '/sma/prestasi', component: Prestasi },
    { path: '/sma/kontak', component: Kontak },
    { path: '/sma/ppdb', component: PPDB },
    { path: '/sma/berita', component: BeritaList },
    { path: '/sma/galeri', component: GaleriList },
    { path: '/sma/berita/:id', component: BeritaDetail },
    { path: '/sma/galeri/:id', component: GaleriDetail },
    { path: '/lpi', redirect: '/' },
    { path: '/lpi/tentang', redirect: '/tentang' },
    { path: '/lpi/program', redirect: '/program' },
    { path: '/lpi/layanan', redirect: '/layanan' },
    { path: '/lpi/struktur', redirect: '/struktur' },
    { path: '/profil', redirect: '/sma/profil' },
    { path: '/sma/profil/kurikulum', redirect: '/sma/kurikulum' },
    { path: '/sma/profil/visi-misi', redirect: '/sma/visi-misi' },
    { path: '/fasilitas', redirect: '/sma/fasilitas' },
    { path: '/prestasi', redirect: '/sma/prestasi' },
    { path: '/kontak', redirect: '/sma/kontak' },
    { path: '/ppdb', redirect: '/sma/ppdb' },
    { path: '/berita', redirect: '/sma/berita' },
    { path: '/galeri', redirect: '/sma/galeri' },
    { path: '/berita/:id', redirect: (to) => `/sma/berita/${to.params.id}` },
    { path: '/galeri/:id', redirect: (to) => `/sma/galeri/${to.params.id}` },
    { path: LOGIN_PATH, component: Login, meta: { guestOnly: true } },
    { path: '/:pathMatch(.*)*', component: NotFound },
    { path: '/app', redirect: () => roleMap[currentRole()] || '/' },
    { path: '/app/pending', component: PendingAccess, meta: { requiresAuth: true, role: 'pending' } },
    { path: '/app/operator', redirect: '/app/operator/akun' },
    { path: '/app/operator/akun', component: OperatorAkun, meta: { requiresAuth: true, role: ['admin_utama', 'operator'], permissions: ['users.view'] } },
    { path: '/app/admin', component: AdminDashboard, meta: { requiresAuth: true, role: 'admin_utama', permissions: ADMIN_MANAGE_PERMISSIONS } },
    { path: '/app/admin/settings', component: AdminSettings, meta: { requiresAuth: true, role: 'admin_utama', permissions: ['users.view', 'permissions.view', 'settings.site.manage', 'settings.landing.manage', 'content.news.view', 'content.gallery.view', 'ppdb.waves.view'] } },
    { path: '/app/admin/activity-logs', component: AdminActivityLogs, meta: { requiresAuth: true, role: 'admin_utama', permissions: ['logs.view'], adminOnly: true } },
    { path: '/app/admin/homepage', component: AdminHomepageSettings, meta: { requiresAuth: true, role: 'admin_utama', permissions: ['settings.homepage.view', 'settings.homepage.manage'] } },
    { path: '/app/admin/organization', component: AdminOrganization, meta: { requiresAuth: true, role: 'admin_utama', permissions: ['content.structure.view'] } },
    { path: '/app/admin/structure', component: AdminStructure, meta: { requiresAuth: true, role: 'admin_utama', permissions: ['content.structure.view'] } },
    { path: '/app/admin/students', component: AdminStudents, meta: { requiresAuth: true, role: 'admin_utama', permissions: ['students.view'] } },
    { path: '/app/admin/teachers', component: AdminTeachers, meta: { requiresAuth: true, role: ['admin_utama', 'operator'], permissions: ['teachers.view'] } },
    { path: '/app/admin/teachers/:id', component: TeacherDetail, meta: { requiresAuth: true, role: ['admin_utama', 'operator'], permissions: ['teachers.view'] } },
    { path: '/app/kepala', redirect: '/app/kepala/guru' },
    { path: '/app/kepala/guru', component: AdminTeachers, meta: { requiresAuth: true, role: 'kepala_sekolah', permissions: ['teachers.view'] } },
    { path: '/app/kepala/guru/:id', component: TeacherDetail, meta: { requiresAuth: true, role: 'kepala_sekolah', permissions: ['teachers.view'] } },
    { path: '/app/admin/finance', component: AdminFinance, meta: { requiresAuth: true, role: financeRoles, permissions: ['finance.view', 'reports.finance.view'] } },
    { path: '/app/admin/kelas', component: AdminKelas, meta: { requiresAuth: true, role: 'admin_utama', permissions: ['classrooms.view'] } },
    { path: '/app/admin/mapel', component: AdminMapel, meta: { requiresAuth: true, role: 'admin_utama', permissions: ['subjects.view'] } },
    { path: '/app/admin/schedules', component: AdminSchedules, meta: { requiresAuth: true, role: 'admin_utama', permissions: ['schedules.view'] } },
    { path: '/app/admin/import-siswa', component: ImportSiswa, meta: { requiresAuth: true, role: 'admin_utama', permissions: ['students.import'], adminOnly: true } },
    { path: '/app/guru', component: GuruDashboard, meta: { requiresAuth: true, role: 'guru' } },
    { path: '/app/guru/jadwal', component: GuruJadwal, meta: { requiresAuth: true, role: 'guru' } },
    { path: '/app/guru/absensi', component: GuruAbsensi, meta: { requiresAuth: true, role: 'guru' } },
    { path: '/app/guru/import-absensi', component: GuruImportAbsensi, meta: { requiresAuth: true, role: 'guru' } },
    { path: '/app/guru/nilai', component: GuruNilai, meta: { requiresAuth: true, role: 'guru' } },
    { path: '/app/bendahara', component: BendaharaDashboard, meta: { requiresAuth: true, role: financeRoles, permissions: ['finance.view'] } },
    { path: '/app/bendahara/pemasukan', component: BendaharaTransactions, meta: { requiresAuth: true, role: financeRoles, permissions: ['finance.transactions.view'] } },
    { path: '/app/bendahara/pengeluaran', component: BendaharaTransactions, meta: { requiresAuth: true, role: financeRoles, permissions: ['finance.transactions.view'] } },
    { path: '/app/bendahara/spp', component: BendaharaSpp, meta: { requiresAuth: true, role: financeRoles, permissions: ['spp.view'] } },
    { path: '/app/bendahara/pembayaran', component: BendaharaPayments, meta: { requiresAuth: true, role: financeRoles, permissions: ['spp.view'] } },
    { path: '/app/bendahara/laporan', component: BendaharaReports, meta: { requiresAuth: true, role: financeRoles, permissions: ['reports.finance.view'] } },
    { path: '/app/bendahara/preview/pemasukan', component: BendaharaPreview, meta: { requiresAuth: true, role: financeRoles, permissions: ['finance.transactions.view'], reportType: 'income' } },
    { path: '/app/bendahara/preview/pengeluaran', component: BendaharaPreview, meta: { requiresAuth: true, role: financeRoles, permissions: ['finance.transactions.view'], reportType: 'expense' } },
    { path: '/app/wali-murid', component: WaliMuridDashboard, meta: { requiresAuth: true, role: 'wali_murid' } },
    { path: '/app/wali-murid/nilai', component: WaliNilaiAnak, meta: { requiresAuth: true, role: 'wali_murid' } },
    { path: '/app/wali-murid/surat', component: WaliSurat, meta: { requiresAuth: true, role: 'wali_murid' } },
    { path: '/app/wali-murid/pertemuan', component: WaliPertemuan, meta: { requiresAuth: true, role: 'wali_murid' } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to) {
    if (to.hash) return { el: to.hash, top: 96, behavior: 'smooth' }
    return { top: 0 }
  },
})
router.beforeEach(async (to) => {
  if (to.path === '/tentang' && to.hash === '#struktur') return '/struktur'
  let token = localStorage.getItem('madani_token')
  if (token) {
    await syncSessionOnce()
    token = localStorage.getItem('madani_token')
  }
  if (to.meta.requiresAuth && !token) return LOGIN_PATH
  if (to.meta.guestOnly && token) {
    const r = currentRole()
    return roleMap[r] || '/'
  }
  if (token && (to.meta.role || to.meta.permissions || to.meta.adminOnly)) {
    const u = currentUser()
    const r = u?.role || null
    if (r === 'pending' && to.path !== '/app/pending') return '/app/pending'
    const allowedRoles = to.meta.role ? (Array.isArray(to.meta.role) ? to.meta.role : [to.meta.role]) : []
    const roleOk = Boolean(r) && allowedRoles.includes(r)
    const perms = Array.isArray(u?.permissions) ? u.permissions : null
    const permOk = to.meta.permissions
      ? Boolean(perms && to.meta.permissions.some((p) => perms.includes(p)))
      : true

    if (to.meta.adminOnly && r !== 'admin_utama') return denyAccess()
    if (allowedRoles.length && !roleOk) return denyAccess()
    if (to.meta.permissions && !permOk) return denyAccess()
  }
  return true
})

export default router

