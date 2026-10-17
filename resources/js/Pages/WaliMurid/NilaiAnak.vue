<template>
  <AppShell title="Nilai Anak">
    <BackButton fallback="/app/wali-murid" />
    <div v-if="loading" class="p-8 text-center text-sm text-on-surface-variant">Memuat...</div>
    <EmptyState v-else-if="!Object.keys(grouped).length" icon="assignment" title="Belum ada nilai" description="Nilai anak akan tampil di sini setelah guru memasukkan data." />
    <div v-for="(scores, sid) in grouped" :key="sid" class="bg-white border border-outline-variant rounded-xl shadow-sm p-5 space-y-3">
      <div class="font-extrabold">{{ scores[0]?.student?.name ?? ('Anak #'+sid) }} — {{ scores[0]?.classroom?.name ?? '' }}</div>
      <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-primary text-on-primary"><tr><th class="p-2 text-left">Mapel</th><th class="p-2 text-left">Semester</th><th class="p-2 text-right">Nilai</th></tr></thead><tbody><tr v-for="s in scores" :key="s.id" class="border-t border-outline-variant"><td class="p-2">{{ s.subject?.name }}</td><td class="p-2">{{ s.semester }}</td><td class="p-2 text-right font-bold">{{ s.value }}</td></tr></tbody></table></div>
    </div>
  </AppShell>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import BackButton from '../../Components/BackButton.vue'
import EmptyState from '../../Components/EmptyState.vue'
import AppShell from '../../Components/AppShell.vue'
const grouped=ref({}), loading=ref(false)
onMounted(async()=>{ loading.value=true; try{ const r=await fetch('/api/wali/scores'); if(!r.ok) throw new Error('Gagal'); grouped.value=await r.json() } catch{} finally{ loading.value=false } })
</script>
