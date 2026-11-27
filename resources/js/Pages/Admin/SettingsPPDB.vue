<template>
<div class="ppdb-settings space-y-4">
  <div class="flex gap-2"><button @click="openCreate" class="btn btn--primary btn--sm">+ Gelombang</button></div>

  <Transition name="fade">
    <div v-if="showForm" class="bg-surface-container-low border border-outline-variant rounded-xl p-4 space-y-3">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <input v-model="form.name" placeholder="Nama gelombang *" class="input" />
        <input v-model="form.slug" placeholder="Slug (auto)" class="input" />
        <textarea v-model="form.description" placeholder="Deskripsi" rows="2" class="md:col-span-2 input"></textarea>
        <input v-model="form.start_date" type="date" class="input" />
        <input v-model="form.end_date" type="date" class="input" />
        <select v-model="form.status" class="input"><option value="buka">buka</option><option value="tutup">tutup</option><option value="archived">archived</option></select>
        <label class="flex items-center gap-2 text-xs font-bold"><input type="checkbox" v-model="form.is_public" /> Public</label>
      </div>
      <p v-if="formError" class="text-xs text-error bg-error-bg border border-error rounded-xl px-3 py-2">{{ formError }}</p>
      <div class="flex gap-2"><button @click="submit" class="btn btn--primary btn--sm">Simpan</button><button @click="showForm=false" class="btn btn--ghost btn--sm">Batal</button></div>
    </div>
  </Transition>

  <div v-if="loading" class="text-center py-6 text-sm text-on-surface-variant">Memuat...</div>
  <div v-else class="space-y-2">
    <div v-for="w in waves" :key="w.id" class="p-3 bg-surface-container-lowest border border-outline-variant rounded-xl flex items-center justify-between gap-2">
      <div><div class="font-bold text-sm">{{ w.name }}</div><div class="text-xs text-on-surface-variant">{{ (w.start_date||'').slice(0,10) }} — {{ (w.end_date||'').slice(0,10) }} · {{ w.status }} · {{ w.is_public?'public':'private' }}</div></div>
      <div class="flex gap-1"><button @click="selectedWave=w.id;loadRegs()" class="btn btn--ghost btn--sm">Pendaftar</button><button @click="openEdit(w)" class="btn btn--ghost btn--sm">Edit</button><button @click="remove(w)" class="btn btn--destructive btn--sm">Hapus</button></div>
    </div>
    <div v-if="!waves.length" class="text-center py-6 text-sm text-on-surface-variant">Belum ada gelombang.</div>
  </div>

  <Transition name="fade">
    <div v-if="selectedWave" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 space-y-3">
      <h3 class="font-bold text-sm">Pendaftar — Gelombang #{{ selectedWave }}</h3>
      <div class="flex flex-wrap gap-2">
        <input v-model="regSearch" @input="onRegSearch" placeholder="Cari nama/NISN" class="input max-w-xs" />
        <select v-model="regStatus" @change="loadRegs" class="input"><option value="">Semua status</option><option value="pending">pending</option><option value="diterima">diterima</option><option value="ditolak">ditolak</option><option value="cadangan">cadangan</option></select>
        <select v-model="regJenjang" @change="loadRegs" class="input"><option value="">Semua jalur</option><option value="lanjut">Jenjang Lanjut</option><option value="sma">Pendaftaran SMA</option><option value="smp">Pendaftaran SMP</option><option value="salafiyah">Salafiyah Santri Putri</option></select>
        <button @click="downloadRecapPdf" class="btn btn--primary btn--sm">Rekap PDF</button>
      </div>
      <div class="space-y-1">
        <div v-for="r in regs" :key="r.id" class="p-2 bg-surface-container-low border border-outline-variant rounded-xl flex items-center justify-between gap-2">
          <div class="min-w-0"><div class="font-bold text-xs truncate">{{ r.registration_number || ('#'+r.id) }} — {{ r.name }} <span class="text-on-surface-variant">({{ r.nisn||'-' }})</span></div><div class="text-xs text-on-surface-variant truncate">{{ jenjangLabel(r.jenjang) }} · {{ r.phone||'-' }}</div></div>
          <div class="flex items-center gap-1 shrink-0"><button @click="downloadRegistrationPdf(r)" class="px-2 py-1 border border-outline-variant rounded-md text-xs font-bold bg-surface-container-lowest text-on-surface">PDF</button><button @click="openDetail(r)" class="px-2 py-1 border border-outline-variant rounded-md text-xs font-bold bg-surface-container-lowest text-on-surface">Detail</button>
            <select :value="r.status" @change="e=>updateStatus(r,e.target.value)" class="px-2 py-1 border border-outline-variant rounded-md text-xs font-bold"><option value="pending">pending</option><option value="diterima">diterima</option><option value="ditolak">ditolak</option><option value="cadangan">cadangan</option></select></div>
        </div>
        <div v-if="!regs.length" class="text-center py-4 text-xs text-on-surface-variant">Belum ada pendaftar.</div>
      </div>
    </div>
  </Transition>

  <Transition name="modal-fade">
    <div v-if="detail" class="ppdb-modal-root fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeDetail">
      <div class="modal-shell w-full max-w-[960px] max-h-[90vh] flex flex-col rounded-2xl border border-outline-variant bg-surface-container-lowest text-on-surface shadow-2xl overflow-hidden" role="dialog" aria-modal="true">
        <div class="modal-header sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-outline-variant bg-surface-container-lowest/95 backdrop-blur" style="padding:24px 32px;">
          <div>
            <div class="header-kicker">Form Biodata Pendaftar</div>
            <div class="header-reg">{{ detail.registration_number || ('#'+detail.id) }}</div>
          </div>
          <button @click="closeDetail" class="icon-close" aria-label="Tutup"><span class="material-symbols-outlined">close</span></button>
        </div>

        <div class="modal-body flex-1 overflow-y-auto" style="padding:32px;">
          <p v-if="saveError" class="mb-6 text-sm text-error bg-error-bg border border-error rounded-xl px-4 py-3">{{ saveError }}</p>

          <div class="profile-card rounded-2xl border border-outline-variant bg-surface-container-low/60 flex flex-col sm:flex-row gap-6 items-start" style="padding:24px;">
            <div class="photo-wrap shrink-0">
              <img v-if="photoUrl" :src="photoUrl" alt="Foto pendaftar" class="w-[140px] aspect-[3/4] object-cover rounded-xl border border-outline-variant bg-surface-container" />
              <div v-else class="w-[140px] aspect-[3/4] rounded-xl border border-outline-variant bg-primary/10 text-primary flex items-center justify-center text-4xl font-bold select-none">{{ initials(detail.name) }}</div>
            </div>
            <div class="min-w-0 flex-1">
              <h2 class="profile-name">{{ detail.name || '—' }}</h2>
              <p class="profile-reg">{{ detail.registration_number || ('#'+detail.id) }}</p>
              <div class="flex flex-wrap gap-2 mt-4">
                <span class="badge" :class="jenjangBadgeClass(detail.jenjang)">{{ jenjangLabel(detail.jenjang) }}</span>
                <span class="badge bg-surface-container-high text-on-surface border-outline-variant">{{ detail.wave?.name || '—' }}</span>
                <span class="badge capitalize" :class="statusBadgeClass(detail.status)">{{ detail.status || 'pending' }}</span>
              </div>
              <p v-if="detail.last_edited_by" class="text-xs text-on-surface-variant mt-4">Terakhir diubah oleh <b class="text-on-surface">{{ detail.last_edited_by }}</b> pada {{ fmtDateTime(detail.last_edited_at) }}</p>
            </div>
          </div>

          <div v-if="!editMode" class="mt-6 space-y-6">
            <section class="form-section">
              <h3 class="section-title"><span class="material-symbols-outlined">person</span>Data Pribadi</h3>
              <div class="section-grid">
                <RField label="NIK" :value="detail.nik" mono />
                <RField label="Jenis Kelamin" :value="genderLabel(detail.gender)" />
                <RField label="Tempat & Tanggal Lahir" :value="ttl(detail)" class="md:col-span-2" />
                <RField label="Golongan Darah" :value="detail.blood_type" />
                <RField label="NISN" :value="detail.nisn" mono />
              </div>
            </section>

            <section class="form-section">
              <h3 class="section-title"><span class="material-symbols-outlined">school</span>Data Pendaftaran</h3>
              <div class="section-grid">
                <RField label="Jalur" :value="jenjangLabel(detail.jenjang)" />
                <RField label="Gelombang" :value="detail.wave?.name" />
                <RField label="Sekolah Asal" :value="detail.origin_school_name" class="md:col-span-2" />
              </div>
            </section>

            <section class="form-section">
              <h3 class="section-title"><span class="material-symbols-outlined">home</span>Alamat Lengkap</h3>
              <div class="section-grid">
                <RField label="Alamat" :value="detail.address" class="md:col-span-2" />
                <RField label="Desa/Kelurahan" :value="detail.village" />
                <RField label="Kecamatan" :value="detail.district" />
                <RField label="Kabupaten/Kota" :value="detail.city" />
                <RField label="Provinsi" :value="detail.province" />
              </div>
            </section>

            <section class="form-section">
              <h3 class="section-title"><span class="material-symbols-outlined">family_restroom</span>Data Orang Tua & Wali</h3>
              <div class="section-grid">
                <RField label="Ayah" :value="detail.father_name" />
                <RField label="Ibu" :value="detail.mother_name" />
                <RField label="Wali" :value="detail.guardian_not_required ? 'Tidak diperlukan' : detail.guardian_name" />
                <RField label="Pembiaya" :value="detail.financier_name" />
                <RField label="Hubungan Wali" :value="detail.guardian_not_required ? '—' : detail.guardian_relation" />
                <RField label="Hubungan Pembiaya" :value="detail.financier_relation" />
              </div>
            </section>

            <section class="form-section">
              <h3 class="section-title"><span class="material-symbols-outlined">contact_phone</span>Kontak Darurat & Mahram</h3>
              <div class="section-grid">
                <RField label="Nama Kontak Darurat" :value="detail.emergency_name" />
                <RField label="No. HP Darurat" :value="detail.emergency_phone" />
                <RField label="Hubungan Darurat" :value="detail.emergency_relation" />
                <RField label="Mahram" :value="detail.mahram_name" />
              </div>
            </section>

            <section class="form-section">
              <h3 class="section-title"><span class="material-symbols-outlined">history</span>Riwayat</h3>
              <div class="section-grid">
                <RField label="Riwayat Kesehatan" :value="(detail.medical_history||[]).join(', ') || detail.medical_history_note" class="md:col-span-2" />
                <RField label="Catatan" :value="detail.notes" class="md:col-span-2" />
              </div>
            </section>
          </div>

          <div v-else class="mt-6 space-y-6">
            <section class="form-section">
              <h3 class="section-title"><span class="material-symbols-outlined">person</span>Data Pribadi</h3>
              <div class="section-grid">
                <EField label="NIK *" v-model="formEdit.nik" :error="fieldErrors.nik" mono />
                <ESelect label="Jenis Kelamin *" v-model="formEdit.gender" :error="fieldErrors.gender" :options="[{v:'L',t:'Laki-laki'},{v:'P',t:'Perempuan'}]" :disabled="formEdit.jenjang==='salafiyah'" />
                <EField label="Nama Lengkap *" v-model="formEdit.name" :error="fieldErrors.name" class="md:col-span-2" />
                <EField label="Tempat Lahir *" v-model="formEdit.birth_place" :error="fieldErrors.birth_place" />
                <EField label="Tanggal Lahir *" v-model="formEdit.birth_date" type="date" :error="fieldErrors.birth_date" />
                <ESelect label="Golongan Darah *" v-model="formEdit.blood_type" :error="fieldErrors.blood_type" :options="bloodOptions" />
                <EField label="NISN" v-model="formEdit.nisn" :error="fieldErrors.nisn" mono />
              </div>
            </section>

            <section class="form-section">
              <h3 class="section-title"><span class="material-symbols-outlined">school</span>Data Pendaftaran</h3>
              <div class="section-grid">
                <ESelect label="Jalur *" v-model="formEdit.jenjang" :error="fieldErrors.jenjang" :options="jenjangOptions" @change="onEditJenjang" />
                <ESelect label="Gelombang *" v-model="formEdit.wave_id" :error="fieldErrors.wave_id" :options="waveOptions" />
                <EField label="Sekolah Asal *" v-model="formEdit.origin_school_name" :error="fieldErrors.origin_school_name" class="md:col-span-2" />
              </div>
            </section>

            <section class="form-section">
              <h3 class="section-title"><span class="material-symbols-outlined">home</span>Alamat Lengkap</h3>
              <div class="section-grid">
                <EArea label="Alamat *" v-model="formEdit.address" :error="fieldErrors.address" class="md:col-span-2" />
                <EField label="Desa/Kelurahan *" v-model="formEdit.village" :error="fieldErrors.village" />
                <EField label="Kecamatan *" v-model="formEdit.district" :error="fieldErrors.district" />
                <EField label="Kabupaten/Kota *" v-model="formEdit.city" :error="fieldErrors.city" />
                <EField label="Provinsi *" v-model="formEdit.province" :error="fieldErrors.province" />
              </div>
            </section>

            <section class="form-section">
              <h3 class="section-title"><span class="material-symbols-outlined">family_restroom</span>Data Orang Tua & Wali</h3>
              <div class="section-grid">
                <EField label="Nama Ayah *" v-model="formEdit.father_name" :error="fieldErrors.father_name" />
                <EField label="No. KK Ayah *" v-model="formEdit.father_family_card" :error="fieldErrors.father_family_card" />
                <EField label="Nama Ibu *" v-model="formEdit.mother_name" :error="fieldErrors.mother_name" />
                <EField label="No. KK Ibu *" v-model="formEdit.mother_family_card" :error="fieldErrors.mother_family_card" />
                <label class="flex items-center gap-2 text-sm font-medium md:col-span-2"><input type="checkbox" v-model="formEdit.guardian_not_required" /> Wali tidak diperlukan</label>
                <EField v-if="!formEdit.guardian_not_required" label="Nama Wali *" v-model="formEdit.guardian_name" :error="fieldErrors.guardian_name" />
                <EField v-if="!formEdit.guardian_not_required" label="Hubungan Wali *" v-model="formEdit.guardian_relation" :error="fieldErrors.guardian_relation" />
                <EField label="Nama Pembiaya *" v-model="formEdit.financier_name" :error="fieldErrors.financier_name" />
                <EField label="Hubungan Pembiaya *" v-model="formEdit.financier_relation" :error="fieldErrors.financier_relation" />
              </div>
            </section>

            <section class="form-section">
              <h3 class="section-title"><span class="material-symbols-outlined">contact_phone</span>Kontak Darurat & Mahram</h3>
              <div class="section-grid">
                <EField label="Nama Kontak Darurat *" v-model="formEdit.emergency_name" :error="fieldErrors.emergency_name" />
                <EField label="No. HP Darurat *" v-model="formEdit.emergency_phone" :error="fieldErrors.emergency_phone" />
                <EField label="Hubungan Darurat *" v-model="formEdit.emergency_relation" :error="fieldErrors.emergency_relation" />
                <EField label="Nama Mahram" v-model="formEdit.mahram_name" :error="fieldErrors.mahram_name" />
              </div>
            </section>

            <section class="form-section">
              <h3 class="section-title"><span class="material-symbols-outlined">history</span>Riwayat</h3>
              <div class="section-grid">
                <EArea label="Riwayat Kesehatan / Catatan" v-model="formEdit.medical_history_note" :error="fieldErrors.medical_history_note" class="md:col-span-2" />
                <EArea label="Catatan Admin" v-model="formEdit.notes" :error="fieldErrors.notes" class="md:col-span-2" />
                <EField label="Nama Pernyataan *" v-model="formEdit.declarant_name" :error="fieldErrors.declarant_name" class="md:col-span-2" />
                <ESelect label="Status" v-model="formEdit.status" :error="fieldErrors.status" :options="statusOptions" />
              </div>
            </section>
          </div>
        </div>

        <div class="modal-footer sticky bottom-0 z-20 flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 border-t border-outline-variant bg-surface-container-lowest/95 backdrop-blur" style="padding:20px 32px;">
          <template v-if="!editMode">
            <button @click="startEdit" class="fbtn fbtn-primary"><span class="material-symbols-outlined text-[18px]">edit</span>Edit Data</button>
            <button @click="downloadRegistrationPdf(detail)" class="fbtn fbtn-outline"><span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>Cetak PDF</button>
            <button @click="closeDetail" class="fbtn fbtn-ghost">Tutup</button>
          </template>
          <template v-else>
            <button @click="cancelEdit" class="fbtn fbtn-ghost">Batal</button>
            <button @click="saveEdit" :disabled="saving" class="fbtn fbtn-primary"><span v-if="saving" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>{{ saving ? 'Menyimpan…' : 'Simpan Perubahan' }}</button>
          </template>
        </div>
      </div>
    </div>
  </Transition>

  <Transition name="fade">
    <div v-if="toast" class="fixed bottom-5 right-5 z-[60] px-4 py-3 rounded-xl shadow-lg border text-sm font-semibold" :class="toastType==='error' ? 'bg-error-bg text-error border-error' : 'bg-success-solid text-white border-success-solid'">{{ toast }}</div>
  </Transition>
</div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, defineComponent, h } from 'vue'

const token = () => localStorage.getItem('madani_token')
const auth = () => ({ Authorization: `Bearer ${token()}`, Accept: 'application/json' })
const waves = ref([]), loading = ref(false), showForm = ref(false), formError = ref('')
const toast = ref(''), toastType = ref('success')
const form = ref({ name: '', slug: '', description: '', start_date: '', end_date: '', status: 'buka', is_public: true })
const selectedWave = ref(null), regs = ref([]), regSearch = ref(''), regStatus = ref(''), regJenjang = ref('')
const detail = ref(null), photoUrl = ref('')
const editMode = ref(false), saving = ref(false), saveError = ref(''), fieldErrors = ref({})
const formEdit = ref({})

const jenjangLabels = { lanjut: 'Jenjang Lanjut', sma: 'Pendaftaran SMA', smp: 'Pendaftaran SMP', salafiyah: 'Salafiyah Santri Putri' }
const jenjangLabel = (v) => jenjangLabels[v] || '—'
const jenjangOptions = Object.entries(jenjangLabels).map(([v, t]) => ({ v, t }))
const waveOptions = computed(() => waves.value.map(w => ({ v: String(w.id), t: w.name })))
const bloodOptions = ['A','B','AB','O','Tidak Diketahui'].map(v => ({ v, t: v }))
const statusOptions = [{v:'pending',t:'Pending'},{v:'diterima',t:'Diterima'},{v:'ditolak',t:'Ditolak'},{v:'cadangan',t:'Cadangan'}]

function initials(name) { if (!name) return '?'; return String(name).trim().split(/\s+/).slice(0,2).map(s=>s[0]?.toUpperCase()||'').join('') || '?' }
function genderLabel(g) { return g === 'L' ? 'Laki-laki' : g === 'P' ? 'Perempuan' : (g || '—') }
function showToast(m, t = 'success') { toast.value = m; toastType.value = t; setTimeout(() => toast.value = '', 3500) }
function clearPhoto() { if (photoUrl.value) URL.revokeObjectURL(photoUrl.value); photoUrl.value = '' }
async function loadPhoto(reg) { clearPhoto(); if (!reg?.photo_path) return; try { const res = await fetch('/api/ppdb-registrations/' + reg.id + '/photo', { headers: auth() }); if (res.ok) photoUrl.value = URL.createObjectURL(await res.blob()) } catch {} }
function fmtDateTime(d) { if (!d) return '—'; try { return new Date(d).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) } catch { return d } }
function ttl(r) { const p = r.birth_place || ''; const d = r.birth_date ? String(r.birth_date).slice(0,10) : ''; return (p || d) ? `${p}${p && d ? ', ' : ''}${d}` : '—' }

function jenjangBadgeClass(j) { return { lanjut:'bg-primary-container text-on-primary-container border-primary-container', sma:'bg-tertiary-container text-on-tertiary-container border-tertiary-container', smp:'bg-secondary-container text-on-secondary-container border-secondary-container', salafiyah:'bg-warning-bg text-warning border-warning-solid' }[j] || 'bg-surface-container text-on-surface-variant border-outline-variant' }
function statusBadgeClass(s) { return { pending:'bg-warning-bg text-warning border-warning-solid', diterima:'bg-success-bg text-success border-success-solid', ditolak:'bg-error-bg text-error border-error-solid', cadangan:'bg-info-bg text-info border-info-solid' }[s] || 'bg-surface-container text-on-surface-variant border-outline-variant' }

async function openDetail(r) {
  try {
    const res = await fetch('/api/ppdb-registrations/' + r.id, { headers: auth() })
    if (res.ok) { detail.value = await res.json(); await loadPhoto(detail.value); editMode.value = false; fieldErrors.value = {}; saveError.value = ''; return }
  } catch {}
  detail.value = r; await loadPhoto(r); editMode.value = false
}
function closeDetail() { if (editMode.value && !confirm('Batalkan perubahan yang belum disimpan?')) return; editMode.value = false; detail.value = null; clearPhoto(); fieldErrors.value = {}; saveError.value = '' }

function editablePayload(d) {
  const keys = ['wave_id','jenjang','name','nik','gender','birth_place','birth_date','nisn','phone','email','address','village','district','city','province','origin_school_name','blood_type','father_family_card','father_name','mother_family_card','mother_name','guardian_not_required','guardian_name','guardian_relation','financier_name','financier_relation','emergency_name','emergency_phone','emergency_relation','mahram_name','medical_history_note','notes','declarant_name','status']
  const out = {}
  keys.forEach(k => {
    let v = d[k]
    if (k === 'wave_id') v = d.wave_id || d.wave?.id || ''
    if (k === 'birth_date' && v) v = String(v).slice(0,10)
    if (v === null || v === undefined) v = (k === 'guardian_not_required') ? false : ''
    out[k] = v
  })
  out.wave_id = String(out.wave_id || '')
  return out
}
function startEdit() { formEdit.value = editablePayload(detail.value); editMode.value = true; fieldErrors.value = {}; saveError.value = '' }
function cancelEdit() { editMode.value = false; fieldErrors.value = {}; saveError.value = '' }
function onEditJenjang() { if (formEdit.value.jenjang === 'salafiyah') formEdit.value.gender = 'P' }

async function saveEdit() {
  saving.value = true; fieldErrors.value = {}; saveError.value = ''
  try {
    const payload = { ...formEdit.value }
    if (payload.wave_id) payload.wave_id = Number(payload.wave_id)
    ;['nisn','phone','email','mahram_name','medical_history_note','notes'].forEach(k => { if (payload[k] === '') payload[k] = null })
    if (payload.guardian_not_required) { payload.guardian_name = payload.guardian_name || null; payload.guardian_relation = payload.guardian_relation || null }
    const res = await fetch('/api/ppdb-registrations/' + detail.value.id, { method: 'PATCH', headers: { 'Content-Type': 'application/json', ...auth() }, body: JSON.stringify(payload) })
    const j = await res.json().catch(() => ({}))
    if (!res.ok) {
      if (j.errors) fieldErrors.value = Object.fromEntries(Object.entries(j.errors).map(([k,v]) => [k, Array.isArray(v)?v[0]:v]))
      saveError.value = j.message || 'Gagal menyimpan perubahan.'
      return
    }
    detail.value = j
    editMode.value = false
    showToast('Data berhasil diperbarui')
    await loadRegs()
  } catch (e) { saveError.value = e.message || 'Gagal menyimpan perubahan.' }
  finally { saving.value = false }
}

let timer = null
function onRegSearch() { clearTimeout(timer); timer = setTimeout(loadRegs, 350) }
function openCreate() { form.value = { name: '', slug: '', description: '', start_date: '', end_date: '', status: 'buka', is_public: true }; formError.value = ''; showForm.value = true }
function openEdit(w) { form.value = { id: w.id, name: w.name, slug: w.slug, description: w.description || '', start_date: (w.start_date||'').slice(0,10) || '', end_date: (w.end_date||'').slice(0,10) || '', status: w.status, is_public: !!w.is_public }; formError.value = ''; showForm.value = true }
async function loadWaves() { loading.value = true; try { const r = await fetch('/api/ppdb-waves', { headers: auth() }); if (!r.ok) throw new Error('Gagal memuat daftar gelombang'); const j = await r.json(); waves.value = j.data ?? j; return true } catch (e) { showToast(e.message, 'error'); return false } finally { loading.value = false } }
async function submit() { formError.value = ''; if (!form.value.name) { formError.value = 'Nama wajib'; return } const payload = { ...form.value }; if (!payload.slug) delete payload.slug; if (!payload.start_date) delete payload.start_date; if (!payload.end_date) delete payload.end_date; const isEdit = !!form.value.id; const url = isEdit ? '/api/ppdb-waves/' + form.value.id : '/api/ppdb-waves'; const method = isEdit ? 'PUT' : 'POST'; const r = await fetch(url, { method, headers: { 'Content-Type': 'application/json', ...auth() }, body: JSON.stringify(payload) }); if (!r.ok) { const j = await r.json().catch(()=>({})); formError.value = j.message || Object.values(j.errors || {}).flat().join(', '); return } showForm.value = false; await loadWaves(); showToast(isEdit ? 'Gelombang diperbarui' : 'Gelombang dibuat') }
async function remove(w) { if (!confirm('Hapus gelombang ' + w.name + '?')) return; const r = await fetch('/api/ppdb-waves/' + w.id, { method: 'DELETE', headers: auth() }); if (!r.ok) { const j = await r.json().catch(() => ({})); showToast(j.message || 'Gagal menghapus gelombang', 'error'); return } await loadWaves(); showToast('Gelombang dihapus') }
async function loadRegs() { if (!selectedWave.value) return; try { const q = new URLSearchParams(); q.set('wave_id', String(selectedWave.value)); if (regSearch.value) q.set('search', regSearch.value); if (regStatus.value) q.set('status', regStatus.value); if (regJenjang.value) q.set('jenjang', regJenjang.value); const r = await fetch('/api/ppdb-registrations?' + q, { headers: auth() }); if (!r.ok) { const j = await r.json().catch(()=>({})); throw new Error(j.message || 'Gagal memuat pendaftar') } const j = await r.json(); regs.value = j.data ?? j } catch (e) { showToast(e.message, 'error') } }
async function updateStatus(r, status) { const res = await fetch('/api/ppdb-registrations/' + r.id, { method: 'PUT', headers: { 'Content-Type': 'application/json', ...auth() }, body: JSON.stringify({ status }) }); if (!res.ok) { showToast('Gagal update', 'error'); return } r.status = status; if (detail.value?.id === r.id) detail.value.status = status; showToast('Status diperbarui') }
async function downloadBlob(url, filename) { try { const res = await fetch(url, { headers: auth() }); if (!res.ok) { const j = await res.json().catch(()=>({})); showToast(j.message || j.report || 'Gagal mengunduh PDF', 'error'); return } const href = URL.createObjectURL(await res.blob()); const a = document.createElement('a'); a.href = href; a.download = filename; document.body.appendChild(a); a.click(); a.remove(); setTimeout(() => URL.revokeObjectURL(href), 1500); showToast('PDF berhasil diunduh') } catch (e) { showToast(e.message || 'Gagal mengunduh PDF', 'error') } }
function downloadRegistrationPdf(r) { downloadBlob('/api/ppdb-registrations/' + r.id + '/pdf', 'formulir-ppdb-' + (r.registration_number || r.id) + '.pdf') }
function downloadRecapPdf() { const q = new URLSearchParams(); if (selectedWave.value) q.set('wave_id', String(selectedWave.value)); if (regStatus.value) q.set('status', regStatus.value); if (regJenjang.value) q.set('jenjang', regJenjang.value); if (regSearch.value) q.set('search', regSearch.value); downloadBlob('/api/ppdb-registrations/pdf?' + q, 'rekap-ppdb.pdf') }

function onKey(e) { if (e.key === 'Escape' && detail.value) closeDetail() }
onMounted(() => { loadWaves(); window.addEventListener('keydown', onKey) })
onUnmounted(() => { window.removeEventListener('keydown', onKey); clearPhoto() })

const RField = defineComponent({
  props: { label: String, value: [String, Number], mono: Boolean },
  setup(props, { attrs }) {
    return () => h('div', { class: ['field', attrs.class] }, [
      h('div', { class: 'field-label' }, props.label),
      h('div', { class: ['field-box', props.mono ? 'mono' : '', (!props.value && props.value !== 0) ? 'is-empty' : ''] }, (props.value || props.value === 0) ? String(props.value) : '—')
    ])
  }
})
const EField = defineComponent({
  props: { label: String, modelValue: [String, Number], type: { default: 'text' }, error: String, mono: Boolean, disabled: Boolean },
  emits: ['update:modelValue'],
  setup(props, { emit, attrs }) {
    return () => h('div', { class: ['field', attrs.class] }, [
      h('label', { class: 'field-label' }, props.label),
      h('input', { class: ['field-input', props.mono ? 'mono' : '', props.error ? 'has-error' : ''], type: props.type, value: props.modelValue ?? '', disabled: props.disabled, onInput: e => emit('update:modelValue', e.target.value) }),
      props.error ? h('p', { class: 'field-error' }, props.error) : null
    ])
  }
})
const EArea = defineComponent({
  props: { label: String, modelValue: [String, Number], error: String },
  emits: ['update:modelValue'],
  setup(props, { emit, attrs }) {
    return () => h('div', { class: ['field', attrs.class] }, [
      h('label', { class: 'field-label' }, props.label),
      h('textarea', { class: ['field-input field-textarea', props.error ? 'has-error' : ''], value: props.modelValue ?? '', onInput: e => emit('update:modelValue', e.target.value) }),
      props.error ? h('p', { class: 'field-error' }, props.error) : null
    ])
  }
})
const ESelect = defineComponent({
  props: { label: String, modelValue: [String, Number], options: Array, error: String, disabled: Boolean },
  emits: ['update:modelValue', 'change'],
  setup(props, { emit, attrs }) {
    return () => h('div', { class: ['field', attrs.class] }, [
      h('label', { class: 'field-label' }, props.label),
      h('select', { class: ['field-input', props.error ? 'has-error' : ''], value: props.modelValue ?? '', disabled: props.disabled, onChange: e => { emit('update:modelValue', e.target.value); emit('change', e.target.value) } },
        [h('option', { value: '' }, '— Pilih —'), ...(props.options||[]).map(o => h('option', { value: o.v }, o.t))]
      ),
      props.error ? h('p', { class: 'field-error' }, props.error) : null
    ])
  }
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

.ppdb-modal-root { font-family: 'Plus Jakarta Sans', Inter, system-ui, -apple-system, sans-serif; line-height: 1.6; }
.input { width: 100%; padding: 8px 10px; border: 1px solid var(--color-outline-variant); border-radius: 10px; background: var(--color-surface-container-lowest); font-size: 13px; color: var(--color-on-surface); }
.btn { padding: 8px 12px; border: 1px solid var(--color-outline-variant); border-radius: 10px; background: var(--color-surface-container-lowest); font-size: 12px; font-weight: 700; color: var(--color-on-surface); }
.btn--sm { padding: 6px 10px; font-size: 11px; }
.btn--primary { background: var(--color-primary); color: var(--color-on-primary); border-color: var(--color-primary); }
.btn--ghost { background: transparent; }
.btn--destructive { background: var(--color-error-bg); color: var(--color-error); border-color: var(--color-error); }

.header-kicker { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-on-surface-variant); }
.header-reg { font-size: 14px; font-weight: 600; color: var(--color-on-surface); margin-top: 2px; }
.icon-close { width: 40px; height: 40px; border-radius: 999px; border: 1px solid var(--color-outline-variant); display: inline-flex; align-items: center; justify-content: center; color: var(--color-on-surface-variant); background: transparent; cursor: pointer; }
.icon-close:hover { background: var(--color-surface-container-high); color: var(--color-on-surface); }

.profile-name { font-size: 24px; font-weight: 700; line-height: 1.3; margin: 0; color: var(--color-on-surface); }
.profile-reg { font-size: 13px; color: var(--color-on-surface-variant); margin-top: 4px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; }
.badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; border: 1px solid; font-size: 12px; font-weight: 600; }

.form-section { background: var(--color-surface-container-low, rgba(255,255,255,0.03)); border: 1px solid var(--color-outline-variant); border-radius: 16px; padding: 24px 28px; }
.section-title { display: flex; align-items: center; gap: 10px; font-size: 16px; font-weight: 600; color: var(--color-on-surface); margin: 0 0 16px; padding-bottom: 12px; border-bottom: 1px solid var(--color-outline-variant); }
.section-title .material-symbols-outlined { font-size: 20px; color: var(--color-primary); }
.section-grid { display: grid; grid-template-columns: 1fr; gap: 20px 24px; }
@media (min-width: 768px) { .section-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

.field { min-width: 0; }
.field-label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--color-on-surface-variant); margin-bottom: 6px; display: block; }
.field-box { min-height: 46px; padding: 12px 16px; border-radius: 8px; border: 1px solid var(--color-outline-variant); background: color-mix(in srgb, var(--color-surface-container-lowest) 70%, black 6%); font-size: 15px; font-weight: 500; color: var(--color-on-surface); display: flex; align-items: center; word-break: break-word; }
.field-box.is-empty { color: var(--color-on-surface-variant); opacity: 0.7; font-weight: 400; }
.field-input { width: 100%; min-height: 46px; padding: 12px 16px; border-radius: 8px; border: 1px solid var(--color-outline-variant); background: color-mix(in srgb, var(--color-surface-container-lowest) 70%, black 6%); font-size: 15px; font-weight: 500; color: var(--color-on-surface); font-family: inherit; outline: none; transition: border-color .15s, box-shadow .15s; }
.field-input:focus { border-color: var(--color-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 20%, transparent); }
.field-input.has-error { border-color: var(--color-error); }
.field-input:disabled { opacity: 0.7; cursor: not-allowed; }
.field-textarea { min-height: 96px; resize: vertical; }
.mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; font-variant-numeric: tabular-nums; letter-spacing: 0.02em; }
.field-error { font-size: 12px; color: var(--color-error); margin-top: 6px; }

.fbtn { height: 42px; padding: 0 18px; border-radius: 8px; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid transparent; cursor: pointer; transition: all .15s ease; }
.fbtn-primary { background: var(--color-primary); color: var(--color-on-primary); border-color: var(--color-primary); }
.fbtn-primary:hover { filter: brightness(1.05); }
.fbtn-outline { background: transparent; color: var(--color-primary); border-color: var(--color-primary); }
.fbtn-outline:hover { background: color-mix(in srgb, var(--color-primary) 10%, transparent); }
.fbtn-ghost { background: transparent; color: var(--color-on-surface-variant); border-color: var(--color-outline-variant); }
.fbtn-ghost:hover { background: var(--color-surface-container-high); color: var(--color-on-surface); }
.fbtn:disabled { opacity: 0.6; cursor: not-allowed; }
@media (max-width: 640px) { .fbtn { width: 100%; } .profile-card { flex-direction: column; } }

.modal-fade-enter-active,.modal-fade-leave-active{ transition: opacity .2s ease; }
.modal-fade-enter-from,.modal-fade-leave-to{ opacity: 0; }
.fade-enter-active,.fade-leave-active{ transition: opacity .2s ease; }
.fade-enter-from,.fade-leave-to{ opacity: 0; }
</style>
