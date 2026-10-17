<template>
  <AppShell title="Import Siswa">
    <BackButton fallback="/app/admin" label="Kembali" />
    <div class="flex gap-3">
      <button @click="gender='L'" :class="gender==='L'?'bg-primary text-on-primary':'bg-white'" class="btn">Siswa Putra (L)</button>
      <button @click="gender='P'" :class="gender==='P'?'bg-primary text-on-primary':'bg-white'" class="btn">Siswa Putri (P)</button>
      <button @click="downloadTemplate" class="btn btn--secondary">
        <span class="material-symbols-outlined !w-4 !h-4">download</span>
        Template {{ gender==='L'?'Putra':'Putri' }}
      </button>
    </div>
    <div class="text-xs bg-white border border-outline-variant rounded-xl p-3 transition-transform duration-200 ease-in-out hover:-translate-y-1">Kelas tersedia: <span class="font-bold">{{ classrooms.join(', ') || 'memuat...' }}</span> — header diterima: NIS / NISN / Nama / Kelas (urutan bebas, case/space tolerant)</div>
    <div class="bg-white border border-outline-variant rounded-xl shadow-sm p-5 space-y-3 transition-transform duration-200 ease-in-out hover:-translate-y-1">
      <input type="file" accept=".xlsx,.xls" @change="onFile" class="text-sm" />
      <button @click="doPreview" :disabled="!file" class="btn btn--primary disabled:opacity-50">Preview & Validasi</button>
    </div>
    <Transition name="fade">
    <div v-if="preview" class="bg-white border border-outline-variant rounded-xl shadow-sm p-5 space-y-3">
      <div class="flex gap-4 text-sm font-bold"><span>Total: {{ preview.total }}</span><span class="text-green-700">Valid: {{ preview.valid }}</span><span class="text-error">Invalid: {{ preview.invalid }}</span></div>
      <div class="overflow-x-auto"><table class="w-full text-xs"><thead class="bg-primary text-on-primary"><tr><th class="p-2 text-left">Baris</th><th class="p-2 text-left">NIS</th><th class="p-2 text-left">NISN</th><th class="p-2 text-left">Nama</th><th class="p-2 text-left">Kelas</th><th class="p-2 text-left">Status</th><th class="p-2 text-left">Error</th></tr></thead><tbody><tr v-for="r in preview.rows" :key="r.row" class="border-t border-outline-variant"><td class="p-2">{{ r.row }}</td><td class="p-2">{{ r.data.nis }}</td><td class="p-2">{{ r.data.nisn }}</td><td class="p-2">{{ r.data.nama }}</td><td class="p-2">{{ r.data.kelas }}</td><td class="p-2 font-bold" :class="r.status==='PASS'?'text-green-700':'text-error'">{{ r.status }}</td><td class="p-2 text-error">{{ r.errors.join('; ') }}</td></tr></tbody></table></div>
      <button @click="doImport" class="btn btn--primary">Konfirmasi Import (hanya valid)</button>
    </div>
    </Transition>
    <Transition name="fade">
    <div v-if="result" class="bg-white border border-outline-variant rounded-xl shadow-sm p-5 text-sm space-y-2">
      <div class="font-extrabold">Hasil Import</div>
      <div>Imported: {{ result.imported }} | Skipped: {{ result.skipped }} | Failed: {{ result.failed }}</div>
      <div v-for="e in result.errors" :key="e" class="text-error">{{ e }}</div>
    </div>
    </Transition>
  </AppShell>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import BackButton from '../../Components/BackButton.vue'
import AppShell from '../../Components/AppShell.vue'
const gender = ref('L')
const file = ref(null)
const preview = ref(null)
const result = ref(null)
const classrooms = ref([])
onMounted(async()=>{
  try{ const r=await fetch('/api/classrooms'); const j=await r.json(); const arr=j.data??j; classrooms.value=arr.map(c=>c.name) } catch{}
})
function onFile(e){ file.value = e.target.files[0]; preview.value = null; result.value = null }
async function downloadTemplate(){
  const r = await fetch(`/api/students/template?gender=${gender.value}`)
  const blob = await r.blob()
  const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = `template_siswa_${gender.value==='L'?'putra':'putri'}.xlsx`; a.click()
}
async function doPreview(){
  const fd = new FormData(); fd.append('file', file.value); fd.append('gender', gender.value)
  const r = await fetch('/api/students/import/preview', { method:'POST', body: fd })
  preview.value = await r.json()
}
async function doImport(){
  const fd = new FormData(); fd.append('file', file.value); fd.append('gender', gender.value)
  const r = await fetch('/api/students/import', { method:'POST', body: fd })
  result.value = await r.json()
}
</script>
