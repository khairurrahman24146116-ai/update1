<template>
  <LpiLayout>
    <section class="w-full max-w-7xl mx-auto px-gutter pt-space-xl pb-space-lg flex flex-col gap-space-lg">
      <div>
        <span class="font-code-md text-code-md text-primary uppercase font-bold tracking-wider">Akademik</span>
        <h1 class="font-display-lg text-display-lg font-bold tracking-tight">Kurikulum</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl leading-relaxed">Kurikulum pendidikan yang diterapkan di {{ brand.name }}.</p>
      </div>
      <div class="bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-xl flex flex-col items-center text-center gap-space-sm">
        <span class="material-symbols-outlined text-primary text-[40px]">menu_book</span>
        <h2 class="font-headline-md text-headline-md font-semibold">Konten segera diisi</h2>
        <p class="font-body-md text-body-md text-on-surface-variant max-w-md">Rincian kurikulum akan ditampilkan di halaman ini.</p>
      </div>
    </section>
  </LpiLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import LpiLayout from '../../Components/LpiLayout.vue'
import { brand } from '../../data/lpi.js'
import { useSeo } from '../../composables/useSeo.js'
onMounted(() => { useSeo({ title: 'Kurikulum', description: `Kurikulum ${brand.name}`, url: location.href }) })
</script>
