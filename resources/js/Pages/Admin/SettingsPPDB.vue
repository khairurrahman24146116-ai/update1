<template>
<div class="space-y-4">
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
<p v-if="formError" class="text-xs text-error bg-red-50 border border-red-600 rounded-full px-2 py-1">{{ formError }}</p>
<div class="flex gap-2"><button @click="submit" class="btn btn--primary btn--sm">Simpan</button><button @click="showForm=false" class="btn btn--ghost btn--sm">Batal</button></div>
</div>
</Transition>
<div v-if="loading" class="text-center py-6 text-sm text-on-surface-variant">Memuat...</div>
<div v-else class="space-y-2">
<div v-for="w in waves" :key="w.id" class="p-3 bg-white border border-outline-variant rounded-xl flex items-center justify-between gap-2 transition-transform duration-200 ease-in-out hover:-translate-y-1">
<div><div class="font-bold text-sm">{{ w.name }}</div><div class="text-xs text-on-surface-variant">{{ w.start_date?.slice(0,10) }} — {{ w.end_date?.slice(0,10) }} • {{ w.status }} • {{ w.is_public?'public':'private' }}</div></div>
<div class="flex gap-1"><button @click="selectedWave=w.id;loadRegs()" class="btn btn--ghost btn--sm">Pendaftar</button><button @click="openEdit(w)" class="btn btn--ghost btn--sm">Edit</button><button @click="remove(w)" class="btn btn--destructive btn--sm">Hapus</button></div>
</div>
<div v-if="!waves.length" class="text-center py-6 text-sm text-on-surface-variant">Belum ada gelombang.</div>
</div>
<Transition name="fade">
<div v-if="selectedWave" class="bg-white border border-outline-variant rounded-xl p-4 space-y-3">
<h3 class="font-bold text-sm">Pendaftar — Gelombang #{{ selectedWave }}</h3>
<div class="flex flex-wrap gap-2">
<input v-model="regSearch" @input="onRegSearch" placeholder="Cari nama/NISN" class="input max-w-xs" />
<select v-model="regStatus" @change="loadRegs" class="input"><option value="">Semua status</option><option value="pending">pending</option><option value="diterima">diterima</option><option value="ditolak">ditolak</option><option value="cadangan">cadangan</option></select>
</div>
<div class="space-y-1">
<div v-for="r in regs" :key="r.id" class="p-2 bg-surface-container-low border border-outline-variant rounded-xl flex items-center justify-between gap-2 transition-transform duration-200 ease-in-out hover:-translate-y-1">
<div class="min-w-0"><div class="font-bold text-xs truncate">{{ r.registration_number || ('#'+r.id) }} — {{ r.name }} <span class="text-on-surface-variant">({{ r.nisn||'-' }})</span></div><div class="text-xs text-on-surface-variant truncate">{{ r.phone||'-' }} • {{ r.email||'-' }}</div></div>
<div class="flex items-center gap-1 shrink-0"><button @click="openDetail(r)" class="px-2 py-1 border border-outline-variant rounded-md text-xs font-bold bg-white">Detail</button>
<select :value="r.status" @change="e=>updateStatus(r,e.target.value)" class="px-2 py-1 border border-outline-variant rounded-md text-xs font-bold"><option value="pending">pending</option><option value="diterima">diterima</option><option value="ditolak">ditolak</option><option value="cadangan">cadangan</option></select></div>
</div>
<div v-if="!regs.length" class="text-center py-4 text-xs text-on-surface-variant">Belum ada pendaftar.</div>
</div>
</div>
</Transition>
<Transition name="fade">
<div v-if="detail" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="detail=null">
<div class="bg-white rounded-xl border border-outline-variant shadow-lg max-w-2xl w-full max-h-[85vh] overflow-y-auto p-4 space-y-2">
<div class="flex items-center justify-between"><h3 class="font-bold text-sm">{{ detail.registration_number || ('#'+detail.id) }} — {{ detail.name }}</h3><button @click="detail=null" class="btn btn--ghost btn--sm">Tutup</button></div>
<img v-if="photoUrl" :src="photoUrl" alt="Pas foto" class="w-20 h-28 object-cover rounded-lg border border-outline-variant" />
<div class="grid grid-cols-1 sm:grid-cols-2 gap-1 text-xs">
<div><b>NIK:</b> {{ detail.nik||'-' }}</div><div><b>JK:</b> {{ detail.gender||'-' }}</div>
<div><b>TTL:</b> {{ detail.birth_place||'-' }}, {{ detail.birth_date?.slice(0,10)||'-' }}</div><div><b>Gol. darah:</b> {{ detail.blood_type||'-' }}</div>
<div><b>Sekolah asal:</b> {{ detail.origin_school_name||'-' }}</div><div><b>NISN:</b> {{ detail.nisn||'-' }}</div>
<div class="sm:col-span-2"><b>Alamat:</b> {{ detail.address||'-' }}, {{ detail.village||'' }} {{ detail.district||'' }} {{ detail.city||'' }} {{ detail.province||'' }}</div>
<div><b>Ayah:</b> {{ detail.father_name||'-' }}</div><div><b>Ibu:</b> {{ detail.mother_name||'-' }}</div>
<div><b>Pembiaya:</b> {{ detail.financier_name||'-' }} ({{ detail.financier_relation||'-' }})</div><div><b>Darurat:</b> {{ detail.emergency_name||'-' }} ({{ detail.emergency_relation||'-' }})</div>
<div><b>Wali:</b> {{ detail.guardian_not_required ? 'Tidak diperlukan' : (detail.guardian_name||'-') }}</div><div><b>Mahram:</b> {{ detail.mahram_name||'-' }}</div>
<div class="sm:col-span-2"><b>Riwayat:</b> {{ (detail.medical_history||[]).join(', ')||'-' }}</div>
</div>
</div>
</div>
</Transition>
<p v-if="msg" class="text-xs font-bold px-2 py-1 rounded-full border border-outline-variant" :class="msgType==='error'?'bg-red-50 text-error':'bg-green-50 text-green-700'">{{ msg }}</p>
</div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
const token=()=>localStorage.getItem('madani_token')
const auth=()=>({Authorization:`Bearer ${token()}`})
const waves=ref([]), loading=ref(false), showForm=ref(false), formError=ref(''), msg=ref(''), msgType=ref('success')
const form=ref({name:'',slug:'',description:'',start_date:'',end_date:'',status:'buka',is_public:true})
const selectedWave=ref(null), regs=ref([]), regSearch=ref(''), regStatus=ref(''), detail=ref(null), photoUrl=ref('')
function clearPhoto(){ if(photoUrl.value) URL.revokeObjectURL(photoUrl.value); photoUrl.value='' }
async function loadPhoto(reg){ clearPhoto(); if(!reg.photo_path) return; try{ const res=await fetch('/api/ppdb-registrations/'+reg.id+'/photo',{headers:auth()}); if(!res.ok) return; photoUrl.value=URL.createObjectURL(await res.blob()) }catch{} }
async function openDetail(r){ try{ const res=await fetch('/api/ppdb-registrations/'+r.id,{headers:auth()}); if(res.ok){ detail.value=await res.json(); await loadPhoto(detail.value); return } }catch{} detail.value=r; await loadPhoto(r) }
let timer=null
function notify(m,t='success'){msg.value=m;msgType.value=t;setTimeout(()=>msg.value='',3000)}
function onRegSearch(){clearTimeout(timer);timer=setTimeout(loadRegs,350)}
function openCreate(){form.value={name:'',slug:'',description:'',start_date:'',end_date:'',status:'buka',is_public:true};formError.value='';showForm.value=true}
function openEdit(w){form.value={id:w.id,name:w.name,slug:w.slug,description:w.description||'',start_date:w.start_date?.slice(0,10)||'',end_date:w.end_date?.slice(0,10)||'',status:w.status,is_public:!!w.is_public};showForm.value=true}
async function loadWaves(){loading.value=true;try{const r=await fetch('/api/ppdb-waves',{headers:auth()});if(!r.ok)throw new Error('Gagal memuat daftar gelombang');const j=await r.json();waves.value=j.data??j;return true}catch(e){notify(e.message,'error');return false}finally{loading.value=false}}
async function submit(){formError.value='';if(!form.value.name){formError.value='Nama wajib';return}const payload={...form.value};if(!payload.slug) delete payload.slug;if(!payload.start_date) delete payload.start_date;if(!payload.end_date) delete payload.end_date;const isEdit=!!form.value.id;const url=isEdit?'/api/ppdb-waves/'+form.value.id:'/api/ppdb-waves';const method=isEdit?'PUT':'POST';const r=await fetch(url,{method,headers:{'Content-Type':'application/json',...auth()},body:JSON.stringify(payload)});if(!r.ok){const j=await r.json();formError.value=j.message||Object.values(j.errors||{}).flat().join(', ');return}showForm.value=false;const reloaded=await loadWaves();if(!reloaded){notify((isEdit?'Gelombang diperbarui, tetapi gagal memuat ulang daftar':'Gelombang dibuat, tetapi gagal memuat ulang daftar'),'error');return}notify(isEdit?'Gelombang diperbarui':'Gelombang dibuat')}
async function remove(w){if(!confirm('Hapus gelombang '+w.name+'?'))return;const r=await fetch('/api/ppdb-waves/'+w.id,{method:'DELETE',headers:auth()});if(!r.ok){const j=await r.json().catch(()=>({}));notify(j.message||'Gagal menghapus gelombang','error');return}const reloaded=await loadWaves();if(!reloaded){notify('Gelombang dihapus, tetapi gagal memuat ulang daftar','error');return}notify('Gelombang dihapus')}
async function loadRegs(){if(!selectedWave.value)return;try{const q=new URLSearchParams();q.set('wave_id',String(selectedWave.value));if(regSearch.value)q.set('search',regSearch.value);if(regStatus.value)q.set('status',regStatus.value);const r=await fetch('/api/ppdb-registrations?'+q,{headers:auth()});if(!r.ok){const j=await r.json().catch(()=>({}));throw new Error(j.message||'Gagal memuat pendaftar')}const j=await r.json();regs.value=j.data??j}catch(e){notify(e.message,'error')}}
async function updateStatus(r,status){const res=await fetch('/api/ppdb-registrations/'+r.id,{method:'PUT',headers:{'Content-Type':'application/json',...auth()},body:JSON.stringify({status})});if(!res.ok){notify('Gagal update','error');return}r.status=status;notify('Status diperbarui')}
onMounted(loadWaves)
</script>
