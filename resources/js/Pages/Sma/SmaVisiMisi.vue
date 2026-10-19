<template>
  <PublicLayout>
    <div class="w-full px-gutter-desktop py-space-sm bg-surface-container-low border-b border-outline-variant">
      <div class="max-w-7xl mx-auto">
        <nav class="flex flex-wrap items-center gap-2 font-label-md text-label-md text-outline" aria-label="Breadcrumb">
          <router-link to="/sma" class="hover:text-primary transition-colors">Beranda</router-link>
          <span aria-hidden="true">/</span>
          <router-link to="/sma/profil" class="hover:text-primary transition-colors">Profil</router-link>
          <span aria-hidden="true">/</span>
          <span class="text-on-surface font-semibold">Visi &amp; Misi</span>
        </nav>
      </div>
    </div>

    <SchoolProfile :only="['visi']" />
  </PublicLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import PublicLayout from '../../Components/PublicLayout.vue'
import SchoolProfile from '../../Components/SchoolProfile.vue'
import { useSeo } from '../../composables/useSeo.js'

onMounted(() => {
  useSeo({
    title: 'Visi & Misi',
    description: 'Visi dan misi SMA Madani Al-Aziziyah.',
    url: window.location.origin + '/sma/visi-misi',
    brand: 'SMA Madani Al-Aziziyah',
  })
})
</script>
