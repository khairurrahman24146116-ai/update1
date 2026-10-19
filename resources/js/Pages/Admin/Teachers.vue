<template>
<AppShell title="Data Guru">
<div class="space-y-4">
<BackButton fallback="/app/admin" />
<div class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm space-y-3">
<div class="flex justify-between items-center"><p class="font-body-sm text-body-sm text-on-surface-variant">Kelola akun guru & status aktif.</p><button @click="showForm=!showForm" class="btn btn--primary btn--sm">+ Tambah Guru</button></div>
<div class="flex gap-2"><input v-model="search" @input="goPage(1)" placeholder="Cari nama/email" class="input w-64" /><select v-model="filterActive" @change="goPage(1)" class="input w-40"><option value="">Semua</option><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
<div v-if="showForm" class="border border-outline-variant rounded-xl p-4 space-y-3 bg-surface-container-low">
<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
<input v-model="form.name" placeholder="Nama" class="input" />
<input v-model="form.email" placeholder="Email" class="input" />
<input v-model="form.password" :placeholder="editingId?'Kosongkan jika tidak ganti':'Password'" type="password" class="input" />
<label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_active" /> Aktif</label>
</div>
<p v-if="formError" class="text-error text-xs">{{ formError }}</p>
<div class="flex gap-2"><button @click="submit" class="btn btn--primary btn--sm">Simpan</button><button @click="cancel" class="btn btn--ghost btn--sm">Batal</button></div>
</div>
</div>
<div class="bg-white p-4 rounded-xl border border-outline-variant shadow-sm overflow-x-auto">
<table class="w-full text-sm"><thead class="bg-primary text-on-primary"><tr><th class="p-2 text-left">Nama</th><th class="p-2 text-left">Email</th><th class="p-2">Aktif</th><th class="p-2 text-right">Aksi</th></tr></thead><tbody><tr v-for="u in list" :key="u.id" class="border-t"><td class="p-2">{{ u.name }}</td><td class="p-2 text-xs">{{ u.email }}</td><td class="p-2 text-center">{{ u.is_active?'Ya':'Tidak' }}</td><td class="p-2 text-right"><button @click="edit(u)" class="text-xs underline">Edit</button></td></tr></tbody></table>
<EmptyState v-if="!list.length" icon="person_off" title="Tidak ada data" description="Belum ada guru yang cocok dengan filter saat ini." />
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
import { ref, onMounted } from 'vue'
import BackButton from '../../Components/BackButton.vue'
import EmptyState from '../../Components/EmptyState.vue'
import AppShell from '../../Components/AppShell.vue'
const search=ref(''), filterActive=ref(''), list=ref([]), showForm=ref(false), formError=ref(''), editingId=ref(null), meta=ref(null), page=ref(1)
const form=ref({ name:'', email:'', password:'', is_active:true })
function goPage(p){ page.value=Math.max(1,p); load() }
async function load(){
  const q=new URLSearchParams(); q.set('role','guru'); q.set('page', String(page.value)); q.set('per_page','50'); if(search.value) q.set('search', search.value); if(filterActive.value!=='') q.set('is_active', filterActive.value);
  const r=await fetch(`/api/users?${q}`); const j=await r.json(); list.value=j.data??j; meta.value=j.current_page?{current_page:j.current_page,last_page:j.last_page,total:j.total}:null
}
function edit(u){ editingId.value=u.id; form.value={ name:u.name, email:u.email, password:'', is_active: !!u.is_active }; showForm.value=true }
function cancel(){ showForm.value=false; editingId.value=null; form.value={ name:'',email:'',password:'',is_active:true }; formError.value='' }
async function submit(){
  formError.value=''
  const payload={ name:form.value.name, email:form.value.email, role:'guru', is_active: !!form.value.is_active }
  if(form.value.password) payload.password=form.value.password
  if(!payload.name || !payload.email){ formError.value='Nama & Email wajib'; return }
  const url=editingId.value?`/api/users/${editingId.value}`:'/api/users'; const method=editingId.value?'PUT':'POST'
  const r=await fetch(url,{method,headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
  if(!r.ok){ const j=await r.json(); formError.value=j.message||Object.values(j.errors??{}).flat().join(', '); return }
  cancel(); await load()
}
onMounted(load)
</script>
