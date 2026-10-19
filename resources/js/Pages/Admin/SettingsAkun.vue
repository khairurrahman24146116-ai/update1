<template>
<div class="space-y-4">
<div class="flex flex-wrap gap-2 items-center">
<input v-model="search" @input="onSearch" placeholder="Cari nama/email" class="input max-w-xs" />
<select v-model="filterRole" @change="load" class="input"><option value="">Semua role</option><option value="admin">Admin</option><option value="guru">Guru</option><option value="bendahara_lpi">Bendahara LPI</option><option value="bendahara_sma">Bendahara SMA</option><option value="wali_murid">Wali Murid</option></select>
<button @click="openCreate" class="btn btn--primary btn--sm">+ Akun</button>
</div>
<Transition name="fade">
<div v-if="showForm" class="bg-surface-container-low border border-outline-variant rounded-xl p-4 space-y-3">
<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
<input v-model="form.name" placeholder="Nama *" class="input" />
<input v-model="form.email" placeholder="Email *" class="input" />
<input v-model="form.password" :placeholder="form.id ? 'Password (kosongkan jika tidak ganti)' : 'Password *'" type="password" class="input" />
<select v-model="form.role" class="input"><option value="admin">Admin</option><option value="guru">Guru</option><option value="bendahara_lpi">Bendahara LPI</option><option value="bendahara_sma">Bendahara SMA</option><option value="wali_murid">Wali Murid</option></select>
<select v-model="form.unit" class="input" :disabled="isBendaharaRole" :title="isBendaharaRole ? 'Unit mengikuti role bendahara' : 'Unit akses (mis. untuk izin keuangan)'"><option value="">Tanpa unit</option><option value="lpi">Unit LPI</option><option value="sma">Unit SMA</option></select>
<label class="flex items-center gap-2 text-xs font-bold"><input type="checkbox" v-model="form.is_active" :disabled="isProtectedEdit" /> Aktif <span v-if="isProtectedEdit" class="opacity-40">(terkunci)</span></label>
</div>
<p v-if="formError" class="text-xs text-error bg-red-50 border border-red-600 rounded-full px-2 py-1">{{ formError }}</p>
<div class="flex gap-2"><button @click="submit" class="btn btn--primary btn--sm">Simpan</button><button @click="showForm=false" class="btn btn--ghost btn--sm">Batal</button></div>
</div>
</Transition>
<div v-if="loading" class="text-center py-6 text-sm text-on-surface-variant">Memuat...</div>
<div v-else class="space-y-1">
<div v-for="u in list" :key="u.id" class="p-2 bg-white border border-outline-variant rounded-xl flex items-center justify-between gap-2 transition-transform duration-200 ease-in-out hover:-translate-y-1">
<div class="min-w-0"><div class="font-bold text-xs truncate">{{ u.name }} <span class="text-on-surface-variant">({{ u.email }})</span> <span v-if="u.is_protected" class="ml-1 px-1.5 py-0.5 bg-primary text-on-primary rounded-full text-[10px]">Utama</span></div><div class="text-xs text-on-surface-variant">{{ labelRole(u.role) }} • {{ u.is_active?'aktif':'nonaktif' }}</div></div>
<div class="flex gap-1 shrink-0"><button v-if="canViewPermissions" @click="openPermissions(u)" class="btn btn--ghost btn--sm" title="Atur hak akses"><span class="material-symbols-outlined !w-4 !h-4">key</span> Akses</button><button @click="openEdit(u)" class="btn btn--ghost btn--sm">Edit</button><button v-if="!u.is_protected" @click="remove(u)" class="btn btn--destructive btn--sm">Hapus</button><span v-else class="px-3 py-1 text-xs opacity-40" title="Akun utama tidak dapat dihapus"><span class="material-symbols-outlined !w-4 !h-4">lock</span></span></div>
</div>
<div v-if="!list.length" class="text-center py-6 text-sm text-on-surface-variant">Belum ada akun.</div>
</div>
<p v-if="msg" class="text-xs font-bold px-2 py-1 rounded-full border border-outline-variant" :class="msgType==='error'?'bg-red-50 text-error':'bg-green-50 text-green-700'">{{ msg }}</p>
<PermissionsPanel v-if="panelUser" :acc="panelUser" @close="panelUser=null" @saved="onPermissionsSaved" />
</div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '../../composables/useAuth.js'
import PermissionsPanel from '../../Components/PermissionsPanel.vue'
const { can } = useAuth()
const token=()=>localStorage.getItem('madani_token')
const auth=()=>({Authorization:`Bearer ${token()}`})
const list=ref([]), loading=ref(false), search=ref(''), filterRole=ref(''), showForm=ref(false), formError=ref(''), msg=ref(''), msgType=ref('success'), panelUser=ref(null)
const form=ref({name:'',email:'',password:'',role:'guru',unit:'',is_active:true})
const isProtectedEdit=computed(()=> !!form.value.id && list.value.find(x=>x.id===form.value.id)?.is_protected)
const isBendaharaRole=computed(()=> ['bendahara_lpi','bendahara_sma'].includes(form.value.role))
const canViewPermissions=computed(()=> can('permissions.view') || can('permissions.manage'))
let timer=null
function labelRole(r){ return ({admin:'Admin',guru:'Guru',bendahara_lpi:'Bendahara LPI',bendahara_sma:'Bendahara SMA',wali_murid:'Wali Murid'})[r] || r }
function notify(m,t='success'){msg.value=m;msgType.value=t;setTimeout(()=>msg.value='',3000)}
function onSearch(){clearTimeout(timer);timer=setTimeout(load,350)}
function openCreate(){form.value={name:'',email:'',password:'',role:'guru',unit:'',is_active:true};formError.value='';showForm.value=true}
function openEdit(u){form.value={id:u.id,name:u.name,email:u.email,password:'',role:u.role,unit:u.unit||'',is_active:!!u.is_active}; if(u.is_protected) form.value.is_active=true; showForm.value=true}
function openPermissions(u){panelUser.value=u}
function onPermissionsSaved(){notify('Hak akses diperbarui')}
async function remove(u){if(!confirm('Hapus akun '+u.name+'?'))return;const r=await fetch('/api/users/'+u.id,{method:'DELETE',headers:auth()});if(!r.ok){const j=await r.json().catch(()=>({}));notify(j.message||'Gagal hapus','error');return}await load();notify('Akun dihapus')}
async function load(){loading.value=true;try{const q=new URLSearchParams();if(search.value)q.set('search',search.value);if(filterRole.value)q.set('role',filterRole.value);const r=await fetch('/api/users?'+q,{headers:auth()});if(!r.ok)throw new Error('Gagal');list.value=await r.json()}catch(e){notify(e.message,'error')}finally{loading.value=false}}
async function submit(){formError.value='';if(!form.value.name||!form.value.email){formError.value='Nama dan email wajib';return}const isEdit=!!form.value.id;if(!isEdit&&!form.value.password){formError.value='Password wajib';return}const payload={name:form.value.name,email:form.value.email,role:form.value.role,unit:form.value.unit||null,is_active:!!form.value.is_active};if(form.value.password)payload.password=form.value.password;const url=isEdit?'/api/users/'+form.value.id:'/api/users';const method=isEdit?'PUT':'POST';const r=await fetch(url,{method,headers:{'Content-Type':'application/json',...auth()},body:JSON.stringify(payload)});if(!r.ok){const j=await r.json();formError.value=j.message||Object.values(j.errors||{}).flat().join(', ');return}showForm.value=false;await load();notify(isEdit?'Akun diperbarui':'Akun dibuat')}
onMounted(load)
</script>
