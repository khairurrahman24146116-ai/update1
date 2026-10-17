<template>
<AppShell title="Mata Pelajaran">
<div class="space-y-4">
<BackButton fallback="/app/admin" />
<div class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm space-y-3">
<div class="flex flex-wrap items-center justify-between gap-3">
<p class="font-body-sm text-body-sm text-on-surface-variant">Master data mapel & kode.</p>
<button @click="openCreate" class="btn btn--primary btn--sm">+ Tambah Mapel</button>
</div>
<div class="flex gap-2">
<input v-model="search" @input="onSearch" placeholder="Cari nama / kode" class="input flex-1 max-w-sm" />
</div>
<p v-if="error" class="text-error text-xs">{{ error }}</p>
</div>
<div v-if="showForm" class="bg-surface-container-low border border-outline-variant rounded-xl p-4 space-y-3">
<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
<input v-model="form.name" placeholder="Nama mapel" class="input" />
<input v-model="form.code" placeholder="Kode (unik)" class="input" />
</div>
<p v-if="formError" class="text-error text-xs">{{ formError }}</p>
<div class="flex gap-2"><button @click="submit" class="btn btn--primary btn--sm">Simpan</button><button @click="showForm=false" class="btn btn--ghost btn--sm">Batal</button></div>
</div>
<div class="bg-white p-4 rounded-xl border border-outline-variant shadow-sm overflow-x-auto">
<div v-if="loading" class="p-6 text-center text-sm text-on-surface-variant">Memuat...</div>
<table v-else class="w-full text-sm"><thead class="bg-primary text-on-primary"><tr><th class="p-2 text-left">Nama</th><th class="p-2 text-left">Kode</th><th class="p-2 text-right">Aksi</th></tr></thead><tbody><tr v-for="s in list" :key="s.id" class="border-t"><td class="p-2 font-bold">{{ s.name }}</td><td class="p-2 text-xs">{{ s.code }}</td><td class="p-2 text-right"><button @click="openEdit(s)" class="text-xs underline">Edit</button> <button @click="remove(s)" class="text-xs underline text-error">Hapus</button></td></tr></tbody></table>
<EmptyState v-if="!loading && !list.length" icon="menu_book" title="Tidak ada data" description="Belum ada mata pelajaran yang terdaftar." />
<div v-if="meta" class="flex items-center justify-between mt-3 pt-3 border-t text-xs"><span class="text-on-surface-variant">Total {{ meta.total }} — Hal {{ meta.current_page }}/{{ meta.last_page }}</span><div class="flex gap-2"><button @click="goPage(meta.current_page-1)" :disabled="meta.current_page<=1" class="btn btn--ghost btn--sm disabled:opacity-40">‹ Prev</button><button @click="goPage(meta.current_page+1)" :disabled="meta.current_page>=meta.last_page" class="btn btn--ghost btn--sm disabled:opacity-40">Next ›</button></div></div>
</div>
</div>
</AppShell>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import BackButton from '../../Components/BackButton.vue'
import EmptyState from '../../Components/EmptyState.vue'
import AppShell from '../../Components/AppShell.vue'
const search=ref(''), list=ref([]), meta=ref(null), page=ref(1), loading=ref(false), error=ref(''), showForm=ref(false), formError=ref(''), editingId=ref(null)
const form=ref({ name:'', code:'' })
let timer=null
function onSearch(){ clearTimeout(timer); timer=setTimeout(()=>goPage(1), 350) }
function goPage(p){ page.value=Math.max(1,p); load() }
async function load(){
  loading.value=true; error.value=''
  try{
    const q=new URLSearchParams(); if(search.value) q.set('search', search.value); q.set('page', String(page.value))
    const r=await fetch(`/api/subjects?${q}`); if(!r.ok) throw new Error('Gagal memuat'); const j=await r.json(); list.value=j.data??j; meta.value=j.meta??null
  }catch(e){ error.value=e.message } finally{ loading.value=false }
}
function openCreate(){ editingId.value=null; form.value={ name:'', code:'' }; formError.value=''; showForm.value=true }
function openEdit(s){ editingId.value=s.id; form.value={ name:s.name, code:s.code }; formError.value=''; showForm.value=true }
async function submit(){
  formError.value=''; if(!form.value.name || !form.value.code){ formError.value='Nama dan kode wajib'; return }
  const payload={ name: form.value.name, code: form.value.code }
  const url=editingId.value?`/api/subjects/${editingId.value}`:'/api/subjects'; const method=editingId.value?'PUT':'POST'
  const r=await fetch(url,{method, headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)})
  if(!r.ok){ const j=await r.json(); formError.value=j.message||Object.values(j.errors??{}).flat().join(', '); return }
  showForm.value=false; editingId.value=null; await load()
}
async function remove(s){
  if(!confirm(`Hapus mapel ${s.name}?`)) return
  const r=await fetch(`/api/subjects/${s.id}`,{method:'DELETE'}); if(!r.ok){ const j=await r.json().catch(()=>({})); alert(j.message||'Gagal hapus'); return } await load()
}
onMounted(load)
</script>
