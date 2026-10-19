<template>
  <div class="min-h-screen bg-surface font-body-md text-body-md text-on-surface antialiased overflow-x-hidden">
    <AmbientCursorLight />
    <ScrollProgress />
    <header
      class="glass-header fixed top-0 w-full z-50"
      :class="{ 'is-scrolled': scrolled }"
    >
      <div class="h-20 w-full px-gutter-desktop mx-auto flex items-center justify-between gap-space-md">
        <div class="flex items-center gap-space-md shrink-0">
          <router-link to="/" class="flex items-center gap-space-md">
            <AppLogo :src="logo" :size="34" shape="md" alt="Logo LPI Dayah Madani Al-Aziziyah" />
            <div class="flex flex-col">
              <span class="font-headline-sm text-headline-sm text-on-surface tracking-tight uppercase leading-none">{{ brand.short }}</span>
              <span class="font-label-sm text-label-sm text-primary tracking-widest uppercase mt-0.5">Lembaga Pendidikan Islam</span>
            </div>
          </router-link>
        </div>

        <NavMenu :items="navigation" variant="desktop" id-prefix="lpi" />

        <div class="flex items-center gap-space-md">
          <ThemeToggle />
          <router-link
            to="/sma/ppdb"
            class="btn-skeu hidden xl:inline-flex items-center gap-2 px-5 h-11 rounded-full bg-primary text-on-primary font-title-md text-title-md uppercase tracking-wide whitespace-nowrap"
          >
            <span class="material-symbols-outlined text-[20px]">how_to_reg</span>
            Pendaftaran PPDB
          </router-link>
          <router-link
            to="/sma/ppdb"
            class="btn-skeu xl:hidden inline-flex items-center h-9 px-3 rounded-full bg-primary text-on-primary font-label-sm text-label-sm font-bold uppercase tracking-wide"
          >
            PPDB
          </router-link>
          <button
            class="control-glass xl:hidden w-9 h-9 rounded-full flex items-center justify-center"
            @click="menuOpen = !menuOpen"
            aria-label="Menu"
            :aria-expanded="menuOpen"
            aria-controls="lpi-mobile-nav"
          >
            <span class="material-symbols-outlined !w-5 !h-5 !text-[22px]">{{ menuOpen ? 'close' : 'menu' }}</span>
          </button>
        </div>
      </div>

      <Transition name="menu">
        <NavMenu
          v-if="menuOpen"
          :items="navigation"
          variant="mobile"
          id-prefix="lpi"
          class="glass-panel xl:hidden border-t border-outline-variant px-gutter-desktop py-space-sm"
          @navigate="menuOpen = false"
        >
          <template #footer>
            <router-link
              to="/sma/ppdb"
              class="mt-2 inline-flex items-center justify-center gap-2 h-11 rounded-full bg-primary text-on-primary font-title-md text-title-md uppercase tracking-wide"
              @click="menuOpen = false"
            >
              <span class="material-symbols-outlined text-[20px]">how_to_reg</span>
              Pendaftaran PPDB
            </router-link>
          </template>
        </NavMenu>
      </Transition>
    </header>

    <main class="w-full pt-20 bg-surface min-h-screen">
      <slot />
    </main>

    <footer class="w-full bg-surface-container-low border-t border-outline-variant pt-space-xl pb-space-lg">
      <div class="w-full px-gutter-desktop mx-auto">
        <div class="flex flex-col gap-space-sm pb-space-md border-b border-outline-variant">
          <span class="font-headline-sm text-headline-sm font-semibold">{{ brand.name }}</span>
          <p class="font-body-sm text-body-sm text-on-surface-variant">{{ brand.tagline }}</p>
          <nav class="flex flex-wrap gap-x-4 gap-y-1 font-body-sm text-body-sm" aria-label="Navigasi footer">
            <router-link v-for="link in footerLinks" :key="link.name" :to="link.href" class="hover:text-primary">{{ link.name }}</router-link>
          </nav>
        </div>
        <div class="pt-space-md font-label-sm text-label-sm text-on-surface-variant">
          <p>© {{ new Date().getFullYear() }} {{ brand.name }}.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import AppLogo from './AppLogo.vue'
import AmbientCursorLight from './AmbientCursorLight.vue'
import NavMenu from './NavMenu.vue'
import ScrollProgress from './ScrollProgress.vue'
import ThemeToggle from './ThemeToggle.vue'
import { brand, footerLinks, navigation } from '../data/lpi.js'
import { useSiteSettings } from '../composables/useSiteSettings.js'

const { get, asset, load } = useSiteSettings()
const logo = ref(null)

const menuOpen = ref(false)

const scrolled = ref(false)
let scrollFrame = null

function applyScrolled() {
  scrollFrame = null
  scrolled.value = window.scrollY > 12
}

function onScroll() {
  if (scrollFrame == null) scrollFrame = requestAnimationFrame(applyScrolled)
}

onMounted(async () => {
  applyScrolled()
  window.addEventListener('scroll', onScroll, { passive: true })
  await load()
  logo.value = asset(get('logo_path', null))
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  if (scrollFrame != null) cancelAnimationFrame(scrollFrame)
})
</script>

<style scoped>
</style>
