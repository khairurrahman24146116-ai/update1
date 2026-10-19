<template>
  <PublicLayout>
    <div class="w-full px-gutter-desktop py-space-sm bg-surface-container-low border-b border-outline-variant">
      <div class="max-w-7xl mx-auto">
        <nav class="flex flex-wrap items-center gap-2 font-label-md text-label-md text-outline" aria-label="Breadcrumb">
          <router-link to="/sma" class="hover:text-primary transition-colors">Beranda</router-link>
          <span aria-hidden="true">/</span>
          <span class="text-on-surface font-semibold">Struktur Organisasi SMA</span>
        </nav>
      </div>
    </div>

    <div class="pt-space-lg">
      <StructureBoard
        unit="sma"
        title="Struktur Organisasi SMA Madani Al-Aziziyah"
        badge="SMA Madani Al-Aziziyah"
        description="Susunan kepemimpinan dan jajaran SMA Madani Al-Aziziyah."
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
    title: 'Struktur Organisasi SMA Madani Al-Aziziyah',
    description: 'Struktur organisasi SMA Madani Al-Aziziyah.',
    url: window.location.origin + '/sma/struktur',
    brand: 'SMA Madani Al-Aziziyah',
  })
})
</script>
