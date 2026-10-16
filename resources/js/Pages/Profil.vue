<template>
  <PublicLayout>
    <!-- Top Utility Bar / Breadcrumb -->
    <div class="bg-surface-container-low px-gutter py-space-sm">
      <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-space-sm">
        <div class="flex items-center gap-space-xs font-label-md text-label-md text-outline">
          <router-link to="/sma" class="hover:text-primary transition-colors">Beranda</router-link>
          <span>/</span>
          <span class="text-on-surface font-semibold">Profil &amp; Kurikulum Terpadu</span>
        </div>
        <div class="flex items-center gap-space-md font-code-md text-code-md">
          <span class="inline-flex items-center gap-1 bg-surface-container-lowest px-2 py-0.5 shadow-sm text-on-surface">
            <span class="material-symbols-outlined text-primary text-[14px]">verified</span>
            Kurikulum Terpadu 2026/2027
          </span>
        </div>
      </div>
    </div>

    <!-- Editorial Hero Section -->
    <section class="max-w-7xl mx-auto w-full px-gutter pt-space-lg pb-space-xl">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-stretch">
        <div class="lg:col-span-8 bg-surface-container-lowest p-space-lg sm:p-space-xl flex flex-col justify-between shadow-md relative overflow-hidden">
          <Reveal slow class="space-y-space-md relative z-10">
            <div class="inline-flex items-center gap-2 bg-secondary-fixed text-on-secondary-fixed px-3 py-1 text-label-sm font-label-sm">
              <span class="material-symbols-outlined text-[16px]">menu_book</span>
              ROADMAP AKADEMIK &amp; PESANTREN
            </div>
            <h1 class="font-display-lg text-display-lg text-on-surface font-semibold tracking-tight">Mencetak Generasi Qur'ani, Saintis Global, dan Berakhlak Karimah.</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant max-w-3xl leading-relaxed">SMA Madani Al Aziziyah menyintesiskan tradisi keilmuan Islam (turats) dengan kurikulum sains modern. Model pendidikan terpadu 24 jam ini membekali santri dengan adab, kedalaman nalar logis, dan kecakapan kepemimpinan.</p>
          </Reveal>
          <Reveal slow :delay="320" class="pt-space-lg mt-space-lg grid grid-cols-2 sm:grid-cols-4 gap-space-md bg-surface-container-low p-space-md">
            <div v-for="s in heroStats" :key="s.label">
              <div class="font-headline-lg text-headline-lg font-bold" :class="s.color">{{ s.value }}</div>
              <div class="font-label-sm text-label-sm text-outline">{{ s.label }}</div>
            </div>
          </Reveal>
        </div>
        <div class="lg:col-span-4 flex flex-col gap-space-md">
          <Reveal slow :delay="200" class="h-64 sm:h-72 w-full overflow-hidden relative shadow-md bg-surface-container block">
            <img class="w-full h-full object-cover landing-ken" src="/images/landing/hero-campus.jpeg" alt="Kawasan kampus Dayah Madani Al-Aziziyah" />
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-inverse-surface/90 to-transparent p-space-md text-surface">
              <span class="font-label-sm text-label-sm tracking-wider uppercase opacity-80">Kawasan Kampus</span>
              <p class="font-headline-sm text-headline-sm leading-tight text-surface">Dayah Madani Al-Aziziyah</p>
            </div>
          </Reveal>
          <Reveal slow :delay="360">
            <div class="bg-primary text-on-primary p-space-md shadow-md flex items-center justify-between landing-card">
              <div class="space-y-1">
                <span class="font-label-sm text-label-sm opacity-80">PROFIL LEMBAGA</span>
                <p class="font-title-md text-title-md font-semibold">Berdiri 2011 · Cabang Dayah MUDI 2015</p>
              </div>
              <span class="material-symbols-outlined text-[32px] landing-float">shield_with_house</span>
            </div>
          </Reveal>
        </div>
      </div>
    </section>

    <!-- Section 1: Profile Sekolah -->
    <SchoolProfile />

    <!-- Section 2: Legalitas & Pimpinan -->
    <section class="max-w-7xl mx-auto w-full px-gutter py-space-lg">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg">
        <div class="lg:col-span-5 bg-surface-container-lowest p-space-lg shadow-md flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between pb-space-sm">
              <span class="font-code-md text-code-md text-primary font-semibold">BAGIAN 02 • INFORMASI ADMINISTRATIF</span>
              <span class="bg-primary text-on-primary text-label-sm font-label-sm px-2 py-0.5">RESMI</span>
            </div>
            <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold mt-1">Status Akreditasi &amp; Identitas Satuan Pendidikan</h2>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-2">SMA Madani Al-Aziziyah merupakan satuan pendidikan formal di lingkungan Yayasan Dayah Madani Al-Aziziyah.</p>
            <div class="mt-space-md space-y-space-xs">
              <div v-for="row in legal" :key="row.label" class="flex items-center justify-between p-space-xs bg-surface-container-low font-code-md text-code-md">
                <span class="text-outline">{{ row.label }}</span>
                <span class="font-semibold text-on-surface" :class="row.highlight ? 'font-bold text-primary' : ''">{{ row.value }}</span>
              </div>
            </div>
          </div>
          <div class="mt-space-md p-space-sm bg-tertiary-fixed text-on-tertiary-fixed font-body-sm text-body-sm flex items-center gap-space-sm">
            <span class="material-symbols-outlined text-tertiary">fact_check</span>
            <span>Penyelenggaraan pendidikan mengacu pada peraturan yang berlaku di lingkungan pemerintah Indonesia.</span>
          </div>
        </div>
        <div class="lg:col-span-7 bg-surface-container-lowest p-space-lg shadow-md flex flex-col justify-between">
          <div>
            <span class="font-code-md text-code-md text-secondary font-semibold">PENGASUH &amp; KEPALA SEKOLAH</span>
            <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold mt-1">Pimpinan Dayah Madani Al-Aziziyah</h2>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">SMA Madani Al-Aziziyah dikelola oleh Yayasan Dayah Madani Al-Aziziyah di bawah asuhan pengasuh dayah.</p>
            <div class="grid grid-cols-1 gap-space-md mt-space-md">
              <PersonCard v-for="fig in pembina" :key="fig.name" :figure="fig" layout="featured" />
            </div>
          </div>
          <div class="mt-space-md pt-space-sm text-outline font-label-sm text-label-sm flex items-center justify-between">
            <span>Pengelolaan sekolah berjalan di bawah koordinasi yayasan.</span>
            <span class="material-symbols-outlined text-outline text-[18px]">account_balance</span>
          </div>
        </div>
      </div>
    </section>

    <CurriculumSection />
  </PublicLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PublicLayout from '../Components/PublicLayout.vue'
import PersonCard from '../Components/PersonCard.vue'
import SchoolProfile from '../Components/SchoolProfile.vue'
import CurriculumSection from '../Components/CurriculumSection.vue'
import Reveal from '../Components/Reveal.vue'

const heroStats = [
  { value: 'B', label: 'AKREDITASI SMP (BAN-S/M)', color: 'text-primary' },
  { value: 'B', label: 'AKREDITASI SMA (BAN-S/M)', color: 'text-secondary' },
]

const legal = [
  { label: 'NPSN:', value: '69986030' },
  { label: 'Akreditasi SMP:', value: 'B (BAN-S/M)', highlight: true },
  { label: 'Akreditasi SMA:', value: 'B (BAN-S/M)', highlight: true },
]

const fallbackPembina = [
  {
    name: 'Dr. Tgk. H. Muhammad Hatta, Lc., M.Ed. (Abiya Hatta)',
    role: 'PENGASUH DAYAH MADANI AL-AZIZIYAH',
    roleColor: 'text-primary',
    photo: '/images/profile/4.png',
    alt: 'Dr. Tgk. H. Muhammad Hatta, Lc., M.Ed. (Abiya Hatta)',
    bio: 'Pengasuh Dayah Madani Al-Aziziyah yang memadukan tradisi pendidikan dayah salafiyah dengan pendidikan akademik formal.',
    riwayat: [
      { deg: 'Pendidikan Dayah', inst: 'Alumnus pendidikan dayah salafiyah Dayah MUDI Mesjid Raya Samalanga, Bireuen, Aceh.' },
      { deg: 'Doktor (S3)', inst: 'Pascasarjana UIN Ar-Raniry Banda Aceh dengan predikat "Terpuji" (Cumlaude). Disertasi: "Dinamika Jaringan Ulama Dalam Perkembangan Pendidikan Dayah Salafiyah di Aceh".' },
    ],
  },
  {
    name: 'Fahmi, M.Pd.',
    role: 'KEPALA SEKOLAH & SEKRETARIS',
    roleColor: 'text-primary',
    photo: '/images/profile/3.png',
    alt: 'Fahmi, M.Pd.',
    bio: 'Fahmi, M.Pd. menjabat sebagai Kepala Sekolah SMA Madani Al-Aziziyah sekaligus Sekretaris, berperan dalam pengelolaan akademik dan administrasi kelembagaan.',
  },
]

const pembina = ref(fallbackPembina)

function normalize(name) { return String(name).toLowerCase().replace(/[^a-z0-9]+/g, '') }

function photoUrl(path, fbPhoto) {
  if (!path) return fbPhoto || '/images/emblem.svg'
  if (/^https?:\/\//.test(path) || path.startsWith('/')) return path
  return `${location.origin}/storage/${path}`
}

onMounted(async () => {
  try {
    const r = await fetch('/api/school-principals/current')
    if (!r.ok) return
    const list = await r.json()
    if (!Array.isArray(list) || !list.length) return
    const byName = Object.fromEntries(fallbackPembina.map((f) => [normalize(f.name), f]))
    pembina.value = list.map((p) => {
      const fb = byName[normalize(p.name)] || {}
      return {
        name: p.name,
        role: p.title || fb.role || 'PIMPINAN',
        roleColor: fb.roleColor || 'text-primary',
        photo: photoUrl(p.photo_path, fb.photo),
        alt: fb.alt || p.name,
        bio: fb.bio,
        riwayat: fb.riwayat || [],
      }
    })
  } catch { /* fallback tetap ditampilkan */ }
})
</script>