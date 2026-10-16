<template>
  <LpiLayout>
    <section class="w-full max-w-7xl mx-auto px-gutter pt-space-xl pb-space-lg flex flex-col gap-space-lg">
      <div>
        <span class="font-code-md text-code-md text-primary uppercase font-bold tracking-wider">Tentang Kami</span>
        <h1 class="font-display-lg text-display-lg font-bold tracking-tight">Tentang {{ brand.name }}</h1>
      </div>
      <div id="visi" class="scroll-mt-24 bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-md">
        <h2 class="font-headline-md text-headline-md font-semibold mb-2">Visi</h2>
        <p class="font-body-md text-body-md text-on-surface-variant">{{ visi }}</p>
      </div>
      <div id="misi" class="scroll-mt-24 bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-md">
        <h2 class="font-headline-md text-headline-md font-semibold mb-2">Misi</h2>
        <ol class="flex flex-col gap-2 font-body-md text-body-md text-on-surface-variant list-decimal pl-5">
          <li v-for="(m, i) in misi" :key="i">{{ m }}</li>
        </ol>
      </div>
      <div id="profil" class="scroll-mt-24 bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-md">
        <h2 class="font-headline-md text-headline-md font-semibold mb-2">Profil</h2>
        <p v-for="(p, i) in profil" :key="i" class="font-body-md text-body-md text-on-surface-variant">{{ p }}</p>
      </div>
      <div id="struktur" class="scroll-mt-24 bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-md">
        <h2 class="font-headline-md text-headline-md font-semibold mb-2">Struktur</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse font-body-sm text-body-sm">
            <thead>
              <tr class="border-b border-outline-variant font-label-sm text-label-sm uppercase tracking-wider">
                <th class="py-2 pr-4">Jabatan</th>
                <th class="py-2">Nama</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
              <tr v-for="s in struktur" :key="s.jabatan">
                <td class="py-2 pr-4 font-semibold">{{ s.jabatan }}</td>
                <td class="py-2">{{ s.nama }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </LpiLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import LpiLayout from '../../Components/LpiLayout.vue'
import { brand, visi, misi, profil, struktur } from '../../data/lpi.js'
import { useSeo } from '../../composables/useSeo.js'
onMounted(() => { useSeo({ title: 'Tentang', description: visi, url: location.href }) })
</script>
