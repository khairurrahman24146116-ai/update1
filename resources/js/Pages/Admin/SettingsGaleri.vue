<template>
<div class="space-y-4 min-w-0 max-w-full">
<div class="flex flex-wrap gap-2">
<button @click="openCreate" class="btn btn--primary btn--sm rounded">+ Album</button>
</div>
<div v-if="showForm" class="bg-surface-container-low border border-outline-variant rounded p-3 sm:p-4 space-y-3 min-w-0">
<div class="grid grid-cols-1 md:grid-cols-2 gap-3 min-w-0">
<input v-model="form.title" placeholder="Judul album *" class="input" />
<input v-model="form.slug" placeholder="Slug (auto)" class="input" />
<textarea v-model="form.description" placeholder="Deskripsi" rows="2" class="md:col-span-2 input h-auto py-2"></textarea>
<label class="flex items-center gap-2 text-xs font-bold"><input type="checkbox" v-model="form.is_public" /> Public</label>
<input type="file" accept="image/*" @change="onCover" class="text-xs w-full min-w-0" />
</div>
<p v-if="formError" class="text-xs text-error bg-red-50 border border-red-600 rounded-full px-2 py-1 break-words">{{ formError }}</p>
<div class="flex flex-wrap gap-2"><button @click="submit" class="btn btn--primary btn--sm">Simpan</button><button @click="showForm=false" class="btn btn--ghost btn--sm">Batal</button></div>
</div>
<div v-if="loading" class="text-center py-6 text-sm text-on-surface-variant">Memuat...</div>
<div v-else class="space-y-3 min-w-0">
<div v-for="a in list" :key="a.id" class="p-3 sm:p-4 bg-white border border-outline-variant rounded space-y-3 min-w-0 max-w-full">
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 min-w-0">
<div class="min-w-0">
<div class="font-bold text-sm truncate">{{ a.title }}</div>
<div class="text-xs text-on-surface-variant">{{ a.is_public?'public':'private' }} • {{ a.items_count }} foto</div>
</div>
<div class="flex flex-wrap gap-2 shrink-0">
<a :href="'/sma/galeri/'+a.id" target="_blank" class="btn btn--ghost btn--sm">Preview</a>
<button @click="openEdit(a)" class="btn btn--ghost btn--sm">Edit</button>
<button @click="remove(a)" class="btn btn--destructive btn--sm">Hapus</button>
</div>
</div>
<div class="flex flex-col md:flex-row md:flex-wrap md:items-center gap-2 min-w-0">
<input type="file" accept="image/*" @change="e=>itemFiles[a.id]=e.target.files[0]" class="text-xs w-full min-w-0 md:w-auto md:flex-1" />
<input v-model="itemCaptions[a.id]" placeholder="Caption" class="input h-8 px-2 text-xs w-full min-w-0 md:w-auto md:max-w-xs" />
<button @click="uploadItem(a)" class="btn btn--primary btn--sm w-full md:w-auto">Upload</button>
</div>
<div v-if="a.items?.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 min-w-0">
<div v-for="it in a.items" :key="it.id" class="border border-outline-variant rounded-sm overflow-hidden min-w-0 max-w-full">
<img :src="'/storage/'+it.image_path" class="w-full h-20 sm:h-24 object-cover" />
<div class="p-1 flex items-center gap-1 min-w-0"><input v-model="it.caption" @change="updateItem(a,it)" class="flex-1 input h-7 px-2 text-xs min-w-0" /><button @click="deleteItem(a,it)" class="text-xs text-error font-bold shrink-0 w-7 h-7">×</button></div>
</div>
</div>
</div>
<div v-if="!list.length" class="text-center py-6 text-sm text-on-surface-variant">Belum ada album.</div>
</div>
<p v-if="msg" class="text-xs font-bold px-2 py-1 rounded-full border border-outline-variant break-words" :class="msgType==='error'?'bg-red-50 text-error':'bg-green-50 text-green-700'">{{ msg }}</p>
</div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
const token=()=>localStorage.getItem('madani_token')
const auth=()=>({Authorization:`Bearer ${token()}`})
const list=ref([]), loading=ref(false), showForm=ref(false), formError=ref(''), coverFile=ref(null), msg=ref(''), msgType=ref('success')
const form=ref({title:'',slug:'',description:'',is_public:false})
const itemFiles=reactive({}), itemCaptions=reactive({})
function notify(m,t='success'){msg.value=m;msgType.value=t;setTimeout(()=>msg.value='',3000)}
function onCover(e){coverFile.value=e.target.files[0]||null}
function openCreate(){form.value={title:'',slug:'',description:'',is_public:false};coverFile.value=null;formError.value='';showForm.value=true}
function openEdit(a){form.value={id:a.id,title:a.title,slug:a.slug,description:a.description||'',is_public:!!a.is_public};coverFile.value=null;formError.value='';showForm.value=true}
async function load(){loading.value=true;try{const r=await fetch('/api/gallery-albums',{headers:auth()});if(!r.ok)throw new Error('Gagal');const j=await r.json();list.value=(j.data??j).map(x=>({...x,items:x.items||[]}));for(const a of list.value){try{const rr=await fetch('/api/gallery-albums/'+a.id,{headers:auth()});if(rr.ok){const d=await rr.json();a.items=d.items||[]}}catch{}}}catch(e){notify(e.message,'error')}finally{loading.value=false}}
async function submit(){formError.value='';if(!form.value.title){formError.value='Judul wajib';return}const fd=new FormData();Object.entries(form.value).forEach(([k,v])=>{if(v!==''&&v!==null&&k!=='id')fd.append(k,v===true?'1':v===false?'0':v)});if(coverFile.value)fd.append('cover',coverFile.value);const isEdit=!!form.value.id;const url=isEdit?'/api/gallery-albums/'+form.value.id:'/api/gallery-albums';let r;if(isEdit){fd.append('_method','PUT');r=await fetch(url,{method:'POST',headers:auth(),body:fd})}else r=await fetch(url,{method:'POST',headers:auth(),body:fd});if(!r.ok){const j=await r.json().catch(()=>({}));formError.value=j.message||Object.values(j.errors||{}).flat().join(', ');return}showForm.value=false;await load();notify(isEdit?'Album diperbarui':'Album dibuat')}
async function remove(a){if(!confirm('Hapus album '+a.title+'?'))return;const r=await fetch('/api/gallery-albums/'+a.id,{method:'DELETE',headers:auth()});if(!r.ok){notify('Gagal','error');return}await load();notify('Album dihapus')}
async function uploadItem(a){const f=itemFiles[a.id];if(!f){notify('Pilih file','error');return}const fd=new FormData();fd.append('image',f);if(itemCaptions[a.id])fd.append('caption',itemCaptions[a.id]);const r=await fetch('/api/gallery-albums/'+a.id+'/items',{method:'POST',headers:auth(),body:fd});if(!r.ok){const j=await r.json().catch(()=>({}));notify(j.message||'Gagal upload','error');return}itemFiles[a.id]=null;itemCaptions[a.id]='';await load();notify('Foto ditambahkan')}
async function deleteItem(a,it){const r=await fetch('/api/gallery-albums/'+a.id+'/items/'+it.id,{method:'DELETE',headers:auth()});if(!r.ok){notify('Gagal hapus','error');return}a.items=a.items.filter(x=>x.id!==it.id);notify('Foto dihapus')}
async function updateItem(a,it){await fetch('/api/gallery-albums/'+a.id+'/items/'+it.id,{method:'PUT',headers:{'Content-Type':'application/json',...auth()},body:JSON.stringify({caption:it.caption})})}
onMounted(load)
</script>
