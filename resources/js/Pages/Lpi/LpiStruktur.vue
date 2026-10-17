<template>
  <LpiLayout>
    <section class="w-full max-w-7xl mx-auto px-gutter pt-space-xl pb-space-sm">
      <!-- Header bagan -->
      <Reveal variant="up">
        <div class="glass-card rounded-[28px] shadow-sm overflow-hidden">
          <div class="lpi-struktur-head relative text-white px-gutter py-space-md flex flex-col sm:flex-row items-center sm:items-center gap-space-sm text-center sm:text-left overflow-hidden">
            <div class="lpi-struktur-head__glow" aria-hidden="true"></div>
            <AppLogo :src="logo" :size="64" shape="lg" alt="Logo LPI Dayah Madani Al-Aziziyah" />
            <div class="relative flex flex-col">
              <h1 class="font-headline-lg text-headline-lg font-bold tracking-tight uppercase leading-tight">Struktur Organisasi</h1>
              <p class="font-title-md text-title-md text-white/90 mt-0.5">{{ brand.name }}</p>
              <p class="font-body-sm text-body-sm text-white/75 mt-1 flex items-center gap-1 justify-center sm:justify-start">
                <span class="material-symbols-outlined text-[16px]">location_on</span>
                {{ lokasi }}
              </p>
            </div>
          </div>
          <div class="px-gutter py-space-sm flex flex-wrap items-center gap-x-4 gap-y-1 font-label-sm text-label-sm text-on-surface-variant border-t border-outline-variant">
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-primary">person</span> Kartu: foto, nama, dan jabatan</span>
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-primary">account_tree</span> Garis menunjukkan hubungan atasan–bawahan</span>
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-primary">info</span> Badge “Perlu konfirmasi” menandai data yang belum pasti</span>
          </div>
        </div>
      </Reveal>
    </section>

    <section class="w-full max-w-7xl mx-auto px-gutter pb-space-lg">
      <div v-if="loading" class="glass-card rounded-[28px] p-space-xl flex flex-col items-center gap-space-sm">
        <span class="material-symbols-outlined text-primary text-[36px] animate-spin">progress_activity</span>
        <p class="font-body-md text-body-md text-on-surface-variant">Memuat struktur organisasi…</p>
      </div>

      <div v-else-if="error" class="rounded-[28px] border border-error bg-error-container shadow-sm p-space-lg flex flex-col items-center text-center gap-space-xs">
        <span class="material-symbols-outlined text-error text-[34px]">error</span>
        <p class="font-body-md text-body-md text-on-error-container">{{ error }}</p>
        <button type="button" class="btn-skeu px-5 h-10 rounded-full bg-primary text-on-primary font-title-md text-title-md" @click="load">Coba Lagi</button>
      </div>

      <div v-else-if="!members.length" class="glass-card rounded-[28px] p-space-xl flex flex-col items-center text-center gap-space-xs">
        <span class="material-symbols-outlined text-primary text-[40px]">account_tree</span>
        <h2 class="font-headline-md text-headline-md font-semibold">Struktur belum diisi</h2>
        <p class="font-body-md text-body-md text-on-surface-variant max-w-md">Data struktur organisasi belum tersedia. Silakan hubungi administrator untuk melengkapinya.</p>
      </div>

      <Reveal v-else variant="scale">
        <div class="rounded-[28px] border border-outline-variant bg-surface-container-lowest shadow-sm p-space-md md:p-space-lg">
          <OrgChart :members="members" />
        </div>
      </Reveal>

      <p class="mt-space-sm font-body-sm text-body-sm text-on-surface-variant flex items-start gap-2">
        <span class="material-symbols-outlined text-[18px] text-primary mt-0.5">verified_user</span>
        Susunan pada bagan ini mengikuti data resmi lembaga. Kartu dengan tanda “Perlu konfirmasi” masih menunggu pelengkapan nama atau jabatan dari pihak berwenang.
      </p>
    </section>
  </LpiLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import LpiLayout from '../../Components/LpiLayout.vue'
import AppLogo from '../../Components/AppLogo.vue'
import OrgChart from '../../Components/OrgChart.vue'
import Reveal from '../../Components/Reveal.vue'
import { brand } from '../../data/lpi.js'
import { useOrganization } from '../../composables/useOrganization.js'
import { useSiteSettings } from '../../composables/useSiteSettings.js'
import { useSeo } from '../../composables/useSeo.js'

const lokasi = 'Gampong Lampeuneurut Ujong Blang, Kecamatan Darul Imarah, Kabupaten Aceh Besar'
const { members, loading, error, load } = useOrganization()
const { get, asset, load: loadSettings } = useSiteSettings()
const logo = ref(null)

onMounted(async () => {
  await Promise.all([load(), loadSettings()])
  logo.value = asset(get('logo_path', null))
  useSeo({ title: 'Struktur Organisasi', description: `Struktur organisasi ${brand.name}`, url: location.href })
})
</script>

<style scoped>
.lpi-struktur-head {
  background: linear-gradient(135deg, #0d4835 0%, #326854 58%, #18503d 100%);
}
.lpi-struktur-head__glow {
  position: absolute;
  width: 260px;
  height: 260px;
  right: -60px;
  top: -120px;
  border-radius: 9999px;
  background: rgba(254, 214, 91, 0.16);
  filter: blur(70px);
  pointer-events: none;
}
</style>
