<template>
  <LpiLayout>
    <!-- Hero -->
    <section class="lpi-profil-hero relative w-full overflow-hidden text-white">
      <div class="absolute inset-0 opacity-25" aria-hidden="true">
        <img src="/images/landing/hero-campus.jpeg" alt="" class="w-full h-full object-cover" />
      </div>
      <div class="absolute inset-0 lpi-profil-hero__wash" aria-hidden="true"></div>
      <div class="lpi-profil-hero__glow lpi-profil-hero__glow--1" aria-hidden="true"></div>
      <div class="lpi-profil-hero__glow lpi-profil-hero__glow--2" aria-hidden="true"></div>
      <div class="relative w-full max-w-7xl mx-auto px-gutter pt-space-xl pb-space-lg flex flex-col gap-space-md">
        <span class="hero-rise glass-sheen inline-flex w-fit items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 font-label-sm text-label-sm uppercase tracking-wider" style="--enter-delay: 60ms">
          <span class="material-symbols-outlined text-[16px]">account_balance</span>
          {{ profilMeta.badge }}
        </span>
        <h1 class="hero-rise font-display-lg text-display-lg font-bold tracking-tight uppercase max-w-4xl leading-tight" style="--enter-delay: 140ms">{{ profilMeta.title }}</h1>
        <p class="hero-rise font-body-lg text-body-lg text-white/85 max-w-3xl leading-relaxed" style="--enter-delay: 220ms">{{ profilMeta.intro }}</p>
        <div class="hero-rise flex flex-wrap gap-space-sm pt-space-xs" style="--enter-delay: 300ms">
          <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20 font-body-sm text-body-sm backdrop-blur-sm">
            <span class="material-symbols-outlined text-[18px]">calendar_month</span> Berdiri {{ profilMeta.established }}
          </span>
          <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20 font-body-sm text-body-sm backdrop-blur-sm">
            <span class="material-symbols-outlined text-[18px]">location_on</span> {{ profilMeta.location }}
          </span>
        </div>
      </div>
    </section>

    <!-- Statistik ringkas -->
    <section class="w-full bg-surface-container-low border-b border-outline-variant">
      <div class="w-full max-w-7xl mx-auto px-gutter py-space-md">
        <Reveal variant="in" class="stagger-in grid grid-cols-2 lg:grid-cols-4 gap-space-sm">
          <div v-for="(s, i) in profilStats" :key="s.label" :style="{ '--i': i }" class="glass-card rounded-2xl shadow-sm p-space-sm">
            <div class="font-headline-md text-headline-md font-bold text-primary">
              <CountUp :value="s.value" />
            </div>
            <div class="font-body-sm text-body-sm text-on-surface-variant mt-1">{{ s.label }}</div>
          </div>
        </Reveal>
      </div>
    </section>

    <section class="w-full max-w-7xl mx-auto px-gutter py-space-lg grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">
      <!-- Navigasi bagian -->
      <aside class="lg:col-span-3 lg:sticky lg:top-24 hidden lg:block">
        <nav class="glass-panel rounded-3xl p-space-sm flex flex-col gap-1" aria-label="Navigasi profil">
          <a
            v-for="sec in profilSections"
            :key="'nav-' + sec.id"
            :href="'#' + sec.id"
            class="flex items-center gap-2 px-3 py-2 rounded-xl font-body-sm text-body-sm text-on-surface-variant hover:bg-surface-container-low hover:text-primary transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"
          >
            <span class="material-symbols-outlined text-[18px] text-primary">{{ sec.icon }}</span>
            <span>{{ sec.title }}</span>
          </a>
        </nav>
      </aside>

      <!-- Konten -->
      <div class="lg:col-span-9 flex flex-col gap-space-lg">
        <Reveal
          v-for="sec in profilSections"
          :key="sec.id"
          :id="sec.id"
          as="article"
          :delay="0"
          class="scroll-mt-24 bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-md md:p-space-lg"
        >
          <header class="flex items-center gap-3 pb-space-sm border-b border-outline-variant">
            <span class="w-11 h-11 rounded-2xl bg-primary-fixed text-primary flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[22px]">{{ sec.icon }}</span>
            </span>
            <h2 class="font-headline-md text-headline-md font-bold text-on-surface">{{ sec.title }}</h2>
          </header>

          <div class="pt-space-sm flex flex-col gap-space-sm">
            <!-- Timeline sejarah (hanya dari data dokumen) -->
            <ol v-if="sec.id === 'sejarah'" class="relative flex flex-col gap-space-sm pl-6 pt-space-xs">
              <span class="absolute left-[7px] top-2 bottom-2 w-px bg-outline-variant" aria-hidden="true"></span>
              <li v-for="(t, i) in profilTimeline" :key="t.year" class="relative">
                <span class="absolute -left-6 top-1.5 w-3.5 h-3.5 rounded-full bg-primary ring-4 ring-primary-fixed" aria-hidden="true"></span>
                <div class="flex flex-wrap items-baseline gap-2">
                  <span class="font-headline-sm text-headline-sm font-bold text-primary">{{ t.year }}</span>
                  <span class="font-title-md text-title-md font-semibold text-on-surface">{{ t.title }}</span>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">{{ t.desc }}</p>
              </li>
            </ol>

            <p v-for="(p, i) in sec.paragraphs || []" :key="'p-' + sec.id + i" class="font-body-md text-body-md text-on-surface-variant leading-relaxed">{{ p }}</p>

            <ol v-if="sec.mission" class="flex flex-col gap-2 list-decimal pl-5 font-body-md text-body-md text-on-surface-variant">
              <li v-for="(m, i) in sec.mission" :key="'m-' + i">{{ m }}</li>
            </ol>

            <!-- Bidang keilmuan -->
            <div v-if="sec.subjects" class="flex flex-col gap-2">
              <h3 class="font-title-md text-title-md font-semibold text-on-surface">{{ sec.subjectsTitle }}</h3>
              <Reveal variant="in" class="stagger-in flex flex-wrap gap-2">
                <span
                  v-for="(s, i) in sec.subjects"
                  :key="s"
                  :style="{ '--i': i }"
                  class="px-3 py-1.5 rounded-full bg-surface-container-low border border-outline-variant font-body-sm text-body-sm text-on-surface transition-colors hover:border-primary"
                >{{ s }}</span>
              </Reveal>
            </div>

            <!-- Sorotan sejarah -->
            <Reveal v-if="sec.highlights" variant="in" class="stagger-in grid grid-cols-2 sm:grid-cols-4 gap-space-xs pt-space-xs">
              <div v-for="(h, i) in sec.highlights" :key="h.label" :style="{ '--i': i }" class="glass-card rounded-2xl p-space-sm">
                <div class="font-headline-sm text-headline-sm font-bold text-primary">{{ h.value }}</div>
                <div class="font-label-sm text-label-sm text-on-surface-variant mt-1 leading-snug">{{ h.label }}</div>
              </div>
            </Reveal>

            <!-- Kartu jenjang pendidikan -->
            <div v-if="sec.cards" class="grid grid-cols-1 md:grid-cols-3 gap-space-sm pt-space-xs">
              <Reveal v-for="(c, i) in sec.cards" :key="c.title" :delay="i * 80" class="h-full">
                <InteractiveCard class="h-full bg-surface-container-low border border-outline-variant rounded-2xl p-space-md flex flex-col gap-2">
                  <span class="w-10 h-10 rounded-xl bg-primary-fixed text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">{{ c.icon }}</span>
                  </span>
                  <h3 class="font-title-md text-title-md font-semibold text-on-surface">{{ c.title }}</h3>
                  <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">{{ c.desc }}</p>
                </InteractiveCard>
              </Reveal>
            </div>

            <!-- Daftar butir biasa -->
            <Reveal v-if="sec.list" variant="in" class="stagger-in grid grid-cols-1 sm:grid-cols-2 gap-2 pt-space-xs">
              <div v-for="(item, i) in sec.list" :key="item" :style="{ '--i': i }" class="flex items-start gap-2 font-body-md text-body-md text-on-surface-variant">
                <span class="material-symbols-outlined text-primary text-[18px] mt-0.5">check_circle</span>
                <span>{{ item }}</span>
              </div>
            </Reveal>

            <!-- Daftar bernomor -->
            <ol v-if="sec.ordered" class="flex flex-col gap-2 list-decimal pl-5 font-body-md text-body-md text-on-surface-variant">
              <li v-for="(item, i) in sec.ordered" :key="'o-' + i">{{ item }}</li>
            </ol>

            <!-- Chip nilai dasar -->
            <Reveal v-if="sec.chips" variant="in" class="stagger-in flex flex-wrap gap-2 pt-space-xs">
              <span
                v-for="(c, i) in sec.chips"
                :key="c"
                :style="{ '--i': i }"
                class="px-4 py-2 rounded-full bg-primary-fixed text-primary font-title-md text-title-md transition-transform duration-300 hover:scale-105"
              >{{ c }}</span>
            </Reveal>

            <!-- Catatan -->
            <div v-if="sec.note" class="flex items-start gap-2 mt-space-xs p-space-sm rounded-2xl bg-tertiary-fixed text-on-surface">
              <span class="material-symbols-outlined text-tertiary text-[20px] shrink-0">info</span>
              <p class="font-body-sm text-body-sm leading-relaxed">{{ sec.note }}</p>
            </div>
          </div>
        </Reveal>
      </div>
    </section>

    <!-- Ajakan lanjutan -->
    <section class="w-full max-w-7xl mx-auto px-gutter pb-space-xl">
      <Reveal variant="scale">
        <div class="lpi-cta-panel rounded-[28px] text-white p-space-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-space-md">
          <div>
            <h2 class="font-headline-md text-headline-md font-bold">Kenali struktur kepengurusan lembaga</h2>
            <p class="font-body-md text-body-md text-white/80 mt-1">Lihat bagan Struktur Organisasi LPI Dayah Madani Al-Aziziyah.</p>
          </div>
          <router-link to="/struktur" class="btn-skeu keep-white inline-flex items-center gap-2 px-5 h-11 rounded-full bg-white text-primary font-title-md text-title-md shrink-0">
            <span class="material-symbols-outlined text-[20px]">account_tree</span>
            Struktur Organisasi
          </router-link>
        </div>
      </Reveal>
    </section>
  </LpiLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import LpiLayout from '../../Components/LpiLayout.vue'
import Reveal from '../../Components/Reveal.vue'
import InteractiveCard from '../../Components/InteractiveCard.vue'
import CountUp from '../../Components/CountUp.vue'
import { profilMeta, profilStats, profilSections, profilTimeline } from '../../data/profileDayah.js'
import { useSeo } from '../../composables/useSeo.js'

onMounted(() => {
  useSeo({
    title: 'Profil Dayah',
    description: profilMeta.intro,
    url: location.href,
  })
})
</script>

<style scoped>
.lpi-profil-hero {
  background: linear-gradient(135deg, #0d4835 0%, #326854 52%, #18503d 100%);
}
.lpi-profil-hero__wash {
  background: linear-gradient(180deg, rgba(0, 33, 22, 0.55) 0%, rgba(0, 33, 22, 0.78) 100%);
}
.lpi-profil-hero__glow {
  position: absolute;
  border-radius: 9999px;
  filter: blur(80px);
  pointer-events: none;
}
.lpi-profil-hero__glow--1 {
  width: 40vw;
  height: 40vw;
  min-width: 280px;
  min-height: 280px;
  top: -18%;
  right: -6%;
  background: rgba(254, 214, 91, 0.16);
  animation: profilDrift 24s ease-in-out infinite alternate;
}
.lpi-profil-hero__glow--2 {
  width: 36vw;
  height: 36vw;
  min-width: 240px;
  min-height: 240px;
  bottom: -22%;
  left: -8%;
  background: rgba(182, 239, 213, 0.18);
  animation: profilDrift 30s ease-in-out infinite alternate-reverse;
}
@keyframes profilDrift {
  from { transform: translate3d(0, 0, 0) scale(1); }
  to { transform: translate3d(-4%, 6%, 0) scale(1.12); }
}

.lpi-cta-panel {
  background: linear-gradient(135deg, #18503d 0%, #326854 60%, #0d4835 100%);
  box-shadow: 0 24px 50px -26px rgba(13, 72, 53, 0.7);
}

@media (prefers-reduced-motion: reduce) {
  .lpi-profil-hero__glow { animation: none !important; }
}
</style>
