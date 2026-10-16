<template>
<div class="min-h-screen bg-surface text-on-surface">
<div class="max-w-[1000px] mx-auto px-4 py-8 space-y-4">
<BackButton fallback="/sma/galeri" label="Kembali" />
<div v-if="loading" class="text-center py-12 opacity-60">Memuat...</div>
<div v-else-if="!album" class="text-center py-12">Album tidak ditemukan.</div>
<div v-else class="space-y-4">
<Reveal slow class="bg-white border border-outline-variant rounded shadow-sm p-6 landing-card">
<h1 class="text-2xl font-extrabold">{{ album.title }}</h1>
<p v-if="album.description" class="text-sm text-on-surface-variant mt-1">{{ album.description }}</p>
</Reveal>
<div class="grid grid-cols-2 md:grid-cols-3 gap-4">
<div
v-for="(it, i) in album.items"
:key="it.id"
class="stagger-slow landing-card bg-white border border-outline-variant rounded-xl overflow-hidden shadow-sm"
:style="{ animationDelay: `${Math.min(i, 8) * 140}ms` }"
>
<div class="overflow-hidden landing-img-wrap">
<img :src="'/storage/'+it.image_path" class="w-full h-40 object-cover border-b border-outline-variant" />
</div>
<p v-if="it.caption" class="p-2 text-xs rounded-b-xl bg-white">{{ it.caption }}</p>
</div>
</div>
<div v-if="!album.items?.length" class="text-center py-8 opacity-60 text-sm">Belum ada foto.</div>
</div>
</div>
</div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import BackButton from '../Components/BackButton.vue'
import Reveal from '../Components/Reveal.vue'
import { useSeo } from '../composables/useSeo.js'
const route=useRoute(); const album=ref(null), loading=ref(true)
onMounted(async()=>{ try{ const r=await fetch('/api/gallery/public/'+route.params.id); if(r.ok){ album.value=await r.json(); useSeo({title:album.value.title, description:album.value.description, image:album.value.cover_path?location.origin+'/storage/'+album.value.cover_path:null, url:location.href, brand:'SMA Madani Al-Aziziyah'}) } } finally{ loading.value=false } })
</script>
