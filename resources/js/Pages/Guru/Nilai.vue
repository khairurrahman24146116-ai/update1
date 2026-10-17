<template>
  <AppShell title="Input Nilai">
    <BackButton fallback="/app/guru" />
    <div class="flex flex-wrap gap-3 items-center">
      <select v-model="classroomId" class="input w-48">
        <option value="">Pilih Kelas</option>
        <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <select v-model="subjectId" class="input w-48">
        <option value="">Pilih Mapel</option>
        <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
      </select>
      <button @click="load" class="btn btn--primary btn--sm">Tampilkan</button>
    </div>
    <div v-if="loading" class="p-8 text-center text-sm text-on-surface-variant">Memuat...</div>
    <EmptyState v-else-if="!sheet.length" icon="assignment" title="Pilih kelas & mapel" description="Pilih kelas dan mata pelajaran, lalu klik Tampilkan." />
    <div v-else class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-primary text-on-primary"><tr><th class="p-3 text-left">No</th><th class="p-3 text-left">NIS</th><th class="p-3 text-left">Siswa</th><th class="p-3 text-left">Nilai (0-100)</th></tr></thead>
        <tbody>
          <tr v-for="(r,i) in sheet" :key="r.student.id" class="border-t border-outline-variant">
            <td class="p-3">{{ i+1 }}</td><td class="p-3 text-xs">{{ r.student.nis }}</td><td class="p-3">{{ r.student.name }}</td>
            <td class="p-3"><input v-model.number="r.score.value" type="number" min="0" max="100" class="input h-9 w-24" placeholder="0-100" /></td>
          </tr>
        </tbody>
      </table>
      <div class="p-4 flex gap-3"><button @click="save" class="btn btn--primary">Simpan Nilai</button><span v-if="saving" class="px-3 py-2 text-sm text-on-surface-variant">Menyimpan...</span></div>
    </div>
  </AppShell>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import BackButton from '../../Components/BackButton.vue'
import EmptyState from '../../Components/EmptyState.vue'
import AppShell from '../../Components/AppShell.vue'
const classrooms=ref([]), subjects=ref([]), sheet=ref([]), loading=ref(false), saving=ref(false), classroomId=ref(''), subjectId=ref('')
onMounted(async()=>{
  const [cr,sr]=await Promise.all([fetch('/api/classrooms').then(r=>r.json()), fetch('/api/subjects').then(r=>r.json())])
  classrooms.value=cr.data??cr; subjects.value=sr.data??sr
})
async function load(){
  if(!classroomId.value||!subjectId.value) return alert('Pilih kelas & mapel')
  loading.value=true
  try{ const r=await fetch(`/api/scores/sheet?classroom_id=${classroomId.value}&subject_id=${subjectId.value}`); const d=await r.json(); if(!r.ok) throw new Error(d.message||'Gagal'); sheet.value=d.map(x=>({student:x.student, score:{value: x.score?.value ?? ''}})) } catch(e){ alert(e.message) } finally{ loading.value=false }
}
async function save(){
  saving.value=true
  try{ const r=await fetch('/api/scores',{method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({classroom_id:Number(classroomId.value), subject_id:Number(subjectId.value), rows: sheet.value.map(x=>({student_id:x.student.id, value: Number(x.score.value)}))})}); if(r.status===422){ const j=await r.json(); alert(Object.values(j.errors??j).flat().join('\n')); return } if(!r.ok) throw new Error('Gagal'); alert('Nilai tersimpan') } catch(e){ alert(e.message) } finally{ saving.value=false }
}
</script>
