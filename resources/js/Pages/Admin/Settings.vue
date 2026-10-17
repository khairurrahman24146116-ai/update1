<template>
<AppShell title="Pengaturan Sistem">
  <BackButton fallback="/app/admin" />
<div class="relative bg-white p-6 md:p-8 rounded-xl border border-outline-variant shadow-md flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4 overflow-hidden">
<div class="absolute -right-8 -top-8 w-44 h-44 opacity-5 pointer-events-none text-primary"><span class="material-symbols-outlined !w-[170px] !h-[170px] text-[170px] select-none">verified_user</span></div>
<div class="space-y-2 max-w-3xl relative z-10">
<p class="text-sm text-on-surface-variant">Kelola konfigurasi identitas kelembagaan, aset visual, kanal komunikasi, landing page, dan backup basis data.</p>
</div>
<div class="flex flex-wrap gap-3 relative z-10 min-w-0">
<button @click="$router.go(0)" class="btn btn--ghost"><span class="material-symbols-outlined !w-4 !h-4">refresh</span> Muat Ulang Halaman</button>
<button @click="save" :disabled="loading || !dirty" class="btn btn--primary disabled:opacity-50"><span class="material-symbols-outlined !w-4 !h-4">save</span> {{ loading?'Menyimpan…':(dirty?'Simpan Perubahan Identitas':'Tidak Ada Perubahan') }}</button>
</div>
</div>

<nav class="flex gap-2 overflow-x-auto pb-1 p-1 bg-surface-container-low border border-outline-variant rounded-xl shadow-sm max-w-full min-w-0">
<button v-for="t in tabs" :key="t.key" @click="active=t.key" :class="active===t.key?'bg-primary text-on-primary shadow-sm border-outline-variant/60':'bg-white hover:bg-surface-container-high border-transparent'" class="inline-flex items-center gap-2 px-4 py-2 border rounded-full text-sm font-bold whitespace-nowrap transition-colors duration-200 ease-in-out">{{ t.label }}</button>
</nav>

  <section v-if="active==='landing'" class="bg-white border border-outline-variant rounded-xl shadow-sm p-6 space-y-4">
    <h2 class="font-headline-md text-headline-md">Landing — Kelola Section</h2>
    <div v-for="sec in landing" :key="sec.id" class="p-3 bg-surface-container-low border border-outline-variant rounded-xl flex flex-col gap-2 transition-transform duration-200 ease-in-out hover:-translate-y-1">
      <input v-model="sec.title" class="h-9 px-2 bg-white border border-outline-variant rounded-md text-sm font-bold"/>
      <textarea v-model="sec.body" rows="2" class="p-2 bg-white border border-outline-variant rounded-md text-sm"></textarea>
      <div class="flex gap-2"><button @click="saveLanding(sec)" class="btn btn--primary btn--sm">Simpan</button><label class="flex items-center gap-1 text-xs"><input type="checkbox" v-model="sec.is_visible" @change="saveLanding(sec)"/> Tampilkan</label></div>
    </div>
  </section>
  <section v-if="active==='berita'" class="bg-white border border-outline-variant rounded-xl shadow-sm p-6"><SettingsBerita /></section>
  <section v-if="active==='galeri'" class="bg-white border border-outline-variant rounded-xl shadow-sm p-3 sm:p-6 min-w-0 max-w-full"><SettingsGaleri /></section>
  <section v-if="active==='ppdb'" class="bg-white border border-outline-variant rounded-xl shadow-sm p-6"><SettingsPPDB /></section>
  <section v-if="active==='akun'" class="bg-white border border-outline-variant rounded-xl shadow-sm p-6"><SettingsAkun /></section>
  <section v-if="active==='keamanan'" class="bg-white border border-outline-variant rounded-xl shadow-sm p-6">
    <h2 class="font-headline-md text-headline-md">Keamanan & Audit Log — Segera hadir</h2>
    <p class="text-xs text-on-surface-variant mt-1">Gunakan /api/activity-logs atau panel backup untuk audit.</p>
  </section>
  <div v-show="['identitas','logo','kontak','sosial'].includes(active)" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
  <div class="lg:col-span-8 space-y-6">
<section class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
<div class="bg-surface-container-low border-b border-outline-variant px-4 py-3 flex items-center justify-between">
<div class="flex items-center gap-2"><span class="w-8 h-8 rounded-full bg-primary text-on-primary flex items-center justify-center border border-outline-variant"><span class="material-symbols-outlined !w-5 !h-5">account_balance</span></span><div><h2 class="font-bold text-sm">1. Legalitas & Identitas Dayah</h2><p class="text-xs text-on-surface-variant">Data primer untuk Rapor Digital, Ijazah & Portal Publik</p></div></div>
</div>
<div class="p-4 md:p-6 space-y-4">
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div class="md:col-span-2 space-y-1.5"><label class="block text-xs font-bold">Nama Resmi Lembaga Dayah / Sekolah <span class="text-error">*</span></label><div class="relative"><input v-model="form.app_name" class="input font-bold pr-9"/><span class="absolute right-3 top-1/2 -translate-y-1/2 text-primary material-symbols-outlined !w-4 !h-4">check</span></div><p class="text-xs text-on-surface-variant">Nama resmi berizin operasional Kanwil Kemenag & Kemendikbudristek RI.</p></div>
<div class="space-y-1.5"><label class="block text-xs font-bold">Nomor Statistik Pesantren (NSPP)</label><input v-model="form.nspp" class="input font-bold" /></div>
<div class="space-y-1.5"><label class="block text-xs font-bold">NPSN (Kemendikbudristek)</label><input v-model="form.npsn" class="input font-bold" /></div>
<div class="space-y-1.5"><label class="block text-xs font-bold">Karakter & Jenjang</label><select v-model="form.jenjang" class="input"><option>SMA Sains & Tahfidz Berasrama Penuh</option><option>Madrasah Aliyah Plus Keterampilan Terpadu</option></select></div>
<div class="space-y-1.5"><label class="block text-xs font-bold">Status Akreditasi & Milad</label><div class="grid grid-cols-2 gap-2"><input v-model="form.akreditasi" class="input font-bold"/><input v-model="form.milad" class="input font-bold"/></div></div>
<div class="md:col-span-2 space-y-1.5"><label class="block text-xs font-bold">Tagline / Motto</label><input v-model="form.tagline" class="input"/></div>
<div class="md:col-span-2 space-y-1.5"><label class="block text-xs font-bold">Visi & Misi</label><textarea v-model="form.vision" rows="3" class="input h-auto py-3"></textarea></div>
</div>
</div>
</section>

<section class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
<div class="bg-surface-container-low border-b border-outline-variant px-4 py-3 flex items-center justify-between"><div class="flex items-center gap-2"><span class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center border border-outline-variant"><span class="material-symbols-outlined !w-5 !h-5">image</span></span><div><h2 class="font-bold text-sm">2. Logo, Favicon & Aset Brand Digital</h2><p class="text-xs text-on-surface-variant">Kelola master vektor, watermark & favicon</p></div></div></div>
<div class="p-4 md:p-6">
<div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start md:items-center">
<div class="md:col-span-4 flex flex-col items-center p-4 bg-surface border border-dashed border-outline-variant rounded-xl text-center gap-2">
<div class="logo-plate rounded-full p-3">
  <img :src="asset(form.logo_path) || '/images/profile/logo-lpi-transparent.png'" alt="Pratinjau logo" width="128" height="128" class="w-32 h-32 object-contain"/>
</div>
<label class="btn btn--ghost btn--sm cursor-pointer">
  <span class="material-symbols-outlined !w-4 !h-4">upload</span>
  {{ uploading.field === 'logo' ? 'Mengunggah…' : 'Pilih Logo' }}
  <input @change="upload($event,'logo')" type="file" accept="image/*" class="sr-only" :disabled="uploading.active"/>
</label>
<p class="text-[11px] text-on-surface-variant">Gunakan PNG latar transparan agar logo bersih tanpa kotak latar.</p>
<p v-if="fileStatus.logo" class="text-xs font-bold" :class="fileStatus.logo.ok ? 'text-green-700' : 'text-error'">{{ fileStatus.logo.msg }}</p>
<p v-else-if="form.logo_path" class="text-xs text-on-surface-variant truncate max-w-full" :title="form.logo_path">{{ fileNameOf(form.logo_path) }}</p>
</div>
<div class="md:col-span-8 space-y-2">
<div v-for="item in brandAssets" :key="item.k" class="p-3 bg-surface-container-low border border-outline-variant rounded-2xl flex flex-col sm:flex-row sm:items-center gap-3 justify-between transition-transform duration-200 ease-in-out hover:-translate-y-1">
<div class="flex items-center gap-3 min-w-0">
  <div class="logo-plate rounded-xl w-12 h-12 shrink-0">
    <img v-if="asset(form[item.k])" :src="asset(form[item.k])" :alt="item.l" class="w-full h-full object-contain"/>
    <span v-else class="material-symbols-outlined !w-5 !h-5 text-on-surface-variant">{{ item.icon }}</span>
  </div>
  <div class="min-w-0">
    <div class="font-bold text-sm">{{ item.l }}</div>
    <p class="text-xs text-on-surface-variant">{{ item.d }}</p>
    <p v-if="fileStatus[item.k]" class="text-xs font-bold" :class="fileStatus[item.k].ok ? 'text-green-700' : 'text-error'">{{ fileStatus[item.k].msg }}</p>
    <p v-else-if="form[item.k]" class="text-xs text-on-surface-variant truncate max-w-[18rem]" :title="form[item.k]">{{ fileNameOf(form[item.k]) }}</p>
  </div>
</div>
<label class="btn btn--ghost btn--sm cursor-pointer shrink-0 self-start sm:self-center">
  <span class="material-symbols-outlined !w-4 !h-4">upload</span>
  {{ uploading.field === item.k ? 'Mengunggah…' : 'Pilih File' }}
  <input @change="upload($event,item.k)" type="file" accept="image/*" class="sr-only" :disabled="uploading.active"/>
</label>
</div>
</div>
</div>
</div>
</section>

<section class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
<div class="bg-surface-container-low border-b border-outline-variant px-4 py-3 flex items-center justify-between"><div class="flex items-center gap-2"><span class="w-8 h-8 rounded-full bg-tertiary-fixed flex items-center justify-center border border-outline-variant"><span class="material-symbols-outlined !w-5 !h-5">location_on</span></span><div><h2 class="font-bold text-sm">3. Kanal Kontak & Lokasi</h2><p class="text-xs text-on-surface-variant">Titik GPS & kontak pimpinan</p></div></div></div>
<div class="p-4 md:p-6 space-y-4">
<textarea v-model="form.address" rows="2" class="input h-auto py-3" placeholder="Alamat lengkap"></textarea>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<div><label class="block text-xs font-bold">PSTN / Fax</label><input v-model="form.pstn" class="input"/></div>
<div><label class="block text-xs font-bold">WhatsApp Helpdesk</label><input v-model="form.wa_helpdesk" class="input"/></div>
<div><label class="block text-xs font-bold">Surel Resmi</label><input v-model="form.email" class="input"/></div>
</div>
</div>
</section>

<section class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
<div class="bg-surface-container-low border-b border-outline-variant px-4 py-3"><h2 class="font-bold text-sm">4. Kanal Publikasi Media Sosial</h2></div>
<div class="p-4 md:p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
<div v-for="s in [['instagram','ig/'],['youtube','yt/'],['tiktok','tt/'],['facebook','fb/']]" :key="s[0]"><label class="block text-xs font-bold mb-1">{{ s[0] }}</label><div class="flex border border-outline-variant rounded-md overflow-hidden"><span class="px-3 bg-surface-container border-r-2 border-outline-variant text-xs font-bold flex items-center">{{ s[1] }}</span><input v-model="form[s[0]]" class="flex-1 h-10 px-2 text-sm outline-none"/></div></div>
</div>
</section>
</div>

<div class="lg:col-span-4 space-y-6">
<section class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
<div class="bg-surface-container-low border-b border-outline-variant px-4 py-3 flex items-center justify-between"><h3 class="font-bold text-sm flex items-center gap-2"><span class="material-symbols-outlined !w-4 !h-4">database</span> Backup Basis Data</h3></div>
<div class="p-4 space-y-3">
<button @click="runBackup" :disabled="backupLoading" class="btn btn--ghost w-full disabled:opacity-50 disabled:cursor-not-allowed">{{ backupLoading ? 'Membuat backup…' : 'Jalankan Backup Sekarang' }}</button>
<p v-if="backupMsg" class="text-xs font-bold text-green-700 bg-green-50 border border-green-600 rounded-full px-2 py-1">{{ backupMsg }} <a v-if="backupFilename" @click.prevent="downloadBackup" href="#" class="underline">Download</a></p>
<p v-if="backupErr" class="text-xs font-bold text-error bg-red-50 border border-red-600 rounded-full px-2 py-1">{{ backupErr }}</p>
</div>
</section>
</div>
</div>

<Transition name="toast">
<div v-if="toast" class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-full text-sm font-bold border border-outline-variant shadow-sm" :class="toastType==='error'?'bg-error-container text-on-error-container':'bg-primary-fixed text-on-surface'">{{ toast }}</div>
</Transition>
<div class="bg-white border border-outline-variant rounded-xl p-4 shadow-md flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 min-w-0">
<div class="flex items-center gap-3 min-w-0"><span class="w-10 h-10 rounded-full bg-primary-fixed border border-outline-variant flex items-center justify-center shrink-0"><span class="material-symbols-outlined !w-5 !h-5" :class="dirty?'text-error':'text-primary'">{{ dirty?'warning':'check_circle' }}</span></span><div class="min-w-0"><span class="font-bold block">{{ dirty?'Ada perubahan yang belum disimpan':'Tidak ada perubahan yang belum disimpan' }}</span><span class="text-sm text-on-surface-variant">Tab Identitas, Logo, Kontak &amp; Sosial — terakhir disimpan pukul {{ lastSave }}</span></div></div>
<div class="flex flex-wrap gap-3"><button v-if="dirty" @click="fetchData" class="btn btn--ghost">Batalkan</button><button @click="save" :disabled="loading || !dirty" class="btn btn--primary disabled:opacity-50">{{ loading?'Menyimpan…':'Simpan Perubahan' }}</button></div>
</div>
</AppShell>
</template>
<script setup>
import { ref, watch, onMounted } from 'vue'
import BackButton from '../../Components/BackButton.vue'
import AppShell from '../../Components/AppShell.vue'
import SettingsBerita from './SettingsBerita.vue'
import SettingsGaleri from './SettingsGaleri.vue'
import SettingsPPDB from './SettingsPPDB.vue'
import SettingsAkun from './SettingsAkun.vue'
import { useSiteSettings } from '../../composables/useSiteSettings'
const { load: reloadSiteSettings } = useSiteSettings()
const form = ref({ app_name:'', nspp:'', npsn:'', jenjang:'', akreditasi:'', milad:'', tagline:'', vision:'', address:'', logo_path:'', favicon:'', kop_surat:'', hero_image:'', phone:'', pstn:'', wa_helpdesk:'', email:'', map_lat:'', map_lng:'', instagram:'', youtube:'', tiktok:'', facebook:'' })
const dirty = ref(false)
const snapshot = ref(JSON.stringify(form.value))
watch(form, () => { dirty.value = JSON.stringify(form.value) !== snapshot.value }, { deep: true })
function syncSnapshot(patch = null) {
  snapshot.value = patch ? JSON.stringify({ ...JSON.parse(snapshot.value), ...patch }) : JSON.stringify(form.value)
  dirty.value = JSON.stringify(form.value) !== snapshot.value
}
const loading = ref(false); const lastSave = ref('-'); const active = ref('identitas')
const backupLoading = ref(false); const backupMsg = ref(''); const backupErr = ref(''); const backupFilename = ref('')
const toast = ref(''); const toastType = ref('success')
const landing = ref([])
const uploading = ref({ active: false, field: null })
const fileStatus = ref({})
const brandAssets = [
  { k: 'favicon', l: 'Favicon Portal', d: 'PNG/JPG/WebP, ideal 32×32', icon: 'star' },
  { k: 'kop_surat', l: 'Watermark Rapor Digital', d: 'Grayscale 15%', icon: 'water_drop' },
  { k: 'hero_image', l: 'Header Kop Surat', d: 'PNG/JPG/WebP untuk kop A4', icon: 'article' },
]
const tabFromQuery = () => { const q = new URLSearchParams(location.search).get('tab'); if(q) active.value = q }
function notify(msg, type = 'success') {
  toast.value = msg
  toastType.value = type
  setTimeout(() => { toast.value = '' }, 3000)
}
function asset(path) {
  if (!path) return null
  return /^https?:\/\//.test(path) ? path : `${location.origin}/storage/${path}`
}
function fileNameOf(path) {
  if (!path) return ''
  const base = String(path).split(/[\\/]/).pop() || path
  return base
}
async function fetchLanding(){ try{ const r=await fetch('/api/landing-sections',{headers:{Authorization:`Bearer ${token()}`}}); if(r.ok) landing.value=await r.json(); }catch{} }
async function saveLanding(sec){ const r=await fetch('/api/landing-sections/'+sec.id,{method:'PUT',headers:{'Content-Type':'application/json',Authorization:`Bearer ${token()}`},body:JSON.stringify(sec)}); if(r.ok) notify('Section tersimpan'); else notify('Gagal simpan', 'error') }
const tabs = [{key:'identitas',label:'Identitas Dayah'},{key:'logo',label:'Logo & Favicon'},{key:'kontak',label:'Kontak & Alamat'},{key:'sosial',label:'Sosial Media'},{key:'landing',label:'Landing Page'},{key:'berita',label:'Berita & Kategori'},{key:'galeri',label:'Galeri & Album'},{key:'ppdb',label:'PPDB & Pendaftar'},{key:'akun',label:'Portal / Akun'},{key:'keamanan',label:'Keamanan & Audit Log'}]
const token = () => localStorage.getItem('madani_token')
async function fetchData(){
  try{ const r=await fetch('/api/site-settings',{headers:{Authorization:`Bearer ${token()}`}}); if(r.ok){ form.value=await r.json(); syncSnapshot() } }catch{}
}
async function save(){
  loading.value=true
  try{
    const r=await fetch('/api/site-settings',{method:'PUT',headers:{'Content-Type':'application/json',Authorization:`Bearer ${token()}`},body:JSON.stringify(form.value)})
    if(!r.ok){ const j=await r.json().catch(()=>({})); throw new Error(j.message||Object.values(j.errors||{}).flat().join(', ')||'Gagal simpan') }
    lastSave.value=new Date().toLocaleTimeString('id-ID'); syncSnapshot(); notify('Tersimpan'); reloadSiteSettings({ force: true })
  }catch(e){ notify(e.message || 'Gagal simpan', 'error') } finally{ loading.value=false}
}
async function upload(e, field){
  const input = e.target
  const file = input.files?.[0]
  if (!file) return
  if (uploading.value.active) return
  const label = field === 'logo' ? 'Logo' : (brandAssets.find(a => a.k === field)?.l || field)
  uploading.value = { active: true, field }
  fileStatus.value = { ...fileStatus.value, [field]: { ok: true, msg: `Mengunggah ${file.name}…` } }
  try {
    const fd = new FormData()
    fd.append('file', file)
    fd.append('field', field)
    const r = await fetch('/api/site-settings/upload', { method: 'POST', headers: { Authorization: `Bearer ${token()}` }, body: fd })
    const j = await r.json().catch(() => ({}))
    if (!r.ok) {
      const msg = j.message || (j.errors && Object.values(j.errors).flat().join(', ')) || `Upload ${label} gagal`
      fileStatus.value = { ...fileStatus.value, [field]: { ok: false, msg } }
      notify(msg, 'error')
      return
    }
    const path = j.path || ''
    form.value = { ...form.value, [field === 'logo' ? 'logo_path' : field]: path }
    syncSnapshot({ [field === 'logo' ? 'logo_path' : field]: path })
    fileStatus.value = { ...fileStatus.value, [field]: { ok: true, msg: `${file.name} — berhasil diunggah` } }
    notify(`${label} berhasil diunggah`)
    reloadSiteSettings({ force: true })
  } catch (err) {
    const msg = err?.message || `Upload ${label} gagal (jaringan)`
    fileStatus.value = { ...fileStatus.value, [field]: { ok: false, msg } }
    notify(msg, 'error')
  } finally {
    uploading.value = { active: false, field: null }
    input.value = ''
  }
}
async function runBackup(){
  if(backupLoading.value) return
  backupLoading.value=true; backupMsg.value=''; backupErr.value=''; backupFilename.value=''
  try{
    const r=await fetch('/api/backups',{method:'POST',headers:{Authorization:`Bearer ${token()}`}})
    const j=await r.json().catch(()=>({}))
    if(!r.ok) throw new Error(j.message || 'Backup gagal dibuat. Silakan coba lagi.')
    backupMsg.value=j.message || 'Backup berhasil dibuat.'
    backupFilename.value=j.filename || ''
  }catch(e){ backupErr.value=e.message || 'Backup gagal dibuat. Silakan coba lagi.' } finally{ backupLoading.value=false }
}
async function downloadBackup(){
  try{
    const r=await fetch('/api/backups/download/'+encodeURIComponent(backupFilename.value),{headers:{Authorization:`Bearer ${token()}`}})
    if(!r.ok) throw new Error('Download gagal')
    const blob=await r.blob(); const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download=backupFilename.value; a.click(); URL.revokeObjectURL(url)
  }catch(e){ backupErr.value=e.message }
}
onMounted(()=>{ tabFromQuery(); fetchData(); fetchLanding() })
</script>
