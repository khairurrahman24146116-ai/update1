<template>
<AppShell title="Kelas">
<div class="space-y-4">
<BackButton fallback="/app/admin" />
<div class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm space-y-3">
<div class="flex flex-wrap items-center justify-between gap-3">
<p class="font-body-sm text-body-sm text-on-surface-variant">Master data kelas, jalur, jenjang, dan tahun ajaran.</p>
<button @click="openCreate" class="btn btn--primary btn--sm">+ Tambah Kelas</button>
</div>
<div class="flex gap-2">
<input v-model="search" @input="onSearch" placeholder="Cari kelas / tahun ajaran" class="input flex-1 max-w-sm" />
<select v-model="filterYear" @change="goPage(1)" class="input w-44">
<option value="">Semua Tahun</option>
<option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
</select>
</div>
<p v-if="error" class="text-error text-xs">{{ error }}</p>
</div>
<div v-if="showForm" class="bg-surface-container-low border border-outline-variant rounded-xl p-4 space-y-3">
<div class="grid grid-cols-1 md:grid-cols-3 gap-3">
<input v-model="form.name" placeholder="Nama kelas (contoh X-1)" class="input" />
<input v-model.number="form.grade" type="number" min="7" max="12" placeholder="Tingkat (7-12)" class="input" />
<select v-model="form.tahun_ajaran_id" class="input"><option value="">Tahun ajaran</option><option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option></select>
          <select v-model="form.jalur" class="input"><option v-for="option in jalurOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
          <select v-model="form.jenjang" class="input"><option v-for="option in jenjangOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
          <div class="text-xs text-on-surface-variant flex items-center">Unit mengikuti jenjang dan jalur: SMP = SMP, formal = SMA, LPI = LPI.</div>
</div>
<p v-if="formError" class="text-error text-xs">{{ formError }}</p>
<div class="flex gap-2"><button @click="submit" class="btn btn--primary btn--sm">Simpan</button><button @click="showForm=false" class="btn btn--ghost btn--sm">Batal</button></div>
</div>
<div class="bg-white p-4 rounded-xl border border-outline-variant shadow-sm overflow-x-auto">
<div v-if="loading" class="p-6 text-center text-sm text-on-surface-variant">Memuat...</div>
<table v-else class="w-full text-sm"><thead class="bg-primary text-on-primary"><tr><th class="p-2 text-left">Nama</th><th class="p-2 text-left">Jalur</th><th class="p-2 text-left">Jenjang</th><th class="p-2 text-left">Tingkat</th><th class="p-2 text-left">Tahun Ajaran</th><th class="p-2 text-center">Siswa</th><th class="p-2 text-right">Aksi</th></tr></thead><tbody><tr v-for="c in list" :key="c.id" class="border-t"><td class="p-2 font-bold">{{ c.name }}</td><td class="p-2 uppercase">{{ c.jalur || c.unit || '—' }}</td><td class="p-2">{{ c.jenjang || '—' }}</td><td class="p-2">{{ c.grade }}</td><td class="p-2">{{ c.academic_year }}</td><td class="p-2 text-center">{{ c.students_count ?? '—' }}</td><td class="p-2 text-right"><button @click="openEdit(c)" class="text-xs underline">Edit</button> <button @click="remove(c)" class="text-xs underline text-error">Hapus</button></td></tr></tbody></table>
<EmptyState v-if="!loading && !list.length" icon="meeting_room" title="Tidak ada data" description="Belum ada kelas yang terdaftar." />
<div v-if="meta" class="flex items-center justify-between mt-3 pt-3 border-t text-xs"><span class="text-on-surface-variant">Total {{ meta.total }} — Hal {{ meta.current_page }}/{{ meta.last_page }}</span><div class="flex gap-2"><button @click="goPage(meta.current_page-1)" :disabled="meta.current_page<=1" class="btn btn--ghost btn--sm disabled:opacity-40">Prev</button><button @click="goPage(meta.current_page+1)" :disabled="meta.current_page>=meta.last_page" class="btn btn--ghost btn--sm disabled:opacity-40">Next</button></div></div>
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
import { enabledUnitCodes } from '../../units.js'
const { user } = useAuth()
const search=ref(''), list=ref([]), meta=ref(null), page=ref(1), loading=ref(false), error=ref(''), showForm=ref(false), formError=ref(''), editingId=ref(null)
const years=ref([]), filterYear=ref('')
const enabledCodes=computed(()=>enabledUnitCodes(user.value))
const jalurCatalog=[
  { value:'formal', label:'Formal', codes:['sma','smp'] },
  { value:'lpi', label:'LPI', codes:['lpi'] },
]
const jenjangCatalog=[
  { value:'SMP', label:'SMP', codes:['smp'] },
  { value:'SMA', label:'SMA', codes:['sma'] },
  { value:'Tsanawiyah', label:'Tsanawiyah', codes:['lpi'] },
  { value:'Aliyah', label:'Aliyah', codes:['lpi'] },
]
const baseJalurOptions=computed(()=>jalurCatalog.filter(option=>option.codes.some(code=>enabledCodes.value.includes(code))))
const baseJenjangOptions=computed(()=>jenjangCatalog.filter(option=>option.codes.some(code=>enabledCodes.value.includes(code))))
const emptyForm=()=>({ name:'', grade:'', tahun_ajaran_id:'', jalur:baseJalurOptions.value[0]?.value||'formal', jenjang:baseJenjangOptions.value[0]?.value||'SMA' })
const form=ref(emptyForm())
const jalurOptions=computed(()=>{
  const list=baseJalurOptions.value
  if(form.value.jalur && !list.some(option=>option.value===form.value.jalur)) return [{ value:form.value.jalur, label:`${form.value.jalur} (nonaktif)` }, ...list]
  return list
})
const jenjangOptions=computed(()=>{
  const list=baseJenjangOptions.value
  if(form.value.jenjang && !list.some(option=>option.value===form.value.jenjang)) return [{ value:form.value.jenjang, label:`${form.value.jenjang} (nonaktif)` }, ...list]
  return list
})
let timer=null
function onSearch(){ clearTimeout(timer); timer=setTimeout(()=>goPage(1), 350) }
function goPage(p){ page.value=Math.max(1,p); load() }
async function loadYears(){
  try{ const r=await fetch('/api/import-siswa/options'); if(!r.ok) return; const j=await r.json(); years.value=j.years||[] }catch{}
}
async function load(){
  loading.value=true; error.value=''
  try{
    const q=new URLSearchParams(); if(search.value) q.set('search', search.value); if(filterYear.value) q.set('tahun_ajaran_id', filterYear.value); q.set('page', String(page.value))
    const r=await fetch(`/api/classrooms?${q}`); if(!r.ok) throw new Error('Gagal memuat'); const j=await r.json(); list.value=j.data??j; meta.value=j.meta??null
  }catch(e){ error.value=e.message } finally{ loading.value=false }
}
function openCreate(){ editingId.value=null; form.value=emptyForm(); formError.value=''; showForm.value=true }
function openEdit(c){ editingId.value=c.id; form.value={ name:c.name, grade:c.grade, tahun_ajaran_id:c.tahun_ajaran_id||'', jalur:c.jalur||c.unit||'formal', jenjang:c.jenjang||'SMA' }; formError.value=''; showForm.value=true }
async function submit(){
  formError.value=''
  if(!form.value.name || !form.value.grade){ formError.value='Nama dan tingkat wajib'; return }
  const year=years.value.find(y=>String(y.id)===String(form.value.tahun_ajaran_id))
  const unit = form.value.jenjang==='SMP' ? 'smp' : (form.value.jalur==='lpi' ? 'lpi' : 'sma')
  const payload={ name: form.value.name, grade: Number(form.value.grade), tahun_ajaran_id: form.value.tahun_ajaran_id ? Number(form.value.tahun_ajaran_id) : undefined, academic_year: year?.name, jalur: form.value.jalur, jenjang: form.value.jenjang, unit }
  const url=editingId.value?`/api/classrooms/${editingId.value}`:'/api/classrooms'; const method=editingId.value?'PUT':'POST'
  const r=await fetch(url,{method, headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)})
  if(!r.ok){ const j=await r.json(); formError.value=j.message||Object.values(j.errors??{}).flat().join(', '); return }
  showForm.value=false; editingId.value=null; await load()
}
async function remove(c){
  if(!confirm(`Hapus kelas ${c.name}?`)) return
  const r=await fetch(`/api/classrooms/${c.id}`,{method:'DELETE'}); if(!r.ok){ const j=await r.json().catch(()=>({})); alert(j.message||'Gagal hapus'); return } await load()
}
onMounted(async()=>{ await loadYears(); await load() })
</script>
