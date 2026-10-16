<template>
<div class="space-y-4">
<div class="flex flex-wrap gap-2">
<input v-model="catName" placeholder="Nama kategori baru" class="input" />
<button @click="createCat" class="btn btn--primary btn--sm">+ Kategori</button>
</div>
<div class="flex flex-wrap gap-2">
<span v-for="c in cats" :key="c.id" class="inline-flex items-center gap-1 px-2 py-1 bg-surface-container-low border border-outline-variant rounded-full text-xs font-bold">{{ c.name }}
<button @click="removeCat(c)" class="text-error">×</button></span>
</div>
<div class="flex flex-wrap gap-2 items-center">
<input v-model="search" @input="onSearch" placeholder="Cari berita" class="input max-w-xs" />
<select v-model="filterStatus" @change="goPage(1)" class="input"><option value="">Semua status</option><option value="draft">draft</option><option value="publish">publish</option></select>
<button @click="openCreate" class="btn btn--primary btn--sm">+ Berita</button>
</div>
<Transition name="fade">
<div v-if="showForm" class="bg-surface-container-low border border-outline-variant rounded-xl p-4 space-y-3">
<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
<input v-model="form.title" placeholder="Judul *" class="input" />
<select v-model="form.category_id" class="input"><option value="">Kategori</option><option v-for="c in cats" :key="c.id" :value="c.id">{{ c.name }}</option></select>
<input v-model="form.slug" placeholder="Slug (auto jika kosong)" class="input md:col-span-2" />
<textarea v-model="form.excerpt" placeholder="Ringkasan" rows="2" class="md:col-span-2 input"></textarea>
<textarea v-model="form.body" placeholder="Isi berita" rows="4" class="md:col-span-2 input"></textarea>
<select v-model="form.status" class="input"><option value="draft">draft</option><option value="publish">publish</option></select>
<input type="file" accept="image/*" @change="onFile" class="text-xs" />
</div>
<p v-if="formError" class="text-xs text-error bg-red-50 border border-red-600 rounded-full px-2 py-1">{{ formError }}</p>
<div class="flex gap-2"><button @click="submit" class="btn btn--primary btn--sm">Simpan</button><button @click="showForm=false" class="btn btn--ghost btn--sm">Batal</button></div>
</div>
</Transition>
<div v-if="loading" class="p-6 text-center text-sm text-on-surface-variant">Memuat...</div>
<div v-else class="space-y-2">
<div v-for="n in list" :key="n.id" class="p-3 bg-white border border-outline-variant rounded-xl flex items-center justify-between gap-2 transition-transform duration-200 ease-in-out hover:-translate-y-1">
<div class="min-w-0"><div class="font-bold text-sm truncate">{{ n.title }}</div><div class="text-xs text-on-surface-variant">{{ n.category?.name || '—' }} • {{ n.status }} • {{ n.slug }}</div></div>
<div class="flex gap-1 shrink-0"><a :href="'/sma/berita/'+n.id" target="_blank" class="btn btn--ghost btn--sm">Preview</a><button @click="openEdit(n)" class="btn btn--ghost btn--sm">Edit</button><button @click="remove(n)" class="btn btn--destructive btn--sm">Hapus</button></div>
</div>
<div v-if="!list.length" class="text-center py-6 text-sm text-on-surface-variant">Belum ada berita.</div>
<div v-if="meta" class="flex items-center justify-between pt-2 border-t text-xs"><span class="text-on-surface-variant">Total {{ meta.total }} — Hal {{ meta.current_page }}/{{ meta.last_page }}</span><div class="flex gap-2"><button @click="goPage(meta.current_page-1)" :disabled="meta.current_page<=1" class="btn btn--ghost btn--sm disabled:opacity-40">‹ Prev</button><button @click="goPage(meta.current_page+1)" :disabled="meta.current_page>=meta.last_page" class="btn btn--ghost btn--sm disabled:opacity-40">Next ›</button></div></div>
</div>
<p v-if="msg" class="text-xs font-bold px-2 py-1 rounded-full border border-outline-variant" :class="msgType==='error'?'bg-red-50 text-error':'bg-green-50 text-green-700'">{{ msg }}</p>
</div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
const token=()=>localStorage.getItem('madani_token')
const auth=()=>({Authorization:`Bearer ${token()}`})
const cats=ref([]), catName=ref('')
const list=ref([]), meta=ref(null), page=ref(1), search=ref(''), filterStatus=ref(''), loading=ref(false)
const showForm=ref(false), formError=ref(''), msg=ref(''), msgType=ref('success'), file=ref(null)
const form=ref({title:'',slug:'',excerpt:'',body:'',category_id:'',status:'draft'})
let timer=null
function notify(m,t='success'){msg.value=m;msgType.value=t;setTimeout(()=>msg.value='',3000)}
function onSearch(){clearTimeout(timer);timer=setTimeout(()=>goPage(1),350)}
function goPage(p){page.value=Math.max(1,p);load()}
async function loadCats(){try{const r=await fetch('/api/news-categories',{headers:auth()});if(r.ok)cats.value=await r.json()}catch{}}
async function createCat(){if(!catName.value)return;const r=await fetch('/api/news-categories',{method:'POST',headers:{'Content-Type':'application/json',...auth()},body:JSON.stringify({name:catName.value})});if(!r.ok){const j=await r.json();notify(j.message||'Gagal','error');return}catName.value='';await loadCats();notify('Kategori dibuat')}
async function removeCat(c){if(!confirm('Hapus kategori '+c.name+'?'))return;const r=await fetch('/api/news-categories/'+c.id,{method:'DELETE',headers:auth()});if(!r.ok){const j=await r.json().catch(()=>({}));notify(j.message||'Gagal','error');return}await loadCats()}
async function load(){loading.value=true;try{const q=new URLSearchParams();if(search.value)q.set('search',search.value);if(filterStatus.value)q.set('status',filterStatus.value);q.set('page',String(page.value));const r=await fetch('/api/news?'+q,{headers:auth()});if(!r.ok)throw new Error('Gagal');const j=await r.json();list.value=j.data??j;meta.value=j.meta??null}catch(e){notify(e.message,'error')}finally{loading.value=false}}
function openCreate(){form.value={title:'',slug:'',excerpt:'',body:'',category_id:'',status:'draft'};file.value=null;formError.value='';showForm.value=true}
function openEdit(n){form.value={id:n.id,title:n.title,slug:n.slug,excerpt:n.excerpt||'',body:n.body||'',category_id:n.category_id||'',status:n.status};file.value=null;showForm.value=true}
function onFile(e){file.value=e.target.files[0]||null}
async function submit(){formError.value='';if(!form.value.title){formError.value='Judul wajib';return}const fd=new FormData();Object.entries(form.value).forEach(([k,v])=>{if(v!==''&&v!==null&&k!=='id')fd.append(k,v)});if(file.value)fd.append('image',file.value);const isEdit=!!form.value.id;const url=isEdit?'/api/news/'+form.value.id:'/api/news';const method=isEdit?'POST': 'POST';let r;if(isEdit){fd.append('_method','PUT');r=await fetch(url,{method:'POST',headers:auth(),body:fd})}else{r=await fetch(url,{method:'POST',headers:auth(),body:fd})}if(!r.ok){const j=await r.json().catch(()=>({}));formError.value=j.message||Object.values(j.errors||{}).flat().join(', ');return}showForm.value=false;await load();notify(isEdit?'Berita diperbarui':'Berita dibuat')}
async function remove(n){if(!confirm('Hapus berita '+n.title+'?'))return;const r=await fetch('/api/news/'+n.id,{method:'DELETE',headers:auth()});if(!r.ok){notify('Gagal hapus','error');return}await load();notify('Berita dihapus')}
onMounted(()=>{loadCats();load()})
</script>
