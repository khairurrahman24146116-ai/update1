<template>
<button @click="go" class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-surface-container-lowest border border-outline-variant rounded-full text-xs font-semibold text-on-surface shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-[transform,box-shadow]">
<span class="material-symbols-outlined text-[16px]">arrow_back</span> {{ label }}
</button>
</template>
<script setup>
import { useRouter } from 'vue-router'
const props = defineProps({ fallback: { type: String, default: '' }, label: { type: String, default: 'Kembali' } })
const router = useRouter()
function resolveFallback(){
  if(props.fallback) return props.fallback
  try{
    const u=JSON.parse(localStorage.getItem('madani_user')||'null')
    const map={admin_utama:'/app/admin',admin:'/app/admin',pending:'/app/pending',operator:'/app/operator/akun',kepala_sekolah:'/app/kepala/guru',guru:'/app/guru',bendahara:'/app/bendahara',bendahara_lpi:'/app/bendahara',bendahara_sma:'/app/bendahara',wali_murid:'/app/wali-murid'}
    return map[u?.role]||'/'
  }catch{ return '/' }
}
function go(){
  if(window.history.length>1) window.history.back()
  else router.push(resolveFallback())
}
</script>

