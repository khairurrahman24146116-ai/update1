<template>
  <LpiLayout>
    <div class="w-full max-w-7xl mx-auto px-gutter pt-space-lg pb-space-sm">
      <nav class="flex flex-wrap items-center gap-2 font-label-md text-label-md text-outline" aria-label="Breadcrumb">
        <router-link to="/" class="hover:text-primary transition-colors">Beranda</router-link>
        <span aria-hidden="true">/</span>
        <span class="text-on-surface font-semibold">Struktur Organisasi LPI</span>
      </nav>
    </div>

    <StructureBoard
      unit="lpi"
      title="Struktur Organisasi LPI Dayah Madani Al-Aziziyah"
      badge="LPI Dayah Madani Al-Aziziyah"
      description="Susunan kepengurusan Lembaga Pendidikan Islam Dayah Madani Al-Aziziyah berdasarkan data resmi lembaga."
    />
  </LpiLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import LpiLayout from '../../Components/LpiLayout.vue'
import StructureBoard from '../../Components/StructureBoard.vue'
import { useSeo } from '../../composables/useSeo.js'

onMounted(() => {
  useSeo({
    title: 'Struktur Organisasi LPI Dayah Madani Al-Aziziyah',
    description: 'Struktur organisasi LPI Dayah Madani Al-Aziziyah.',
    url: window.location.origin + '/struktur',
  })
})
</script>
