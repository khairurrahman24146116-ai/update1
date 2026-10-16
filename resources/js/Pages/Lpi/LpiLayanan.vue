<template>
  <LpiLayout>
    <section class="w-full max-w-7xl mx-auto px-gutter pt-space-xl pb-space-lg flex flex-col gap-space-md">
      <span class="font-code-md text-code-md text-primary uppercase font-bold tracking-wider">Layanan</span>
      <h1 class="font-display-lg text-display-lg font-bold tracking-tight">Layanan {{ brand.short }}</h1>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
        <div v-for="l in layanan" :key="l.name" class="bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-md flex items-start gap-space-sm">
          <span class="material-symbols-outlined text-primary text-[28px]">{{ l.icon }}</span>
          <div>
            <h2 class="font-headline-sm text-headline-sm font-semibold">{{ l.name }}</h2>
            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ l.desc }}</p>
          </div>
        </div>
      </div>
    </section>
  </LpiLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import LpiLayout from '../../Components/LpiLayout.vue'
import { brand, layanan } from '../../data/lpi.js'
import { useSeo } from '../../composables/useSeo.js'
onMounted(() => { useSeo({ title: 'Layanan', description: brand.tagline, url: location.href }) })
</script>
