<template>
  <LpiLayout>
    <section class="w-full max-w-7xl mx-auto px-gutter pt-space-xl pb-space-lg flex flex-col gap-space-md">
      <span class="font-code-md text-code-md text-primary uppercase font-bold tracking-wider">Program Pendidikan</span>
      <h1 class="font-display-lg text-display-lg font-bold tracking-tight">Program {{ brand.short }}</h1>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
        <div v-for="p in programs" :key="p.key" class="bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-md flex flex-col gap-space-xs">
          <span class="material-symbols-outlined text-primary text-[28px]">{{ p.icon }}</span>
          <h2 class="font-headline-sm text-headline-sm font-semibold">{{ p.name }}</h2>
          <p class="font-body-sm text-body-sm text-on-surface-variant">{{ p.active ? p.desc : 'Segera hadir' }}</p>
          <router-link v-if="p.active" :to="p.href" class="inline-flex items-center gap-1 font-title-md text-primary underline underline-offset-4 w-fit">
            <span>Kunjungi situs</span>
            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
          </router-link>
          <span v-else class="inline-flex items-center gap-1 font-title-md text-on-surface-variant">
            <span class="material-symbols-outlined text-[16px]">schedule</span>
            <span>Segera hadir</span>
          </span>
        </div>
      </div>
    </section>
  </LpiLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import LpiLayout from '../../Components/LpiLayout.vue'
import { brand, programs } from '../../data/lpi.js'
import { useSeo } from '../../composables/useSeo.js'
onMounted(() => { useSeo({ title: 'Program', description: brand.tagline, url: location.href }) })
</script>
