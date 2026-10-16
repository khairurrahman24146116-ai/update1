<template>
  <div class="min-h-screen bg-surface font-body-md text-body-md text-on-surface antialiased overflow-x-hidden">
    <header class="fixed top-0 w-full z-50 bg-white/85 backdrop-blur-xl border-b border-outline-variant shadow-sm">
      <div class="h-20 w-full px-gutter-desktop mx-auto flex items-center justify-between gap-space-md">
        <div class="flex items-center gap-space-md shrink-0">
          <router-link to="/" class="flex items-center gap-space-md">
            <AppLogo src="" :size="34" shape="md" alt="Logo LPI Dayah Madani Al-Aziziyah" />
            <div class="flex flex-col">
              <span class="font-headline-sm text-headline-sm text-on-surface tracking-tight uppercase leading-none">{{ brand.short }}</span>
              <span class="font-label-sm text-label-sm text-primary tracking-widest uppercase mt-0.5">Lembaga Pendidikan Islam</span>
            </div>
          </router-link>
        </div>
        <nav class="hidden xl:flex items-center gap-space-lg">
          <router-link to="/" class="lpi-link" :class="isActive('/') ? 'text-primary font-title-md' : ''">Beranda</router-link>
          <div class="relative" @mouseenter="tentangOpen = true" @mouseleave="tentangOpen = false">
            <router-link to="/tentang" class="lpi-link flex items-center gap-1" :class="isActive('/tentang') ? 'text-primary font-title-md' : ''" aria-haspopup="true" :aria-expanded="tentangOpen">
              Tentang
              <span class="material-symbols-outlined text-[16px]">expand_more</span>
            </router-link>
            <Transition name="menu">
              <div v-if="tentangOpen" class="absolute left-0 top-full pt-2 w-64">
                <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-md p-2 flex flex-col gap-1">
                  <router-link v-for="t in tentangMenu" :key="t.key" :to="{ path: '/tentang', hash: t.hash }" class="flex items-center gap-2 p-2 rounded-xl hover:bg-surface-container-low transition-colors" @click="tentangOpen = false">
                    <span class="material-symbols-outlined text-primary text-[20px] shrink-0">{{ t.icon }}</span>
                    <span class="font-title-md text-title-md">{{ t.label }}</span>
                  </router-link>
                </div>
              </div>
            </Transition>
          </div>
          <div class="relative" @mouseenter="progOpen = true" @mouseleave="progOpen = false">
            <button @click="progOpen = !progOpen" class="lpi-link flex items-center gap-1" :class="isActive('/program') ? 'text-primary font-title-md' : ''" aria-haspopup="true" :aria-expanded="progOpen">
              Program
              <span class="material-symbols-outlined text-[16px]">expand_more</span>
            </button>
            <Transition name="menu">
              <div v-if="progOpen" class="absolute left-0 top-full pt-2 w-72">
                <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-md p-2 flex flex-col gap-1">
                  <template v-for="p in programs" :key="p.key">
                    <router-link v-if="p.active" :to="p.href" class="flex items-start gap-2 p-2 rounded-xl transition-colors hover:bg-surface-container-low">
                      <span class="material-symbols-outlined text-primary text-[20px] shrink-0">{{ p.icon }}</span>
                      <span>
                        <span class="font-title-md text-title-md block">{{ p.name }}</span>
                        <span class="font-body-sm text-body-sm text-on-surface-variant block">{{ p.desc }}</span>
                      </span>
                    </router-link>
                    <span v-else aria-disabled="true" class="flex items-start gap-2 p-2 rounded-xl transition-colors opacity-60 cursor-not-allowed">
                      <span class="material-symbols-outlined text-primary text-[20px] shrink-0">{{ p.icon }}</span>
                      <span>
                        <span class="font-title-md text-title-md block">{{ p.name }}</span>
                        <span class="font-body-sm text-body-sm text-on-surface-variant block">Segera hadir</span>
                      </span>
                    </span>
                  </template>
                </div>
              </div>
            </Transition>
          </div>
          <router-link to="/layanan" class="lpi-link" :class="isActive('/layanan') ? 'text-primary font-title-md' : ''">Layanan</router-link>
        </nav>
        <div class="flex items-center gap-space-md">
          <button class="xl:hidden w-9 h-9 rounded-full border border-outline-variant bg-white shadow-sm flex items-center justify-center" @click="menuOpen = !menuOpen" aria-label="Menu" :aria-expanded="menuOpen">
            <span class="material-symbols-outlined !w-5 !h-5 !text-[22px]">{{ menuOpen ? 'close' : 'menu' }}</span>
          </button>
        </div>
      </div>
      <Transition name="menu">
        <div v-if="menuOpen" class="xl:hidden bg-surface-container-lowest border-t border-outline-variant px-gutter-desktop py-space-sm flex flex-col gap-1">
          <router-link to="/" class="py-2 font-body-md" @click="menuOpen = false">Beranda</router-link>
          <button @click="tentangAccordion = !tentangAccordion" class="py-2 font-body-md flex items-center justify-between text-left" :aria-expanded="tentangAccordion">
            Tentang
            <span class="material-symbols-outlined text-[18px]">{{ tentangAccordion ? 'expand_less' : 'expand_more' }}</span>
          </button>
          <div v-if="tentangAccordion" class="flex flex-col gap-1 pl-3 border-l-2 border-outline-variant ml-1">
            <router-link to="/tentang" class="py-2 font-body-sm" @click="menuOpen = false">Semua Tentang</router-link>
            <router-link v-for="t in tentangMenu" :key="'m-' + t.key" :to="{ path: '/tentang', hash: t.hash }" class="py-2 font-body-sm" @click="menuOpen = false">{{ t.label }}</router-link>
          </div>
          <button @click="progAccordion = !progAccordion" class="py-2 font-body-md flex items-center justify-between text-left" :aria-expanded="progAccordion">
            Program
            <span class="material-symbols-outlined text-[18px]">{{ progAccordion ? 'expand_less' : 'expand_more' }}</span>
          </button>
          <div v-if="progAccordion" class="flex flex-col gap-1 pl-3 border-l-2 border-outline-variant ml-1">
            <template v-for="p in programs" :key="'m-' + p.key">
              <router-link v-if="p.active" :to="p.href" class="py-2 font-body-sm" @click="menuOpen = false">{{ p.name }} — Kunjungi</router-link>
              <span v-else aria-disabled="true" class="py-2 font-body-sm opacity-60">{{ p.name }} — Segera hadir</span>
            </template>
          </div>
          <router-link to="/layanan" class="py-2 font-body-md" @click="menuOpen = false">Layanan</router-link>
        </div>
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
          <nav class="flex flex-wrap gap-x-4 gap-y-1 font-body-sm text-body-sm">
            <router-link to="/" class="hover:text-primary">Beranda</router-link>
            <router-link to="/tentang" class="hover:text-primary">Tentang</router-link>
            <router-link to="/program" class="hover:text-primary">Program</router-link>
            <router-link to="/layanan" class="hover:text-primary">Layanan</router-link>
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
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import AppLogo from './AppLogo.vue'
import { brand, programs, tentangMenu } from '../data/lpi.js'

const route = useRoute()
const menuOpen = ref(false)
const progOpen = ref(false)
const progAccordion = ref(false)
const tentangOpen = ref(false)
const tentangAccordion = ref(false)

function isActive(path) {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}
</script>

<style scoped>
.lpi-link {
  color: var(--color-on-surface-variant);
}
.lpi-link:hover {
  color: var(--color-on-surface);
}
</style>
