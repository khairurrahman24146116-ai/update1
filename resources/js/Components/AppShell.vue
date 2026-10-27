<template>
  <div class="min-h-screen bg-surface font-body-md text-body-md text-on-surface" :class="{ 'ui-square': squareShell }">
    <nav class="nav-glass">
      <div class="max-w-7xl mx-auto px-gutter-mobile md:px-gutter-desktop min-h-16 py-2 sm:h-16 sm:py-0 flex flex-wrap items-center justify-between gap-x-space-md gap-y-1">
        <router-link :to="homePath" class="flex items-center gap-space-md min-w-0 w-full sm:w-auto sm:flex-1" aria-label="Beranda Portal">
          <AppLogo :src="logo" :size="34" shape="md" :alt="`Logo ${brandName}`" />
          <span class="font-headline-sm text-on-surface tracking-tight uppercase leading-tight break-words line-clamp-2 min-w-0 text-[11px] sm:text-headline-sm sm:leading-none sm:truncate">
            {{ brandName }}
          </span>
        </router-link>
      <div class="flex items-center gap-space-sm shrink-0 w-full sm:w-auto justify-end">
        <router-link v-if="showAdminPanel" to="/app/admin" class="btn btn--ghost btn--sm hidden sm:inline-flex">
          <span class="material-symbols-outlined !w-4 !h-4">admin_panel_settings</span>
          Panel
        </router-link>
        <router-link v-if="showFinancePanel" to="/app/bendahara" class="btn btn--ghost btn--sm hidden sm:inline-flex">
          <span class="material-symbols-outlined !w-4 !h-4">account_balance_wallet</span>
          Keuangan
        </router-link>
        <ThemeToggle />
        <span class="badge bg-surface-container-low text-on-surface-variant capitalize whitespace-nowrap">{{ roleLabel }}</span>
          <button type="button" class="btn btn--ghost btn--sm" @click="logout">
            <span class="material-symbols-outlined !w-4 !h-4">logout</span>
            <span class="hidden sm:inline">Keluar</span>
          </button>
        </div>
      </div>
    </nav>
    <main class="max-w-7xl mx-auto px-gutter-mobile md:px-gutter-desktop py-space-lg space-y-6">
      <PermissionNoticeBanner
        :notices="notices"
        :denied="denied"
        @dismiss="dismissNotice"
        @dismiss-denied="dismissDenied"
      />
      <h1 v-if="title" class="font-headline-lg text-headline-lg text-on-surface tracking-tight stagger-item">{{ title }}</h1>
      <slot />
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, watchEffect } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'
import { usePermissionNotices } from '../composables/usePermissionNotices.js'
import { LOGIN_PATH } from '../site-paths.js'
import { brand } from '../data/lpi.js'
import { useSiteSettings } from '../composables/useSiteSettings'
import AppLogo from './AppLogo.vue'
import PermissionNoticeBanner from './PermissionNoticeBanner.vue'
import ThemeToggle from './ThemeToggle.vue'

const props = defineProps({ title: { type: String, default: '' } })
const route = useRoute()
const router = useRouter()
const { user, doLogout, isAdminAreaUser, isFinanceAreaUser, refreshUser } = useAuth()
const { notices, dismiss: dismissNotice } = usePermissionNotices(user)
const { get, asset, load } = useSiteSettings()
const denied = computed(() => route.query.denied === '1')

const brandName = brand.name
const logo = computed(() => asset(get('logo_path', null)))

watchEffect(() => {
  document.title = props.title ? `${props.title} | ${brandName}` : brandName
})

onMounted(() => { load(); refreshUser().catch(() => {}) })

const roleLabel = computed(() => {
  const map = { admin_utama: 'Admin Utama', admin: 'Admin', pending: 'Menunggu Hak Akses', operator: 'Operator', kepala_sekolah: 'Kepala Sekolah', guru: 'Guru', bendahara: 'Bendahara', bendahara_lpi: 'Bendahara LPI', bendahara_sma: 'Bendahara SMA', wali_murid: 'Wali Murid' }
  return map[user.value?.role] || 'Pengguna'
})

const squareShell = computed(() => ['admin', 'admin_utama', 'bendahara', 'bendahara_lpi', 'bendahara_sma'].includes(user.value?.role))

const showAdminPanel = computed(() => !['admin', 'admin_utama', 'operator', 'kepala_sekolah'].includes(user.value?.role) && isAdminAreaUser.value)
const showFinancePanel = computed(() => !['admin', 'admin_utama', 'bendahara_lpi', 'bendahara_sma'].includes(user.value?.role) && isFinanceAreaUser.value)

const homePath = computed(() => {
  const map = { admin_utama: '/app/admin', admin: '/app/admin', pending: '/app/pending', operator: '/app/operator/akun', kepala_sekolah: '/app/kepala/guru', guru: '/app/guru', bendahara: '/app/bendahara', bendahara_lpi: '/app/bendahara', bendahara_sma: '/app/bendahara', wali_murid: '/app/wali-murid' }
  return map[user.value?.role] || '/'
})

function logout() {
  doLogout()
  router.push(LOGIN_PATH)
}

function dismissDenied() {
  const query = { ...route.query }
  delete query.denied
  router.replace({ query })
}
</script>



