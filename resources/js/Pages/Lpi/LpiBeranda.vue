<template>
  <LpiLayout>
    <section class="relative isolate w-full min-h-[100svh] -mt-20 flex items-center justify-center overflow-hidden">
      <div ref="heroBg" class="absolute inset-0 z-0" aria-hidden="true">
        <template v-if="!hasMedia">
          <div class="lpi-hero-gradient absolute inset-0"></div>
          <div class="lpi-hero-blob lpi-hero-blob--1"></div>
          <div class="lpi-hero-blob lpi-hero-blob--2"></div>
          <div class="lpi-hero-blob lpi-hero-blob--3"></div>
          <div class="lpi-hero-pattern absolute inset-0"></div>
        </template>
        <template v-else>
          <video
            v-if="videoUrl"
            class="absolute inset-0 h-full w-full object-cover"
            :src="videoUrl"
            :poster="videoPoster"
            autoplay
            muted
            loop
            playsinline
            preload="metadata"
          ></video>
          <template v-else>
            <img
              v-for="(url, i) in imageUrls"
              :key="url"
              class="lpi-hero-slide absolute inset-0 h-full w-full object-cover"
              :class="{ 'is-active': i === slideIndex }"
              :src="url"
              alt=""
              loading="eager"
            />
          </template>
        </template>
      </div>

      <div class="lpi-hero-overlay absolute inset-0 z-10" :style="{ opacity: overlayOpacity }"></div>
      <div class="lpi-hero-scrim absolute inset-0 z-10"></div>

      <div class="relative z-20 w-full max-w-3xl mx-auto px-gutter pt-28 pb-space-xl flex flex-col items-center text-center gap-space-md">
        <div class="hero-scale lpi-hero-logo logo-plate" style="--enter-delay: 40ms">
          <img :src="logoUrl" alt="Logo LPI Dayah Madani Al-Aziziyah" width="96" height="96" class="h-full w-full object-contain" />
        </div>

        <span class="hero-rise lpi-hero-badge" style="--enter-delay: 160ms">{{ badgeText }}</span>

        <h1 class="hero-rise font-display-lg text-display-lg text-white font-bold tracking-tight uppercase drop-shadow-sm" style="--enter-delay: 240ms">{{ heroTitle }}</h1>

        <p class="hero-rise font-body-lg text-body-lg text-white/85 max-w-2xl leading-relaxed" style="--enter-delay: 340ms">{{ tagline }}</p>

        <div v-if="prayerVisible" class="hero-rise lpi-prayer-card mt-space-sm bg-white shadow-xl flex flex-col items-center gap-1" style="--enter-delay: 440ms">
          <div class="flex items-center gap-1.5 text-primary">
            <span class="material-symbols-outlined text-[18px]">dark_mode</span>
            <span class="font-label-md text-label-md uppercase tracking-widest font-bold">Menuju {{ nextPrayerLabel }}</span>
          </div>
          <div class="lpi-prayer-countdown font-display tracking-tight text-primary">{{ countdown }}</div>
          <div class="font-body-sm text-body-sm text-on-surface-variant">
            <template v-if="ready">{{ nextPrayerLabel }} pukul {{ nextClock }} · {{ cityName }}</template>
            <template v-else>Atur lokasi salat di pengaturan</template>
          </div>
        </div>
      </div>

      <div class="absolute bottom-space-md left-1/2 -translate-x-1/2 z-20 text-white/70 lpi-hero-scroll" aria-hidden="true">
        <span class="material-symbols-outlined text-[28px]">keyboard_arrow_down</span>
      </div>
    </section>

    <section class="w-full max-w-7xl mx-auto px-gutter py-space-2xl flex flex-col gap-space-lg">
      <Reveal class="flex flex-col gap-space-xs">
        <span class="font-code-md text-code-md text-primary uppercase font-bold tracking-wider">Program Pendidikan</span>
        <h2 class="font-headline-lg text-headline-lg font-bold tracking-tight">Program {{ brand.short }}</h2>
        <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl">Pendidikan formal dan kepesantrenan yang terpadu di lingkungan Dayah Madani Al-Aziziyah.</p>
      </Reveal>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
        <Reveal v-for="(p, i) in programs" :key="p.key" :delay="i * 90" class="h-full">
          <InteractiveCard class="h-full bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-md flex flex-col gap-space-xs">
            <span class="material-symbols-outlined text-primary text-[28px]">{{ p.icon }}</span>
            <h3 class="font-headline-sm text-headline-sm font-semibold">{{ p.name }}</h3>
            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ p.active ? p.desc : 'Segera hadir' }}</p>
            <router-link v-if="p.active" :to="p.href" class="inline-flex items-center gap-1 font-title-md text-primary underline underline-offset-4 w-fit mt-auto">
              <span>Kunjungi situs</span>
              <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </router-link>
            <span v-else class="inline-flex items-center gap-1 font-title-md text-on-surface-variant mt-auto">
              <span class="material-symbols-outlined text-[16px]">schedule</span>
              <span>Segera hadir</span>
            </span>
          </InteractiveCard>
        </Reveal>
      </div>
    </section>

    <section class="w-full bg-surface-container-low px-gutter py-space-2xl">
      <div class="max-w-7xl mx-auto flex flex-col gap-space-lg">
        <Reveal class="flex flex-col gap-space-xs">
          <span class="font-code-md text-code-md text-primary uppercase font-bold tracking-wider">Layanan</span>
          <h2 class="font-headline-lg text-headline-lg font-bold tracking-tight">Layanan {{ brand.short }}</h2>
        </Reveal>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
          <Reveal v-for="(l, i) in layanan" :key="l.name" :delay="i * 80" class="h-full">
            <InteractiveCard class="h-full bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-md flex flex-col gap-space-sm">
              <span class="w-11 h-11 rounded-2xl bg-primary-fixed text-primary flex items-center justify-center">
                <span class="material-symbols-outlined text-[26px]">{{ l.icon }}</span>
              </span>
              <h3 class="font-title-md text-title-md font-semibold">{{ l.name }}</h3>
              <p class="font-body-sm text-body-sm text-on-surface-variant">{{ l.desc }}</p>
            </InteractiveCard>
          </Reveal>
        </div>
      </div>
    </section>

    <section class="w-full max-w-7xl mx-auto px-gutter py-space-2xl flex flex-col gap-space-lg">
      <Reveal class="flex items-center justify-between gap-space-sm">
        <div class="flex flex-col gap-space-xs">
          <span class="font-code-md text-code-md text-primary uppercase font-bold tracking-wider">Informasi</span>
          <h2 class="font-headline-lg text-headline-lg font-bold tracking-tight">Berita Terbaru</h2>
        </div>
        <router-link to="/sma/berita" class="hidden sm:inline-flex items-center gap-1 font-title-md text-primary hover:underline underline-offset-4">
          <span>Semua berita</span>
          <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </router-link>
      </Reveal>
      <div v-if="newsLoading" class="py-space-lg"><EmptyState icon="hourglass_empty" title="Memuat berita" description="Mohon tunggu, berita sedang dimuat." /></div>
      <div v-else-if="news.length" class="grid grid-cols-1 md:grid-cols-3 gap-space-md">
        <Reveal v-for="(n, i) in news" :key="n.id" :delay="i * 90" class="h-full">
          <InteractiveCard class="h-full flex flex-col bg-surface-container-lowest rounded-[28px] border border-outline-variant shadow-sm overflow-hidden">
            <router-link :to="'/sma/berita/' + n.id" class="flex flex-col h-full">
              <div class="relative h-44 w-full bg-surface-container overflow-hidden">
                <img v-if="n.image_path" class="zoom-media w-full h-full object-cover" :src="'/storage/' + n.image_path" :alt="n.title" loading="lazy" />
                <div v-else class="w-full h-full flex items-center justify-center text-outline">
                  <span class="material-symbols-outlined text-[40px]">image</span>
                </div>
              </div>
              <div class="p-space-md flex flex-col gap-space-xs flex-1">
                <span class="font-code-md text-code-md text-on-surface-variant">{{ formatDate(n.published_at) }}</span>
                <h3 class="font-headline-sm text-headline-sm font-semibold leading-snug">{{ n.title }}</h3>
                <p v-if="n.excerpt" class="font-body-sm text-body-sm text-on-surface-variant line-clamp-2">{{ n.excerpt }}</p>
              </div>
            </router-link>
          </InteractiveCard>
        </Reveal>
      </div>
      <EmptyState v-else icon="newspaper" title="Belum ada berita" description="Berita terbaru akan tampil di sini setelah diterbitkan." />
    </section>

    <section class="w-full bg-surface-container-low px-gutter py-space-2xl">
      <div class="max-w-7xl mx-auto flex flex-col gap-space-lg">
        <Reveal class="flex items-center justify-between gap-space-sm">
          <div class="flex flex-col gap-space-xs">
            <span class="font-code-md text-code-md text-primary uppercase font-bold tracking-wider">Dokumentasi</span>
            <h2 class="font-headline-lg text-headline-lg font-bold tracking-tight">Galeri Kegiatan</h2>
          </div>
          <router-link to="/sma/galeri" class="hidden sm:inline-flex items-center gap-1 font-title-md text-primary hover:underline underline-offset-4">
            <span>Semua galeri</span>
            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
          </router-link>
        </Reveal>
        <div v-if="galleryLoading" class="py-space-lg"><EmptyState icon="hourglass_empty" title="Memuat galeri" description="Mohon tunggu, galeri sedang dimuat." /></div>
        <div v-else-if="albums.length" class="grid grid-cols-1 md:grid-cols-3 gap-space-md">
          <Reveal v-for="(a, i) in albums" :key="a.id" :delay="i * 90" class="h-full">
            <InteractiveCard class="h-full flex flex-col bg-surface-container-lowest rounded-[28px] border border-outline-variant shadow-sm overflow-hidden">
              <router-link :to="'/sma/galeri/' + a.id" class="flex flex-col h-full">
                <div class="relative h-48 w-full bg-surface-container overflow-hidden">
                  <img v-if="coverUrl(a)" class="zoom-media w-full h-full object-cover" :src="coverUrl(a)" :alt="a.title" loading="lazy" />
                  <div v-else class="w-full h-full flex items-center justify-center text-outline">
                    <span class="material-symbols-outlined text-[40px]">photo_library</span>
                  </div>
                </div>
                <div class="p-space-md flex flex-col gap-space-xs flex-1">
                  <h3 class="font-headline-sm text-headline-sm font-semibold leading-snug">{{ a.title }}</h3>
                  <p v-if="a.description" class="font-body-sm text-body-sm text-on-surface-variant line-clamp-2">{{ a.description }}</p>
                </div>
              </router-link>
            </InteractiveCard>
          </Reveal>
        </div>
        <EmptyState v-else icon="photo_library" title="Belum ada album" description="Galeri kegiatan akan tampil di sini setelah diterbitkan." />
      </div>
    </section>
  </LpiLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import LpiLayout from '../../Components/LpiLayout.vue'
import EmptyState from '../../Components/EmptyState.vue'
import Reveal from '../../Components/Reveal.vue'
import InteractiveCard from '../../Components/InteractiveCard.vue'
import { brand, layanan, programs } from '../../data/lpi.js'
import { useHomepage } from '../../composables/useHomepage.js'
import { usePrayerTimes } from '../../composables/usePrayerTimes.js'
import { useSeo } from '../../composables/useSeo.js'

const { homepage, load, asset } = useHomepage()

const fallbackLogo = '/images/profile/logo-lpi-transparent.png'

const badgeText = computed(() => homepage.value?.homepage_badge_text || 'Lembaga Pendidikan Islam')
const heroTitle = computed(() => homepage.value?.homepage_title || brand.name)
const tagline = computed(() => homepage.value?.homepage_tagline || brand.tagline)
const logoUrl = computed(() => asset(homepage.value?.homepage_logo_path) || asset(homepage.value?.logo_path) || fallbackLogo)
const overlayOpacity = computed(() => {
  const raw = homepage.value?.hero_overlay_opacity
  const value = Number.isFinite(Number(raw)) ? Number(raw) : 40
  return Math.min(80, Math.max(0, value)) / 100
})
const cityName = computed(() => homepage.value?.prayer_city_name || 'Banda Aceh')
const prayerVisible = computed(() => homepage.value?.prayer_card_visible !== false)

const media = computed(() => (Array.isArray(homepage.value?.hero_media) ? homepage.value.hero_media : []))
const videoMedia = computed(() => media.value.find((m) => m.type === 'video'))
const videoUrl = computed(() => (videoMedia.value ? asset(videoMedia.value.url) : null))
const videoPoster = computed(() => (videoMedia.value?.poster ? asset(videoMedia.value.poster) : null))
const imageUrls = computed(() => media.value.filter((m) => m.type === 'image').map((m) => asset(m.url)).filter(Boolean))
const hasMedia = computed(() => Boolean(videoUrl.value) || imageUrls.value.length > 0)

const slideIndex = ref(0)
let slideTimer = null

const heroBg = ref(null)
let parallaxFrame = null

function applyParallax() {
  parallaxFrame = null
  if (!heroBg.value) return
  const y = Math.min(window.scrollY, 640)
  heroBg.value.style.transform = `translate3d(0, ${(y * 0.12).toFixed(2)}px, 0)`
}

function onParallaxScroll() {
  if (parallaxFrame == null) parallaxFrame = requestAnimationFrame(applyParallax)
}

function startSlideshow() {
  stopSlideshow()
  if (videoUrl.value || imageUrls.value.length < 2) return
  const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
  if (reduce) return
  slideTimer = setInterval(() => {
    slideIndex.value = (slideIndex.value + 1) % imageUrls.value.length
  }, 7000)
}
function stopSlideshow() {
  if (slideTimer) {
    clearInterval(slideTimer)
    slideTimer = null
  }
}

const prayerConfig = () => ({
  latitude: homepage.value?.prayer_latitude,
  longitude: homepage.value?.prayer_longitude,
  timeZone: homepage.value?.prayer_timezone || 'Asia/Jakarta',
})
const { start: startPrayer, nextPrayerLabel, countdown, nextClock, ready } = usePrayerTimes(prayerConfig)

const news = ref([])
const newsLoading = ref(true)
const albums = ref([])
const galleryLoading = ref(true)

function formatDate(value) {
  if (!value) return ''
  return new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}
function coverUrl(a) {
  if (a.cover_path) return '/storage/' + a.cover_path
  const first = (a.items || [])[0]
  return first?.image_path ? '/storage/' + first.image_path : ''
}

onMounted(async () => {
  useSeo({ title: 'Beranda', description: brand.tagline, url: location.href })

  await load()
  startPrayer()
  startSlideshow()

  const fine = window.matchMedia?.('(pointer: fine)').matches
  const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
  if (fine && !reduce) {
    window.addEventListener('scroll', onParallaxScroll, { passive: true })
    applyParallax()
  }

  try {
    const r = await fetch('/api/news/public')
    if (r.ok) {
      const j = await r.json()
      news.value = (Array.isArray(j) ? j : j.data || []).slice(0, 3)
    }
  } catch { /* abaikan */ } finally {
    newsLoading.value = false
  }

  try {
    const r = await fetch('/api/gallery/public')
    if (r.ok) {
      const j = await r.json()
      albums.value = (Array.isArray(j) ? j : []).slice(0, 3)
    }
  } catch { /* abaikan */ } finally {
    galleryLoading.value = false
  }
})

onBeforeUnmount(() => {
  stopSlideshow()
  window.removeEventListener('scroll', onParallaxScroll)
  if (parallaxFrame != null) cancelAnimationFrame(parallaxFrame)
})
</script>

<style scoped>
.lpi-hero-gradient {
  background: linear-gradient(135deg, #7fb69e 0%, #98aeb9 42%, #fed65b 100%);
}

.lpi-hero-blob {
  position: absolute;
  border-radius: 9999px;
  filter: blur(70px);
  opacity: 0.4;
  will-change: transform;
}
.lpi-hero-blob--1 {
  width: 44vw;
  height: 44vw;
  min-width: 320px;
  min-height: 320px;
  top: -10%;
  left: -6%;
  background: #cfe6f2;
  animation: lpiDrift1 26s ease-in-out infinite alternate;
}
.lpi-hero-blob--2 {
  width: 40vw;
  height: 40vw;
  min-width: 300px;
  min-height: 300px;
  bottom: -14%;
  right: -8%;
  background: #ffe088;
  animation: lpiDrift2 32s ease-in-out infinite alternate;
}
.lpi-hero-blob--3 {
  width: 32vw;
  height: 32vw;
  min-width: 240px;
  min-height: 240px;
  top: 24%;
  right: 22%;
  background: #b6efd5;
  opacity: 0.4;
  animation: lpiDrift3 38s ease-in-out infinite alternate;
}

.lpi-hero-pattern {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='72' height='72' viewBox='0 0 72 72'%3E%3Cg fill='none' stroke='%23ffffff' stroke-opacity='0.35' stroke-width='1'%3E%3Crect x='20' y='20' width='32' height='32'/%3E%3Crect x='20' y='20' width='32' height='32' transform='rotate(45 36 36)'/%3E%3Ccircle cx='36' cy='36' r='6'/%3E%3C/g%3E%3C/svg%3E");
  background-size: 72px 72px;
  opacity: 0.5;
  animation: lpiPatternPan 90s linear infinite;
}

@keyframes lpiDrift1 {
  from { transform: translate3d(0, 0, 0) scale(1); }
  to { transform: translate3d(8%, 10%, 0) scale(1.15); }
}
@keyframes lpiDrift2 {
  from { transform: translate3d(0, 0, 0) scale(1.1); }
  to { transform: translate3d(-10%, -6%, 0) scale(1); }
}
@keyframes lpiDrift3 {
  from { transform: translate3d(0, 0, 0) scale(1); }
  to { transform: translate3d(-6%, 12%, 0) scale(1.2); }
}
@keyframes lpiPatternPan {
  from { background-position: 0 0; }
  to { background-position: 288px 288px; }
}

.lpi-hero-overlay {
  background: linear-gradient(180deg, #002116 0%, #0d4835 100%);
}

.lpi-hero-scrim {
  background: radial-gradient(ellipse 62% 58% at 50% 46%, rgba(0, 33, 22, 0.5) 0%, rgba(0, 33, 22, 0.22) 48%, rgba(0, 33, 22, 0) 80%);
}

.lpi-hero-slide {
  opacity: 0;
  transition: opacity 1.6s cubic-bezier(0.22, 1, 0.36, 1);
}
.lpi-hero-slide.is-active {
  opacity: 1;
  animation: lpiKenBurns 9s ease-out both;
}
@keyframes lpiKenBurns {
  from { transform: scale(1); }
  to { transform: scale(1.08); }
}

.lpi-hero-logo {
  width: 96px;
  height: 96px;
  padding: 12px;
  border-radius: 24px;
  overflow: hidden;
}

.lpi-hero-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.3rem 1rem;
  border-radius: 9999px;
  border: 1px solid rgba(255, 255, 255, 0.45);
  background: rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(10px);
  color: #fff;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}

.lpi-prayer-card {
  padding: 1.1rem 1.75rem;
  border-radius: 26px;
  min-width: min(360px, 100%);
}

.lpi-prayer-countdown {
  font-size: clamp(2.25rem, 9vw, 3.25rem);
  line-height: 1.05;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
}

.lpi-hero-scroll {
  animation: lpiBounce 2.4s ease-in-out infinite;
}
@keyframes lpiBounce {
  0%, 100% { transform: translate(-50%, 0); }
  50% { transform: translate(-50%, 8px); }
}

@media (prefers-reduced-motion: reduce) {
  .lpi-hero-blob,
  .lpi-hero-pattern,
  .lpi-hero-slide.is-active,
  .lpi-hero-scroll {
    animation: none !important;
  }
}
</style>
