<template>
  <div class="min-h-screen bg-surface font-body-md text-body-md text-on-surface antialiased overflow-x-clip">
    <AmbientCursorLight />
    <ScrollProgress />
    <header
      class="glass-header fixed top-0 w-full z-50"
      :class="{ 'is-scrolled': scrolled }"
    >
      <div class="h-20 w-full px-gutter-desktop mx-auto flex items-center justify-between gap-space-md">
        <div class="flex items-center gap-3 min-w-0">
          <router-link to="/" class="flex items-center gap-3 min-w-0" :title="brand.short">
            <AppLogo :src="logo" :size="34" shape="md" alt="Logo LPI Dayah Madani Al-Aziziyah" />
            <div class="flex flex-col min-w-0">
              <span class="font-headline-sm text-headline-sm text-on-surface tracking-tight uppercase leading-none truncate">{{ brand.short }}</span>
              <span class="hidden sm:block font-label-sm text-label-sm text-primary tracking-widest uppercase mt-0.5 truncate">Lembaga Pendidikan Islam</span>
            </div>
          </router-link>
        </div>

        <nav class="hidden xl:flex items-center gap-space-lg" aria-label="Navigasi utama">
          <template v-for="item in navigation" :key="item.name">
            <router-link v-if="!item.children" :to="item.href" class="lpi-link" :class="isActive(item.href) ? 'text-primary font-title-md' : ''">
              {{ item.name }}
            </router-link>
            <div v-else class="relative" @mouseenter="openDesktop = item.name" @mouseleave="openDesktop = null" @focusin="openDesktop = item.name">
              <button
                type="button"
                class="lpi-link flex items-center gap-1"
                :class="isActiveGroup(item) ? 'text-primary font-title-md' : ''"
                :aria-haspopup="true"
                :aria-expanded="openDesktop === item.name"
                @click="openDesktop = openDesktop === item.name ? null : item.name"
                @keydown.escape="openDesktop = null"
              >
                {{ item.name }}
                <span class="material-symbols-outlined text-[16px]" :class="openDesktop === item.name ? 'rotate-180' : ''">expand_more</span>
              </button>
              <Transition name="menu">
                <div v-if="openDesktop === item.name" class="absolute left-1/2 -translate-x-1/2 top-full pt-3 w-72 max-w-[calc(100vw-2rem)]">
                  <div class="glass-panel glass-sheen rounded-2xl p-2 flex flex-col gap-1">
                    <template v-for="child in item.children" :key="child.name">
                      <router-link
                        v-if="child.href"
                        :to="child.hash ? { path: child.href, hash: child.hash } : child.href"
                        class="flex items-center px-3 py-2.5 rounded-xl text-left transition-colors hover:bg-surface-container-low/80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"
                        :class="isActive(child.href) ? 'text-primary font-title-md font-bold' : 'text-on-surface font-body-md'"
                        @click="openDesktop = null"
                      >
                        {{ child.name }}
                      </router-link>
                      <span v-else aria-disabled="true" class="flex items-center gap-2 px-3 py-2.5 rounded-xl opacity-60 cursor-not-allowed">
                        <span class="font-body-md text-on-surface-variant">{{ child.name }}</span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant">Segera hadir</span>
                      </span>
                    </template>
                  </div>
                </div>
              </Transition>
            </div>
          </template>
        </nav>

        <div class="flex items-center gap-2 sm:gap-space-md shrink-0">
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
            class="btn-skeu hidden sm:inline-flex xl:hidden items-center h-11 px-4 rounded-full bg-primary text-on-primary font-label-sm text-label-sm font-bold uppercase tracking-wide whitespace-nowrap shrink-0"
          >
            PPDB
          </router-link>
          <button
            type="button"
            class="control-glass xl:hidden h-11 w-11 shrink-0 rounded-full inline-flex items-center justify-center"
            @click="menuOpen = !menuOpen"
            aria-label="Menu"
            :aria-expanded="menuOpen"
            aria-controls="lpi-mobile-nav"
          >
            <span class="material-symbols-outlined !w-6 !h-6 !text-[24px]">{{ menuOpen ? 'close' : 'menu' }}</span>
          </button>
        </div>
      </div>

      <Transition name="menu">
        <nav
          id="lpi-mobile-nav"
          v-if="menuOpen"
          class="glass-panel xl:hidden border-t border-outline-variant px-gutter-desktop flex flex-col divide-y divide-outline-variant/60 max-h-[calc(100dvh-5rem)] overflow-y-auto overscroll-contain pb-[max(1.25rem,env(safe-area-inset-bottom))]"
          aria-label="Navigasi seluler"
        >
          <router-link
            to="/sma/ppdb"
            class="mt-3 mb-1 inline-flex items-center justify-center gap-2 h-11 rounded-full bg-primary text-on-primary font-title-md text-title-md uppercase tracking-wide shrink-0"
            @click="menuOpen = false"
          >
            <span class="material-symbols-outlined text-[20px]">how_to_reg</span>
            Pendaftaran PPDB
          </router-link>
          <template v-for="item in navigation" :key="'m-' + item.name">
            <router-link v-if="!item.children" :to="item.href" class="min-h-11 flex items-center py-2 font-body-md" :class="isActive(item.href) ? 'text-primary font-semibold' : ''" @click="menuOpen = false">{{ item.name }}</router-link>
            <div v-else class="flex flex-col py-1">
              <button
                type="button"
                class="min-h-11 py-2 font-body-md flex items-center justify-between gap-2 text-left w-full"
                :aria-expanded="mobileOpen === item.name"
                :aria-controls="'m-sub-' + item.name"
                @click="mobileOpen = mobileOpen === item.name ? null : item.name"
              >
                <span :class="isActiveGroup(item) ? 'text-primary font-semibold' : ''">{{ item.name }}</span>
                <span class="material-symbols-outlined text-[18px] shrink-0" aria-hidden="true">{{ mobileOpen === item.name ? 'expand_less' : 'expand_more' }}</span>
              </button>
              <div :id="'m-sub-' + item.name" v-show="mobileOpen === item.name" class="flex flex-col pl-3 ml-3 border-l border-outline-variant">
                <router-link v-if="item.href" :to="item.href" class="min-h-11 flex items-center py-2 pl-3 font-body-sm" :class="isActive(item.href) ? 'text-primary font-semibold' : 'text-on-surface-variant'" @click="menuOpen = false">Semua {{ item.name }}</router-link>
                <template v-for="child in item.children" :key="'m-sub-' + item.name + '-' + child.name">
                  <router-link v-if="child.href" :to="child.hash ? { path: child.href, hash: child.hash } : child.href" class="min-h-11 flex items-center py-2 pl-3 font-body-sm" :class="isActive(child.href) ? 'text-primary font-semibold' : 'text-on-surface-variant'" @click="menuOpen = false">{{ child.name }}</router-link>
                  <span v-else aria-disabled="true" class="min-h-11 flex items-center gap-2 py-2 pl-3 font-body-sm text-on-surface-variant/60"><span>{{ child.name }}</span><span class="font-label-sm text-label-sm">Segera hadir</span></span>
                </template>
              </div>
            </div>
          </template>
        </nav>
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
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppLogo from './AppLogo.vue'
import AmbientCursorLight from './AmbientCursorLight.vue'
import ScrollProgress from './ScrollProgress.vue'
import ThemeToggle from './ThemeToggle.vue'
import { brand, footerLinks, navigation } from '../data/lpi.js'
import { useSiteSettings } from '../composables/useSiteSettings.js'

const route = useRoute()
const { get, asset, load } = useSiteSettings()
const logo = ref(null)

const menuOpen = ref(false)
const openDesktop = ref(null)
const mobileOpen = ref(null)

const scrolled = ref(false)
let scrollFrame = null

function applyScrolled() {
  scrollFrame = null
  scrolled.value = window.scrollY > 12
}

function onScroll() {
  if (scrollFrame == null) scrollFrame = requestAnimationFrame(applyScrolled)
}

function onKeydown(event) {
  if (event.key === 'Escape') {
    menuOpen.value = false
    openDesktop.value = null
    mobileOpen.value = null
  }
}

watch(
  () => route.path,
  () => {
    menuOpen.value = false
    openDesktop.value = null
    mobileOpen.value = null
  },
)

onMounted(async () => {
  applyScrolled()
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('keydown', onKeydown)
  await load()
  logo.value = asset(get('logo_path', null))
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('keydown', onKeydown)
  if (scrollFrame != null) cancelAnimationFrame(scrollFrame)
})

function isActive(path) {
  if (!path) return false
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}

function isActiveGroup(item) {
  if (isActive(item.href)) return true
  return (item.children || []).some((child) => isActive(child.href))
}
</script>

<style scoped>
.lpi-link {
  position: relative;
  color: var(--color-on-surface-variant);
  transition: color 0.25s var(--ease-out-soft);
}
.lpi-link::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  bottom: -6px;
  height: 2px;
  border-radius: 9999px;
  background: linear-gradient(90deg, var(--color-primary), var(--color-secondary-container));
  transform: scaleX(0);
  transform-origin: 0 50%;
  transition: transform 0.35s var(--ease-out-soft);
}
.lpi-link:hover {
  color: var(--color-on-surface);
}
.lpi-link:hover::after {
  transform: scaleX(1);
}
.lpi-link:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 4px;
  border-radius: 6px;
}
</style>
