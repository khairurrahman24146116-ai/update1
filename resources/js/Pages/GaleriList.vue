<template>
  <PublicLayout>
    <section class="w-full bg-surface-container-low px-gutter py-space-sm">
      <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-space-xs">
        <div class="flex items-center gap-space-xs font-label-sm text-label-sm text-on-surface-variant">
          <router-link to="/sma" class="hover:text-primary transition-colors">Beranda</router-link>
          <span class="material-symbols-outlined text-[13px] text-outline">chevron_right</span>
          <span class="text-on-surface font-semibold">Galeri Kegiatan</span>
        </div>
        <span class="font-code-md text-code-md text-outline">DOKUMENTASI KEGIATAN MADANI</span>
      </div>
    </section>

    <section class="w-full max-w-7xl mx-auto px-gutter pt-space-xl pb-space-lg">
      <Reveal slow class="flex flex-col gap-space-xs">
        <div class="inline-flex items-center gap-space-xs w-fit bg-tertiary-fixed text-on-tertiary-fixed px-space-md py-1 rounded-full font-label-sm text-label-sm">
          <span class="material-symbols-outlined text-[15px]">photo_library</span>
          <span>GALERI</span>
        </div>
        <h1 class="font-display-lg text-display-lg text-on-surface font-bold tracking-tight">Album Kegiatan SMA Madani Al-Aziziyah</h1>
        <p class="font-body-md text-body-md text-on-surface-variant max-w-3xl leading-relaxed">Dokumentasi kegiatan dan keseharian santri di lingkungan Dayah Madani Al-Aziziyah.</p>
      </Reveal>
    </section>

    <section class="w-full max-w-7xl mx-auto px-gutter pb-space-xl">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg">
        <article v-for="(a, i) in albums" :key="a.id" class="stagger-slow landing-card flex flex-col bg-surface-container-lowest rounded shadow-md overflow-hidden" :style="{ animationDelay: `${Math.min(i, 8) * 140}ms` }">
          <router-link :to="'/sma/galeri/' + a.id" class="flex flex-col h-full">
            <div class="relative h-56 w-full bg-surface-container overflow-hidden landing-img-wrap">
              <img v-if="coverUrl(a)" class="w-full h-full object-cover" :src="coverUrl(a)" :alt="a.title" loading="lazy" />
              <div v-else class="w-full h-full flex items-center justify-center text-outline">
                <span class="material-symbols-outlined !w-12 !h-12 text-[48px]">photo_library</span>
              </div>
              <span class="absolute top-space-sm right-space-sm font-code-md text-code-md px-space-sm py-0.5 rounded-full shadow-sm bg-surface-container-lowest text-on-surface">{{ itemCount(a) }} foto</span>
            </div>
            <div class="p-space-lg flex flex-col flex-1 justify-between gap-space-md">
              <div class="flex flex-col gap-space-xs">
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold leading-snug">{{ a.title }}</h3>
                <p v-if="a.description" class="font-body-md text-body-md text-on-surface-variant line-clamp-2">{{ a.description }}</p>
              </div>
              <span class="inline-flex items-center gap-1 text-primary font-title-md text-title-md">Lihat Album<span class="material-symbols-outlined text-[16px]">arrow_forward</span></span>
            </div>
          </router-link>
        </article>
      </div>

      <div v-if="loading" class="py-space-xl">
        <EmptyState icon="hourglass_empty" title="Memuat galeri" description="Mohon tunggu, galeri sedang dimuat." />
      </div>
      <div v-else-if="!albums.length" class="py-space-xl">
        <EmptyState icon="photo_library" title="Belum ada album" description="Galeri kegiatan akan tampil di sini setelah admin menerbitkannya melalui dashboard." />
      </div>
    </section>
  </PublicLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import PublicLayout from '../Components/PublicLayout.vue'
import EmptyState from '../Components/EmptyState.vue'
import Reveal from '../Components/Reveal.vue'

const albums = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const r = await fetch('/api/gallery/public')
    if (r.ok) albums.value = await r.json()
  } catch { /* abaikan */ } finally {
    loading.value = false
  }
})

function coverUrl(a) {
  if (a.cover_path) return '/storage/' + a.cover_path
  const first = (a.items || [])[0]
  return first?.image_path ? '/storage/' + first.image_path : ''
}

function itemCount(a) {
  return (a.items || []).length
}
</script>