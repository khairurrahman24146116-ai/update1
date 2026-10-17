<template>
<AppShell title="Import Absensi">
<BackButton fallback="/app/guru/absensi" />
<div class="bg-white border border-outline-variant rounded-xl shadow-sm p-5 space-y-3">
<div class="grid grid-cols-1 md:grid-cols-3 gap-3">
<select v-model="scheduleId" class="input"><option value="">Pilih Jadwal</option><option v-for="s in schedules" :key="s.id" :value="s.id">{{ s.subject?.name }} — {{ s.classroom?.name }} ({{ labelDay(s.day_of_week) }})</option></select>
<input v-model="date" type="date" class="input" />
<input type="file" accept=".xlsx,.xls" @change="onFile" class="text-sm" />
</div>
<p class="text-xs text-on-surface-variant">Header: NIS / NISN / Status (hadir/izin/sakit/alfa) / Keterangan — status wajib salah satu dari hadir/izin/sakit/alfa</p>
<p v-if="error" class="text-error text-xs bg-red-50 border border-red-600 rounded-full px-2 py-1">{{ error }}</p>
<button @click="doPreview" :disabled="!file || !scheduleId || !date" class="btn btn--secondary disabled:opacity-50">Preview & Validasi</button>
</div>
<div v-if="preview" class="bg-white border border-outline-variant rounded-xl shadow-sm p-5 space-y-3">
<div class="flex gap-4 text-sm font-bold"><span>Total: {{ preview.total }}</span><span class="text-green-700">Valid: {{ preview.valid }}</span><span class="text-error">Invalid: {{ preview.invalid }}</span></div>
<div class="overflow-x-auto"><table class="w-full text-xs"><thead class="bg-primary text-on-primary"><tr><th class="p-2 text-left">Baris</th><th class="p-2 text-left">NIS</th><th class="p-2 text-left">NISN</th><th class="p-2 text-left">Status</th><th class="p-2 text-left">Ket</th><th class="p-2">Hasil</th><th class="p-2 text-left">Error</th></tr></thead><tbody><tr v-for="r in preview.rows" :key="r.row" class="border-t"><td class="p-2">{{ r.row }}</td><td class="p-2">{{ r.data.nis }}</td><td class="p-2">{{ r.data.nisn }}</td><td class="p-2">{{ r.data.status }}</td><td class="p-2">{{ r.data.notes }}</td><td class="p-2 font-bold" :class="r.status==='PASS'?'text-green-700':'text-error'">{{ r.status }}</td><td class="p-2 text-error">{{ r.errors.join('; ') }}</td></tr></tbody></table></div>
<button @click="doImport" class="btn btn--primary">Konfirmasi Import</button>
</div>
<div v-if="result" class="bg-white border border-outline-variant rounded-xl shadow-sm p-5 text-sm space-y-2">
<div class="font-extrabold">Hasil Import</div>
<div>Imported: {{ result.imported }} | Skipped: {{ result.skipped }} | Failed: {{ result.failed }}</div>
<div v-for="e in result.errors" :key="e" class="text-error">{{ e }}</div>
</div>
</AppShell>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import BackButton from '../../Components/BackButton.vue'
import AppShell from '../../Components/AppShell.vue'
const dayMap = { mon:'Senin', tue:'Selasa', wed:'Rabu', thu:'Kamis', fri:'Jumat', sat:'Sabtu', sun:'Minggu' }
function labelDay(d){ return dayMap[d] ?? d }
const schedules=ref([]), scheduleId=ref(''), date=ref(new Date().toISOString().slice(0,10)), file=ref(null), preview=ref(null), result=ref(null), error=ref('')
onMounted(async()=>{ try{ const r=await fetch('/api/schedules'); const j=await r.json(); schedules.value=j.data??j }catch{} })
function onFile(e){ file.value=e.target.files[0]; preview.value=null; result.value=null; error.value='' }
async function doPreview(){
  error.value=''; preview.value=null
  const fd=new FormData(); fd.append('file', file.value); fd.append('schedule_id', scheduleId.value); fd.append('date', date.value)
  const r=await fetch('/api/guru/attendance/import/preview',{method:'POST', body:fd})
  const j=await r.json(); if(!r.ok){ error.value=j.message||Object.values(j.errors??{}).flat().join(', '); return } preview.value=j
}
async function doImport(){
  error.value=''
  const fd=new FormData(); fd.append('file', file.value); fd.append('schedule_id', scheduleId.value); fd.append('date', date.value)
  const r=await fetch('/api/guru/attendance/import',{method:'POST', body:fd})
  const j=await r.json(); if(!r.ok){ error.value=j.message||Object.values(j.errors??{}).flat().join(', '); return } result.value=j
}
</script>
