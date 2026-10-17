<template>
<AppShell title="Data Santri">
<div class="space-y-4">
<BackButton fallback="/app/admin" />
<div class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm space-y-3">
<div class="flex flex-wrap items-center justify-between gap-3">
<p class="font-body-sm text-body-sm text-on-surface-variant">Menampilkan santri berstatus {{ labelStatus.toLowerCase() }}.</p>
<div class="flex gap-2"><router-link to="/app/admin/import-siswa" class="btn btn--ghost btn--sm">Import Putra/Putri</router-link><button @click="showForm=!showForm" class="btn btn--primary btn--sm">+ Tambah Santri</button></div>
</div>
<div class="flex flex-wrap gap-2 items-center">
<input v-model="search" @input="onSearchInput" placeholder="Cari NIS/Nama" class="input w-56" />
<select v-model="filterKelas" @change="goPage(1)" class="input w-44"><option value="">Semua Kelas</option><option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option></select>
<select v-model="filterGender" @change="goPage(1)" class="input w-36"><option value="">L/P Semua</option><option value="L">L</option><option value="P">P</option></select>
<select v-model="filterStatus" @change="goPage(1)" class="input w-40"><option value="">Semua Status</option><option value="aktif">aktif</option><option value="nonaktif">nonaktif</option><option value="lulus">lulus</option></select>
</div>
<div v-if="showForm" class="border border-outline-variant rounded-xl p-4 space-y-3 bg-surface-container-low">
<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
<input v-model="form.nis" placeholder="NIS" class="input" />
<input v-model="form.nisn" placeholder="NISN" class="input" />
<input v-model="form.name" placeholder="Nama" class="input" />
<select v-model="form.gender" class="input"><option value="">L/P</option><option value="L">L</option><option value="P">P</option></select>
<select v-model="form.classroom_id" class="input"><option value="">Kelas</option><option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option></select>
<select v-model="form.status" class="input"><option value="aktif">aktif</option><option value="nonaktif">nonaktif</option><option value="lulus">lulus</option></select>
<select v-model="form.parent_id" class="input md:col-span-2"><option value="">— Tanpa Wali —</option><option v-for="w in waliList" :key="w.id" :value="w.id">{{ w.name }} — {{ w.email }}</option></select>
</div>
<p v-if="formError" class="text-error text-xs">{{ formError }}</p>
<div class="flex gap-2"><button @click="submit" class="btn btn--primary btn--sm">Simpan</button><button @click="cancelForm" class="btn btn--ghost btn--sm">Batal</button></div>
</div>
</div>
<div class="bg-white p-4 rounded-xl border border-outline-variant shadow-sm overflow-x-auto">
<div v-if="loading" class="p-6 text-center text-sm text-on-surface-variant">Memuat...</div>
<table v-else class="w-full text-sm"><thead class="bg-primary text-on-primary"><tr><th class="p-2 text-left">NIS</th><th class="p-2 text-left">NISN</th><th class="p-2 text-left">Nama</th><th class="p-2">L/P</th><th class="p-2 text-left">Kelas</th><th class="p-2 text-left">Wali</th><th class="p-2 text-left">Status</th><th class="p-2 text-right">Aksi</th></tr></thead><tbody><tr v-for="s in list" :key="s.id" class="border-t"><td class="p-2 text-xs">{{ s.nis }}</td><td class="p-2 text-xs">{{ s.nisn }}</td><td class="p-2">{{ s.name }}</td><td class="p-2 text-center">{{ s.gender }}</td><td class="p-2">{{ s.classroom?.name }}</td><td class="p-2 text-xs">{{ s.parent ? s.parent.name + ' — ' + s.parent.email : '—' }}</td><td class="p-2"><span class="badge">{{ s.status }}</span></td><td class="p-2 text-right"><button @click="edit(s)" class="text-xs underline">Edit</button> <button @click="toggleStatus(s)" class="text-xs underline">{{ s.status==='aktif'?'Nonaktifkan':'Aktifkan' }}</button></td></tr></tbody></table>
<EmptyState v-if="!loading && !list.length" icon="person_off" title="Tidak ada data" description="Belum ada santri yang cocok dengan filter saat ini." />
<div v-if="meta" class="flex flex-wrap items-center justify-between gap-2 mt-3 pt-3 border-t text-xs">
<span class="text-on-surface-variant">Total {{ meta.total }} — Hal {{ meta.current_page }} / {{ meta.last_page }}</span>
<div class="flex gap-2">
<button @click="goPage(meta.current_page-1)" :disabled="meta.current_page<=1" class="btn btn--ghost btn--sm disabled:opacity-40">‹ Prev</button>
<button @click="goPage(meta.current_page+1)" :disabled="meta.current_page>=meta.last_page" class="btn btn--ghost btn--sm disabled:opacity-40">Next ›</button>
</div>
</div>
</div>
</div>
</AppShell>
</template>
<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import BackButton from '../../Components/BackButton.vue'
import EmptyState from '../../Components/EmptyState.vue'
import AppShell from '../../Components/AppShell.vue'
const route=useRoute()
const statusParam=computed(()=>route.query.status||'aktif')
const labelStatus=computed(()=> statusParam.value==='nonaktif' ? 'Non-Aktif' : statusParam.value==='aktif' ? 'Aktif' : statusParam.value)
const search=ref(''), filterKelas=ref(''), filterGender=ref(''), filterStatus=ref(''), list=ref([]), classrooms=ref([]), waliList=ref([]), showForm=ref(false), formError=ref(''), editingId=ref(null), loading=ref(false), meta=ref(null), page=ref(1)
let searchTimer=null
const form=ref({ nis:'', nisn:'', name:'', gender:'', classroom_id:'', status:'aktif', parent_id:'' })
watch(statusParam, ()=>{ filterStatus.value=statusParam.value; goPage(1) })
function onSearchInput(){ clearTimeout(searchTimer); searchTimer=setTimeout(()=>goPage(1), 350) }
function goPage(p){ page.value=Math.max(1,p); load() }
async function load(){
  loading.value=true
  try{
    const q=new URLSearchParams(); const st=filterStatus.value || statusParam.value; if(st) q.set('status', st); if(search.value) q.set('search', search.value); if(filterKelas.value) q.set('classroom_id', filterKelas.value); q.set('page', String(page.value))
    const r=await fetch(`/api/students?${q}`); const j=await r.json()
    if(j.data && j.meta){ let arr=j.data; if(filterGender.value) arr=arr.filter(x=>x.gender===filterGender.value); list.value=arr; meta.value=j.meta }
    else { let arr=j.data??j; if(Array.isArray(arr) && filterGender.value) arr=arr.filter(x=>x.gender===filterGender.value); list.value=Array.isArray(arr)?arr:[]; meta.value=j.meta??null }
  } finally{ loading.value=false }
}
async function loadClassrooms(){ const r=await fetch('/api/classrooms'); const j=await r.json(); classrooms.value=j.data??j }
async function loadWali(){ try{ const r=await fetch('/api/users?role=wali_murid'); const j=await r.json(); const arr=Array.isArray(j)?j:(j.data??[]); waliList.value=arr.filter(u=>u.is_active!==false) }catch{} }
function edit(s){ editingId.value=s.id; form.value={ nis:s.nis, nisn:s.nisn??'', name:s.name, gender:s.gender??'', classroom_id:s.classroom_id, status:s.status, parent_id: s.parent_id??s.parent?.id??'' }; showForm.value=true }
function cancelForm(){ showForm.value=false; editingId.value=null; form.value={ nis:'',nisn:'',name:'',gender:'',classroom_id:'',status:'aktif', parent_id:'' } }
async function toggleStatus(s){ const ns=s.status==='aktif'?'nonaktif':'aktif'; await fetch(`/api/students/${s.id}`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({status:ns})}); await load() }
async function submit(){
  formError.value=''
  const payload={ ...form.value, classroom_id: Number(form.value.classroom_id) }
  if(!payload.nis || !payload.name || !payload.classroom_id){ formError.value='NIS, Nama, Kelas wajib'; return }
  if(payload.parent_id===''||payload.parent_id===null) payload.parent_id=null; else payload.parent_id=Number(payload.parent_id)
  const url=editingId.value?`/api/students/${editingId.value}`:'/api/students'; const method=editingId.value?'PUT':'POST'
  const r=await fetch(url,{method,headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
  if(!r.ok){ const j=await r.json(); formError.value=j.message||Object.values(j.errors??{}).flat().join(', '); return }
  showForm.value=false; editingId.value=null; form.value={ nis:'',nisn:'',name:'',gender:'',classroom_id:'',status:'aktif', parent_id:'' }; await load()
}
onMounted(()=>{ filterStatus.value=statusParam.value; loadClassrooms(); load(); loadWali() })
</script>
