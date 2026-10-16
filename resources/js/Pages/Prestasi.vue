<template>
  <PublicLayout>
    <div class="w-full bg-surface-container-low px-gutter py-space-sm">
      <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-space-xs">
        <div class="flex items-center gap-space-xs font-label-sm text-label-sm text-on-surface-variant">
          <router-link to="/sma" class="hover:text-primary transition-colors">Beranda</router-link>
          <span class="material-symbols-outlined text-[13px] text-outline">chevron_right</span>
          <span class="text-on-surface font-semibold">Prestasi &amp; Pengakuan Sekolah</span>
        </div>
        <span class="font-code-md text-code-md text-outline">REKAM JEJAK RESMI SATUAN PENDIDIKAN</span>
      </div>
    </div>

    <section class="relative w-full overflow-hidden bg-surface-container-lowest py-space-xl">
      <div class="max-w-7xl mx-auto px-gutter relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-xl items-start">
          <div class="lg:col-span-8 flex flex-col gap-space-md">
            <Reveal slow>
              <div class="inline-flex items-center gap-space-xs w-fit bg-secondary-fixed text-on-secondary-fixed px-space-md py-1 rounded-full font-label-sm text-label-sm">
                <span class="material-symbols-outlined text-[15px]">military_tech</span>
                <span>REKAM JEJAK &amp; PENGUKUHAN KELEMBAGAAN</span>
              </div>
            </Reveal>
            <Reveal slow :delay="160">
              <h1 class="font-headline-lg text-headline-lg lg:text-[36px] lg:leading-[44px] text-on-surface font-bold tracking-tight">Prestasi dan Pengakuan Satuan Pendidikan Madani Al Aziziyah</h1>
            </Reveal>
            <Reveal slow :delay="320">
              <p class="font-body-lg text-body-lg text-on-surface-variant max-w-3xl leading-relaxed">Halaman ini menampilkan rekam jejak dan pengakuan resmi yang telah diraih oleh satuan pendidikan Madani Al Aziziyah, baik dalam bidang akreditasi, pendidikan, maupun sosial kemanusiaan.</p>
            </Reveal>
          </div>
          <div class="lg:col-span-4 flex flex-col justify-end">
            <Reveal slow :delay="480" class="block h-full">
              <div class="bg-surface-container p-space-md rounded shadow-md flex flex-col gap-space-xs h-full landing-card">
                <div class="flex items-center justify-between">
                  <span class="font-label-sm text-label-sm text-outline uppercase tracking-wider">Akreditasi Satuan Pendidikan</span>
                  <span class="material-symbols-outlined text-primary text-[20px] landing-float">verified_user</span>
                </div>
                <div class="font-display-lg text-display-lg font-bold text-on-surface">B (BAN-S/M)</div>
                <p class="font-body-sm text-body-sm text-on-surface-variant">Peringkat akreditasi B untuk jenjang SMP dan SMA Madani Al-Aziziyah.</p>
              </div>
            </Reveal>
          </div>
        </div>
      </div>
    </section>

    <section class="w-full py-space-xl">
      <div class="max-w-7xl mx-auto px-gutter flex flex-col gap-space-xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-lg">
          <article v-for="(a, i) in achievements" :key="a.title" class="stagger-slow landing-card flex flex-col bg-surface-container-lowest rounded shadow-md overflow-hidden" :style="{ animationDelay: `${i * 140}ms` }">
            <div class="p-space-lg flex flex-col flex-1 justify-between gap-space-md">
              <div class="flex flex-col gap-space-xs">
                <div class="flex items-center justify-between mb-1">
                  <span class="font-code-md text-code-md px-2 py-0.5 rounded-full shadow-sm" :class="a.badge">{{ a.tag }}</span>
                  <span class="material-symbols-outlined text-[22px]" :class="a.iconClass">{{ a.icon }}</span>
                </div>
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold leading-snug">{{ a.title }}</h3>
                <p class="font-body-md text-body-md text-on-surface-variant">{{ a.desc }}</p>
              </div>
              <div class="pt-space-sm border-t border-surface-container flex items-center gap-2">
                <span class="font-label-sm text-label-sm text-on-surface uppercase font-semibold" :class="a.levelClass">{{ a.level }}</span>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup>
import PublicLayout from '../Components/PublicLayout.vue'
import Reveal from '../Components/Reveal.vue'

const achievements = [
  {
    tag: 'AKREDITASI', badge: 'bg-primary-fixed text-on-primary-fixed', icon: 'verified', iconClass: 'text-primary',
    title: 'Terakreditasi B — SMP Madani Al-Aziziyah',
    desc: 'SMP Madani Al-Aziziyah meraih peringkat akreditasi B dari Badan Akreditasi Nasional Sekolah/Madrasah (BAN-S/M).',
    level: 'Akreditasi Nasional', levelClass: 'text-primary',
  },
  {
    tag: 'AKREDITASI', badge: 'bg-secondary-fixed text-on-secondary-fixed', icon: 'verified', iconClass: 'text-secondary',
    title: 'Terakreditasi B — SMA Madani Al-Aziziyah',
    desc: 'SMA Madani Al-Aziziyah meraih peringkat akreditasi B dari Badan Akreditasi Nasional Sekolah/Madrasah (BAN-S/M).',
    level: 'Akreditasi Nasional', levelClass: 'text-secondary',
  },
  {
    tag: 'KEPENDIDIKAN', badge: 'bg-tertiary-fixed text-on-tertiary-fixed', icon: 'school', iconClass: 'text-tertiary',
    title: 'Doktoral "Terpuji (Cumlaude)" Dr. Tgk. H. Muhammad Hatta, Lc., M.Ed.',
    desc: 'Pengasuh Dayah Madani Al-Aziziyah meraih gelar Doktor dari Program Pascasarjana UIN Ar-Raniry Banda Aceh dengan predikat Terpuji (Cumlaude).',
    level: 'Pendidikan', levelClass: 'text-tertiary',
  },
  {
    tag: 'KEMANUSIAAN', badge: 'bg-primary-container text-on-primary-container', icon: 'volunteer_activism', iconClass: 'text-primary',
    title: 'Kontribusi Kemanusiaan untuk Palestina',
    desc: 'Dukungan dari lingkungan Dayah Madani Al-Aziziyah dalam kegiatan kemanusiaan untuk Palestina melalui Majelis Permusyawaratan Ulama (MPU) Aceh.',
    level: 'Sosial & Kemanusiaan', levelClass: 'text-primary',
  },
]
</script>
