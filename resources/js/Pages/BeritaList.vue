<template>
  <PublicLayout>
    <section class="w-full bg-surface-container-low px-gutter py-space-sm">
      <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-space-xs">
        <div class="flex items-center gap-space-xs font-label-sm text-label-sm text-on-surface-variant">
          <router-link to="/sma" class="hover:text-primary transition-colors">Beranda</router-link>
          <span class="material-symbols-outlined text-[13px] text-outline">chevron_right</span>
          <span class="text-on-surface font-semibold">Berita Sekolah</span>
        </div>
        <span class="font-code-md text-code-md text-outline">INFORMASI &amp; KABAR MADANI</span>
      </div>
    </section>

    <section class="w-full max-w-7xl mx-auto px-gutter pt-space-xl pb-space-lg">
      <Reveal slow class="flex flex-col gap-space-xs">
        <div class="inline-flex items-center gap-space-xs w-fit bg-secondary-fixed text-on-secondary-fixed px-space-md py-1 rounded-full font-label-sm text-label-sm">
          <span class="material-symbols-outlined text-[15px]">newspaper</span>
          <span>BERITA SEKOLAH</span>
        </div>
        <h1 class="font-display-lg text-display-lg text-on-surface font-bold tracking-tight">Kabar &amp; Informasi SMA Madani Al-Aziziyah</h1>
        <p class="font-body-md text-body-md text-on-surface-variant max-w-3xl leading-relaxed">Pengumuman, kegiatan, dan informasi terbaru seputar lingkungan Dayah Madani Al-Aziziyah.</p>
      </Reveal>
    </section>

    <section class="w-full max-w-7xl mx-auto px-gutter pb-space-xl">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg">
        <article v-for="(n, i) in items" :key="n.id" class="stagger-slow landing-card flex flex-col bg-surface-container-lowest rounded shadow-md overflow-hidden" :style="{ animationDelay: `${Math.min(i, 8) * 140}ms` }">
          <router-link :to="'/sma/berita/' + n.id" class="flex flex-col h-full">
            <div class="relative h-48 w-full bg-surface-container overflow-hidden landing-img-wrap">
              <img v-if="n.image_path" class="w-full h-full object-cover" :src="'/storage/' + n.image_path" :alt="n.title" loading="lazy" />
              <div v-else class="w-full h-full flex items-center justify-center text-outline">
                <span class="material-symbols-outlined !w-12 !h-12 text-[48px]">image</span>
              </div>
              <span v-if="n.category" class="absolute top-space-sm left-space-sm font-label-sm text-label-sm px-space-sm py-0.5 rounded-full shadow-sm bg-primary-fixed text-on-primary-fixed">{{ n.category.name }}</span>
            </div>
            <div class="p-space-lg flex flex-col flex-1 justify-between gap-space-md">
              <div class="flex flex-col gap-space-xs">
                <div class="font-code-md text-code-md text-on-surface-variant">{{ formatDate(n.published_at) }}</div>
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold leading-snug">{{ n.title }}</h3>
                <p v-if="n.excerpt" class="font-body-md text-body-md text-on-surface-variant line-clamp-2">{{ n.excerpt }}</p>
              </div>
              <span class="inline-flex items-center gap-1 text-primary font-title-md text-title-md">Baca Selengkapnya<span class="material-symbols-outlined text-[16px]">arrow_forward</span></span>
            </div>
          </router-link>
        </article>
      </div>

      <div v-if="loading" class="py-space-xl">
        <EmptyState icon="hourglass_empty" title="Memuat berita" description="Mohon tunggu, halaman berita sedang dimuat." />
      </div>
      <div v-else-if="!items.length" class="py-space-xl">
        <EmptyState icon="newspaper" title="Belum ada berita" description="Berita sekolah akan tampil di sini setelah admin menerbitkannya melalui dashboard." />
      </div>
    </section>
  </PublicLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import PublicLayout from '../Components/PublicLayout.vue'
import EmptyState from '../Components/EmptyState.vue'
import Reveal from '../Components/Reveal.vue'

const items = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const r = await fetch('/api/news/public')
    if (r.ok) {
      const j = await r.json()
      items.value = Array.isArray(j) ? j : j.data || []
    }
  } catch { /* abaikan */ } finally {
    loading.value = false
  }
})

function formatDate(v) {
  if (!v) return ''
  return new Date(v).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}
</script>