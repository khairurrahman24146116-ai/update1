<template>
<div class="min-h-screen bg-surface text-on-surface">
<div class="max-w-[800px] mx-auto px-4 py-8 space-y-4">
<BackButton fallback="/sma/berita" label="Kembali" />
<div v-if="loading" class="text-center py-12 opacity-60">Memuat...</div>
<div v-else-if="!item" class="text-center py-12">Berita tidak ditemukan.</div>
<Reveal slow v-else class="bg-white border border-outline-variant rounded shadow-sm overflow-hidden landing-card">
<img v-if="item.image_path" :src="'/storage/'+item.image_path" class="w-full h-64 object-cover border-b border-outline-variant landing-img-wrap" />
<div class="p-6 space-y-3">
<div class="text-xs font-bold text-primary">{{ item.category?.name || 'Umum' }} • {{ item.published_at?.slice(0,10) }}</div>
<h1 class="text-2xl font-extrabold">{{ item.title }}</h1>
<p v-if="item.excerpt" class="text-sm text-on-surface-variant">{{ item.excerpt }}</p>
<div class="prose prose-sm max-w-none whitespace-pre-wrap">{{ item.body }}</div>
</div>
</Reveal>
</div>
</div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import BackButton from '../Components/BackButton.vue'
import Reveal from '../Components/Reveal.vue'
import { useSeo } from '../composables/useSeo.js'
const route = useRoute()
const item = ref(null), loading = ref(true)
onMounted(async()=>{
  try{ const r=await fetch('/api/news/public/'+route.params.id); if(r.ok){ item.value=await r.json(); useSeo({title:item.value.title, description:item.value.excerpt, image:item.value.image_path?location.origin+'/storage/'+item.value.image_path:null, url:location.href, brand:'SMA Madani Al-Aziziyah'}) } } finally{ loading.value=false }
})
</script>
