<template>
<AppShell title="Dashboard Wali Murid">
    <div v-if="loading" class="p-8 text-center text-sm text-on-surface-variant">Memuat data anak...</div>
    <div v-else-if="error" class="p-6 bg-error-container border border-outline-variant rounded-xl shadow-sm text-sm">{{ error }}</div>
    <EmptyState v-else-if="!children.length" icon="child_care" title="Belum ada data anak" description="Belum ada data anak yang terhubung ke akun wali murid ini." />
    <div v-for="c in children" :key="c.id" class="p-6 bg-white border border-outline-variant rounded-xl shadow-sm">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-primary text-on-primary flex items-center justify-center font-black border border-outline-variant">{{ initials(c.name) }}</div>
        <div>
          <div class="font-extrabold">{{ c.name }} — {{ c.classroom?.name ?? '-' }}</div>
          <div class="text-xs text-on-surface-variant">NIS: {{ c.nis }}<span v-if="c.nisn"> • NISN: {{ c.nisn }}</span> • Status: {{ (c.status||'').toUpperCase() }}</div>
        </div>
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="p-5 bg-white border border-outline-variant rounded-xl shadow-sm">
        <h3 class="font-bold mb-2">Anak Terhubung</h3>
        <div class="text-3xl font-black">{{ children.length }}</div>
        <div class="text-xs text-on-surface-variant mt-1">Jumlah anak terhubung pada akun wali</div>
      </div>
      <router-link to="/app/wali-murid/nilai" class="p-5 bg-white border border-outline-variant rounded-xl shadow-sm hover:shadow-md transition-[transform,box-shadow] block">
        <h3 class="font-bold mb-2">Nilai Anak</h3>
        <p class="text-sm text-on-surface-variant">Lihat nilai per mapel dari database</p>
        <span class="btn btn--primary btn--sm mt-3">Lihat Nilai →</span>
      </router-link>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <router-link to="/app/wali-murid/surat" class="p-5 bg-white border border-outline-variant rounded-xl shadow-sm hover:shadow-md transition-[transform,box-shadow]">
        <div class="font-bold">Surat Masuk</div>
        <div class="text-xs text-on-surface-variant">Pengumuman & surat resmi sekolah</div>
      </router-link>
      <router-link to="/app/wali-murid/pertemuan" class="p-5 bg-white border border-outline-variant rounded-xl shadow-sm hover:shadow-md transition-[transform,box-shadow]">
        <div class="font-bold">Ajukan Pertemuan</div>
        <div class="text-xs text-on-surface-variant">Kontak & jadwalkan pertemuan dengan sekolah</div>
      </router-link>
      <router-link v-if="hasLpi" to="/app/wali-murid/akhlak" class="p-5 bg-white border border-outline-variant rounded-xl shadow-sm hover:shadow-md transition-[transform,box-shadow]">
        <div class="font-bold">Akhlak & Izin Anak</div>
        <div class="text-xs text-on-surface-variant">Catatan akhlak, izin, dan kelayakan santri</div>
      </router-link>
    </div>
</AppShell>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import EmptyState from '../../Components/EmptyState.vue'
import AppShell from '../../Components/AppShell.vue'
const children = ref([])
const loading = ref(false)
const error = ref('')
const hasLpi = ref(false)
function initials(name){
  if(!name) return '?'
  const p=name.trim().split(/\s+/).slice(0,2).map(s=>s[0]?.toUpperCase()||'').join('')
  return p || '?'
}
onMounted(async()=>{
  loading.value=true; error.value=''
  try{
    const welfareRequest = fetch('/api/welfare/meta')
      .then((response) => (response.ok ? response.json() : null))
      .catch(() => null)
    const [r, welfare] = await Promise.all([fetch('/api/students'), welfareRequest])
    if(!r.ok) throw new Error('Gagal memuat data anak')
    const j = await r.json()
    const arr=j.data ?? (Array.isArray(j)?j:[])
    children.value=arr
    hasLpi.value = Boolean(welfare?.has_lpi)
  }catch(e){ error.value=e.message||'Gagal memuat' }
  finally{ loading.value=false }
})
</script>
