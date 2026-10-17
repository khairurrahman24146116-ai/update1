<template>
  <div class="min-h-screen bg-surface font-body-md text-body-md text-on-surface">
    <nav class="nav-glass">
      <div class="max-w-7xl mx-auto px-gutter-mobile md:px-gutter-desktop h-16 flex items-center justify-between gap-space-md">
        <router-link :to="homePath" class="flex items-center gap-space-md min-w-0" aria-label="Beranda Portal">
          <AppLogo :src="logo" :size="34" shape="md" alt="Logo SMA Madani Al Aziziyah" />
          <span class="font-headline-sm text-headline-sm text-on-surface tracking-tight uppercase leading-none truncate">
            {{ brand }}
          </span>
        </router-link>
      <div class="flex items-center gap-space-sm shrink-0">
        <ThemeToggle />
        <span class="badge bg-surface-container-low text-on-surface-variant capitalize">{{ roleLabel }}</span>
          <button type="button" class="btn btn--ghost btn--sm" @click="logout">
            <span class="material-symbols-outlined !w-4 !h-4">logout</span>
            Keluar
          </button>
        </div>
      </div>
    </nav>
    <main class="max-w-7xl mx-auto px-gutter-mobile md:px-gutter-desktop py-space-lg space-y-6">
      <h1 v-if="title" class="font-headline-lg text-headline-lg text-on-surface tracking-tight stagger-item">{{ title }}</h1>
      <slot />
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'
import { LOGIN_PATH } from '../site-paths.js'
import { useSiteSettings } from '../composables/useSiteSettings'
import AppLogo from './AppLogo.vue'
import ThemeToggle from './ThemeToggle.vue'

defineProps({ title: { type: String, default: '' } })
const router = useRouter()
const { user, doLogout } = useAuth()
const { get, asset, load } = useSiteSettings()

const fallbackBrand = 'SMA MADANI AL AZIZIYAH'
const brand = computed(() => get('app_name', fallbackBrand) || fallbackBrand)
const logo = computed(() => asset(get('logo_path', null)))

onMounted(() => { load() })

const roleLabel = computed(() => {
  const map = { admin: 'Admin', guru: 'Guru', bendahara: 'Bendahara', wali_murid: 'Wali Murid' }
  return map[user.value?.role] || 'Pengguna'
})

const homePath = computed(() => {
  const map = { admin: '/app/admin', guru: '/app/guru', bendahara: '/app/bendahara', wali_murid: '/app/wali-murid' }
  return map[user.value?.role] || '/'
})

function logout() {
  doLogout()
  router.push(LOGIN_PATH)
}
</script>
