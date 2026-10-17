<template>
  <LpiLayout>
    <section class="w-full max-w-7xl mx-auto px-gutter pt-space-xl pb-space-lg flex flex-col gap-space-lg">
      <div>
        <span class="font-code-md text-code-md text-primary uppercase font-bold tracking-wider">Akademik</span>
        <h1 class="font-display-lg text-display-lg font-bold tracking-tight">Kalender Akademik</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl leading-relaxed">Jadwal kegiatan akademik {{ brand.name }} sepanjang tahun.</p>
      </div>
      <div class="bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-xl flex flex-col items-center text-center gap-space-sm">
        <span class="material-symbols-outlined text-primary text-[40px]">calendar_month</span>
        <h2 class="font-headline-md text-headline-md font-semibold">Konten segera diisi</h2>
        <p class="font-body-md text-body-md text-on-surface-variant max-w-md">Kalender akademik akan ditampilkan di halaman ini.</p>
      </div>
    </section>
  </LpiLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import LpiLayout from '../../Components/LpiLayout.vue'
import { brand } from '../../data/lpi.js'
import { useSeo } from '../../composables/useSeo.js'
onMounted(() => { useSeo({ title: 'Kalender Akademik', description: `Kalender akademik ${brand.name}`, url: location.href }) })
</script>
