<template>
  <LpiLayout>
    <section class="w-full max-w-7xl mx-auto px-gutter pt-space-xl pb-space-lg flex flex-col gap-space-md">
      <span class="font-code-md text-code-md text-primary uppercase font-bold tracking-wider">Lembaga Pendidikan Islam</span>
      <h1 class="font-display-lg text-display-lg font-bold tracking-tight">{{ brand.name }}</h1>
      <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl leading-relaxed">{{ brand.tagline }}</p>
      <div class="flex flex-wrap gap-space-sm">
        <router-link :to="smaUrl" class="inline-flex items-center justify-center gap-2 px-space-lg py-space-sm bg-primary text-on-primary font-title-md rounded-full shadow-sm hover:bg-primary-hover">
          <span>Kunjungi Situs SMA</span>
          <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </router-link>
        <router-link to="/program" class="inline-flex items-center justify-center gap-2 px-space-lg py-space-sm bg-surface-container-lowest border border-outline-variant font-title-md rounded-full shadow-sm">
          <span>Lihat Program</span>
        </router-link>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
        <div v-for="p in programs" :key="p.key" class="bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-md flex items-start gap-space-sm">
          <span class="material-symbols-outlined text-primary text-[28px]">{{ p.icon }}</span>
          <div>
            <h2 class="font-headline-sm text-headline-sm font-semibold">{{ p.name }}</h2>
            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ p.active ? p.desc : 'Segera hadir' }}</p>
          </div>
        </div>
      </div>
    </section>
  </LpiLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import LpiLayout from '../../Components/LpiLayout.vue'
import { brand, programs, smaUrl } from '../../data/lpi.js'
import { useSeo } from '../../composables/useSeo.js'
onMounted(() => { useSeo({ title: 'Beranda', description: brand.tagline, url: location.href }) })
</script>
