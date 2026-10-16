<template>
  <div class="min-h-screen bg-surface font-body-md text-body-md text-on-surface antialiased overflow-x-hidden">
    <header class="fixed top-0 w-full z-50 bg-white/85 backdrop-blur-xl border-b border-outline-variant shadow-sm">
      <div class="h-20 w-full px-gutter-desktop mx-auto flex items-center justify-between gap-space-md">
        <div class="flex items-center gap-space-md shrink-0">
          <router-link to="/sma" class="flex items-center gap-space-md">
            <AppLogo :src="logo" :size="34" shape="md" alt="Logo SMA Madani Al Aziziyah" />
            <div class="flex flex-col">
              <span class="font-headline-sm text-headline-sm text-on-surface tracking-tight uppercase leading-none">{{ brand }}</span>
              <span class="font-label-sm text-label-sm text-primary tracking-widest uppercase mt-0.5">Islamic Boarding High School</span>
            </div>
          </router-link>
        </div>
        <nav class="hidden xl:flex items-center gap-space-lg">
          <router-link
            v-for="item in nav"
            :key="item.path"
            :to="item.path"
            class="relative transition-[transform,opacity] duration-200"
            :class="isActive(item) ? 'text-primary font-title-md' : 'text-on-surface-variant font-body-md text-body-md hover:text-on-surface'"
          >
            {{ item.label }}
            <span
              aria-hidden="true"
              class="absolute left-0 -bottom-1 h-0.5 w-full origin-left bg-primary transition-transform duration-200"
              :class="isActive(item) ? 'scale-x-100' : 'scale-x-0'"
            ></span>
          </router-link>
        </nav>
        <div class="flex items-center gap-space-md">
          <button
            class="xl:hidden w-9 h-9 rounded-full border border-outline-variant bg-white shadow-sm flex items-center justify-center transition-transform duration-200"
            :class="menuOpen ? 'rotate-90' : ''"
            @click="menuOpen = !menuOpen"
            aria-label="Menu"
            :aria-expanded="menuOpen"
          >
            <span class="material-symbols-outlined !w-5 !h-5 !text-[22px] transition-opacity duration-150">{{ menuOpen ? 'close' : 'menu' }}</span>
          </button>
        </div>
      </div>
      <Transition name="menu">
        <div v-if="menuOpen" class="xl:hidden bg-surface-container-lowest border-t border-outline-variant px-gutter-desktop py-space-sm flex flex-col gap-1">
          <router-link
            v-for="item in nav"
            :key="'m-' + item.path"
            :to="item.path"
            class="py-2 font-body-md text-body-md"
            :class="isActive(item) ? 'text-primary font-semibold' : 'text-on-surface-variant'"
            @click="menuOpen = false"
          >{{ item.label }}</router-link>
        </div>
      </Transition>
    </header>
    <main class="w-full pt-20 bg-surface min-h-screen">
      <slot />
    </main>
    <footer class="w-full bg-surface-container-low border-t border-outline-variant pt-space-xl pb-space-lg">
      <div class="w-full px-gutter-desktop mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-space-xl pb-space-xl border-b border-outline-variant">
          <div class="flex flex-col gap-space-sm">
            <div class="flex items-center gap-space-xs">
              <span class="font-headline-sm text-headline-sm text-on-surface font-semibold">{{ brand }}</span>
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ summary }}</p>
            <div class="inline-flex items-center gap-space-xs px-space-sm py-1 bg-surface-container-highest border border-secondary rounded-full text-on-secondary-container w-fit mt-space-xs">
              <span class="material-symbols-outlined text-secondary text-[16px]">verified</span>
              <span class="font-label-sm text-label-sm text-on-surface uppercase font-bold tracking-wider">Terakreditasi B (BAN-S/M)</span>
            </div>
          </div>
          <div class="flex flex-col gap-space-sm">
            <h3 class="font-headline-sm text-headline-sm text-on-surface border-b-2 border-primary pb-1 inline-block w-fit">Kampus &amp; Kontak</h3>
            <div class="flex flex-col gap-space-xs font-body-sm text-body-sm text-on-surface-variant">
              <p class="flex items-start gap-space-xs"><span class="material-symbols-outlined text-primary text-[18px] shrink-0 mt-0.5">location_on</span><span>{{ address }}</span></p>
              <p v-if="phones" class="flex items-center gap-space-xs"><span class="material-symbols-outlined text-primary text-[18px] shrink-0">call</span><span>{{ phones }}</span></p>
              <p v-if="email" class="flex items-center gap-space-xs"><span class="material-symbols-outlined text-primary text-[18px] shrink-0">mail</span><span>{{ email }}</span></p>
            </div>
          </div>
          <div class="flex flex-col gap-space-sm">
            <h3 class="font-headline-sm text-headline-sm text-on-surface border-b-2 border-primary pb-1 inline-block w-fit">Akses Cepat</h3>
            <nav class="flex flex-col gap-space-xs font-body-sm text-body-sm text-on-surface-variant">
              <router-link to="/sma/profil" class="hover:text-primary transition-colors">Profil &amp; Kurikulum</router-link>
              <router-link to="/sma/ppdb" class="hover:text-primary transition-colors">Penerimaan Peserta Didik Baru (PPDB)</router-link>
              <router-link to="/sma/prestasi" class="hover:text-primary transition-colors">Prestasi &amp; Pengakuan Sekolah</router-link>
              <router-link to="/sma/fasilitas" class="hover:text-primary transition-colors">Fasilitas Sekolah</router-link>
            </nav>
          </div>
          <div class="flex flex-col gap-space-sm">
            <h3 class="font-headline-sm text-headline-sm text-on-surface border-b-2 border-primary pb-1 inline-block w-fit">Informasi &amp; Media</h3>
            <nav class="flex flex-col gap-space-xs font-body-sm text-body-sm text-on-surface-variant">
              <router-link to="/sma/berita" class="hover:text-primary transition-colors">Berita Sekolah</router-link>
              <router-link to="/sma/galeri" class="hover:text-primary transition-colors">Galeri Kegiatan</router-link>
              <router-link to="/sma/kontak" class="hover:text-primary transition-colors">Kontak &amp; Lokasi</router-link>
            </nav>
          </div>
        </div>
        <div class="pt-space-md flex flex-col md:flex-row items-center justify-between gap-space-sm font-label-sm text-label-sm text-on-surface-variant">
          <p>© {{ new Date().getFullYear() }} {{ brand }}. Hak Cipta Dilindungi Undang-Undang.</p>
          <div class="flex items-center gap-space-md">
            <span class="font-code-md text-code-md text-on-surface-variant">NPSN: {{ npsn }}</span>
            <span v-if="nspp" class="font-code-md text-code-md text-on-surface-variant">NSPP: {{ nspp }}</span>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useSiteSettings } from '../composables/useSiteSettings'
import AppLogo from './AppLogo.vue'

const route = useRoute()
const menuOpen = ref(false)
const { get, asset, load } = useSiteSettings()

onMounted(() => { load() })

const brand = computed(() => get('app_name', 'SMA MADANI AL AZIZIYAH'))
const logo = computed(() => asset(get('logo_path', null)))
const summary = computed(() => get('tagline', 'Lembaga pendidikan Islam terpadu di bawah naungan Yayasan Dayah Madani Al-Aziziyah.'))
const address = computed(() => get('address', 'Jl. T. Imum Hamzah Lr. Dayah, Lampeuneurut Ujong Blang, Kec. Darul Imarah, Kab. Aceh Besar'))
const phones = computed(() => [get('pstn', ''), get('phone', ''), get('wa_helpdesk', '')].map((v) => (v || '').trim()).filter(Boolean).join(' / ') || '')
const email = computed(() => get('email', '') || '')
const npsn = computed(() => get('npsn', '69986030'))
const nspp = computed(() => get('nspp', '') || '')

const nav = [
  { label: 'Beranda', path: '/sma' },
  { label: 'Profil & Kurikulum', path: '/sma/profil' },
  { label: 'Fasilitas', path: '/sma/fasilitas' },
  { label: 'Prestasi', path: '/sma/prestasi' },
  { label: 'Berita', path: '/sma/berita' },
  { label: 'Galeri', path: '/sma/galeri' },
  { label: 'Penerimaan (PPDB)', path: '/sma/ppdb' },
  { label: 'Kontak', path: '/sma/kontak' },
]

function isActive(item) {
  if (item.path === '/sma') return route.path === '/sma'
  return route.path.startsWith(item.path)
}
</script>