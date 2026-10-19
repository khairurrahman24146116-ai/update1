<template>
  <PublicLayout>
    <div class="w-full px-gutter-desktop py-space-sm bg-surface-container-low border-b border-outline-variant">
      <div class="max-w-7xl mx-auto">
        <nav class="flex flex-wrap items-center gap-2 font-label-md text-label-md text-outline" aria-label="Breadcrumb">
          <router-link to="/sma" class="hover:text-primary transition-colors">Beranda</router-link>
          <span aria-hidden="true">/</span>
          <span class="text-on-surface font-semibold">Struktur Organisasi SMP</span>
        </nav>
      </div>
    </div>

    <div class="pt-space-lg">
      <StructureBoard
        unit="smp"
        title="Struktur Organisasi SMP Madani Al-Aziziyah"
        badge="SMP Madani Al-Aziziyah"
        description="Susunan kepemimpinan dan jajaran SMP Madani Al-Aziziyah."
        empty-text="Segera hadir"
      />
    </div>
  </PublicLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import PublicLayout from '../../Components/PublicLayout.vue'
import StructureBoard from '../../Components/StructureBoard.vue'
import { useSeo } from '../../composables/useSeo.js'

onMounted(() => {
  useSeo({
    title: 'Struktur Organisasi SMP Madani Al-Aziziyah',
    description: 'Struktur organisasi SMP Madani Al-Aziziyah.',
    url: window.location.origin + '/smp/struktur',
    brand: 'SMP Madani Al-Aziziyah',
  })
})
</script>
