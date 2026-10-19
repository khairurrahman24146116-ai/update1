<template>
<div class="min-h-screen bg-surface font-body text-on-surface antialiased flex items-center justify-center p-4 md:p-6 relative overflow-hidden">
      <div class="pointer-events-none absolute -top-24 -left-24 w-96 h-96 bg-secondary-container/25 rounded-full blur-3xl"></div>
      <div class="pointer-events-none absolute -bottom-24 -right-24 w-80 h-80 bg-primary-container/10 rounded-full blur-3xl"></div>

<main class="w-full max-w-[30rem] relative">
<div v-if="expiredParam" class="px-4 py-2.5 bg-error-container border border-error rounded-full text-xs font-semibold text-on-error-container text-center mb-3">Sesi login telah berakhir. Silakan login kembali.</div>
<div class="flex items-center justify-between px-1 mb-4">
<div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-on-surface font-code text-[11px] font-semibold shadow-sm border border-outline-variant">
<span class="w-2 h-2 rounded-full bg-primary-container animate-pulse"></span>
Secure Auth Layer
</div>
<ThemeToggle />
</div>

<div class="w-full bg-surface-container-lowest rounded-[28px] border border-outline-variant shadow-card p-6 md:p-8 flex flex-col gap-5 relative overflow-hidden">
<div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-primary-container via-secondary-container to-tertiary-fixed"></div>

<div class="flex flex-col items-center text-center gap-3 pt-1">
<div class="relative group">
            <div class="w-20 h-20 rounded-full logo-plate p-2.5 group-hover:-translate-y-0.5 transition-transform duration-200">
              <img :src="logo" :alt="`Logo ${brandName}`" width="80" height="80" class="h-full w-full object-contain"/>
            </div>
</div>
<div>
<h1 class="font-headline text-lg font-extrabold text-on-surface tracking-tight uppercase leading-none">{{ brandName }}</h1>
<p class="text-sm text-on-surface-variant mt-1">Sistem Informasi Akademik & Pembelajaran Terpadu</p>
</div>
</div>

<!-- Auth error popup (gaya mengikuti banner sistem) -->
<Transition name="fade">
  <div
    v-if="authError"
    :key="attemptKey"
    class="flex items-start gap-2.5 px-4 py-3 border rounded-2xl text-sm shake"
    :class="errorMeta.wrap"
    role="alert"
    aria-live="assertive"
  >
    <span class="material-symbols-outlined text-[20px] shrink-0 mt-0.5">{{ errorMeta.icon }}</span>
    <div class="min-w-0">
      <p class="font-semibold leading-snug">{{ authError }}</p>
      <p v-if="errorMeta.hint" class="text-xs opacity-80 mt-0.5 leading-snug">{{ errorMeta.hint }}</p>
    </div>
  </div>
</Transition>

<form @submit.prevent="submit" novalidate class="flex flex-col gap-4">
<!-- Identity Input -->
<div class="flex flex-col gap-1.5">
<div class="flex justify-between items-center">
<label class="font-code text-[11px] font-semibold text-on-surface uppercase tracking-wide">Email / NISN (Wali Murid)</label>
<span class="font-code text-[11px] text-primary font-bold">[Wajib]</span>
</div>
<div class="relative">
<input
  v-model="form.identity"
  type="text"
  :class="fieldErrors.identity ? 'border-error focus:border-error focus:ring-error/10' : 'border-outline-variant focus:border-primary-container focus:ring-primary-container/10'"
  class="w-full h-12 px-4 pl-11 rounded-md bg-white border font-body text-sm text-on-surface placeholder:text-on-surface-variant/70 focus:bg-white focus:ring-4 focus:outline-none shadow-inset transition-colors"
  placeholder="admin@madani.test atau NISN wali"
  autocomplete="username"
  @input="onFieldInput('identity')"
/>
<span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px] pointer-events-none z-10 flex items-center justify-center w-6 h-6">mail</span>
</div>
<p v-if="fieldErrors.identity" class="font-code text-[11px] text-error flex items-center gap-1">
<span class="material-symbols-outlined text-[14px]">error</span>{{ fieldErrors.identity }}
</p>
</div>

<!-- Password -->
<div class="flex flex-col gap-1.5">
<div class="flex justify-between items-center">
<label class="font-code text-[11px] font-semibold text-on-surface uppercase tracking-wide">Kata Sandi</label>
<a href="#" @click.prevent="openRecovery" class="font-code text-[11px] text-primary hover:underline font-bold">Lupa Sandi?</a>
</div>
<div class="relative">
<input
  v-model="form.password"
  :type="showPwd?'text':'password'"
  :class="fieldErrors.password ? 'border-error focus:border-error focus:ring-error/10' : 'border-outline-variant focus:border-primary-container focus:ring-primary-container/10'"
  class="w-full h-12 px-4 pl-11 pr-11 rounded-md bg-white border font-body text-sm text-on-surface placeholder:text-on-surface-variant/70 focus:bg-white focus:ring-4 focus:outline-none shadow-inset transition-colors"
  placeholder="••••••••"
  autocomplete="current-password"
  @input="onFieldInput('password')"
/>
<span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px] pointer-events-none z-10 flex items-center justify-center w-6 h-6">lock</span>
<button type="button" @click="showPwd=!showPwd" class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface p-1 flex items-center justify-center w-7 h-7 rounded-full" aria-label="Tampilkan sandi">
<span class="material-symbols-outlined text-[20px] flex items-center justify-center w-6 h-6">{{ showPwd?'visibility_off':'visibility' }}</span>
</button>
</div>
<p v-if="fieldErrors.password" class="font-code text-[11px] text-error flex items-center gap-1">
<span class="material-symbols-outlined text-[14px]">error</span>{{ fieldErrors.password }}
</p>
</div>

<!-- Remember + SSL -->
<div class="flex items-center justify-between pt-1">
<label class="flex items-center gap-2 cursor-pointer select-none">
<input v-model="form.remember" type="checkbox" class="w-4 h-4 rounded border border-outline-variant accent-primary cursor-pointer"/>
<span class="text-sm text-on-surface">Ingat sesi di perangkat ini</span>
</label>
<span class="font-code text-[11px] text-on-surface-variant flex items-center gap-0.5">
<span class="material-symbols-outlined text-[14px] text-primary flex items-center justify-center w-5 h-5">verified_user</span> SSL v3
</span>
</div>

  <!-- CTA -->
<button type="submit" :disabled="loading" class="w-full mt-1 h-12 bg-primary text-on-primary font-headline text-base font-semibold rounded-full shadow-sm hover:bg-primary-hover hover:-translate-y-0.5 hover:shadow-md active:translate-y-0 active:shadow-none transition-[transform,box-shadow,background-color,opacity] flex items-center justify-center gap-2 whitespace-nowrap disabled:opacity-50 disabled:hover:translate-y-0 disabled:hover:shadow-sm">
<span class="truncate">{{ loading ? 'Memproses…' : 'Masuk ke Portal' }}</span>
<span class="material-symbols-outlined text-[20px] flex items-center justify-center w-6 h-6 shrink-0">arrow_forward</span>
</button>
</form>

<!-- Footer meta -->
<div class="flex items-center justify-end pt-3 border-t border-dashed border-outline-variant/60 text-on-surface-variant font-code text-[11px]">
<div class="flex items-center gap-2">
<span class="text-primary font-bold">PORTAL MADANI</span>
</div>
</div>
</div>

<!-- Bottom links -->
<div class="w-full mt-4 flex flex-col items-center gap-2.5 px-1">
<p class="font-code text-[11px] text-on-surface-variant text-center opacity-80">© {{ currentYear }} {{ brandName }}. Seluruh Hak Cipta Dilindungi.</p>
<router-link to="/" class="text-xs text-primary hover:underline">← Kembali ke Beranda</router-link>
</div>
</main>

<div v-if="showRecovery" class="fixed inset-0 bg-inverse-surface/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="closeRecovery">
<div class="bg-white max-w-md w-full rounded-[28px] border border-outline-variant shadow-card p-6 space-y-4">
<div class="flex items-center justify-between pb-2 border-b border-outline-variant">
<h3 class="font-bold flex items-center gap-2 text-on-surface"><span class="material-symbols-outlined text-[20px] text-primary">support_agent</span>Pusat Pemulihan Akun</h3>
<button @click="closeRecovery" class="w-8 h-8 rounded-full text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low flex items-center justify-center" aria-label="Tutup">✕</button>
</div>
<p class="text-sm text-on-surface-variant">Masukkan NISN, NUPTK, atau No. HP terdaftar. Tautan pemulihan akan dikirim otomatis.</p>
<label class="block text-xs font-bold uppercase tracking-wide">ID Pengguna / Nomor Ponsel
  <input
    v-model="recoveryId"
    :class="recoveryError ? 'border-error' : 'border-outline-variant focus:border-primary-container focus:ring-primary-container/10'"
    class="mt-1.5 w-full bg-surface-container-low px-4 py-3 rounded-md text-sm border focus:outline-none focus:ring-4"
    placeholder="0078129482 / 08123456789"
    @input="recoveryError=''; recoveryInfo=''"
  />
</label>
<div v-if="recoveryError" class="flex items-center gap-1.5 text-xs text-error">
  <span class="material-symbols-outlined text-[16px]">error</span>{{ recoveryError }}
</div>
<div v-else-if="recoveryInfo" class="flex items-start gap-1.5 text-xs text-success bg-success-bg rounded-lg px-3 py-2">
  <span class="material-symbols-outlined text-[16px]">check_circle</span><span>{{ recoveryInfo }}</span>
</div>
<button @click="doRecovery('WhatsApp')" class="w-full bg-secondary text-on-secondary py-3 rounded-full text-sm font-semibold flex items-center justify-center gap-2 shadow-sm hover:bg-secondary-hover">Kirim via WhatsApp</button>
<button @click="doRecovery('Email')" class="w-full bg-surface-container-low py-3 rounded-full text-sm font-semibold hover:bg-surface-container transition-colors">Kirim via Email</button>
<button @click="closeRecovery" class="w-full text-xs text-on-surface-variant hover:underline">Tutup Jendela</button>
</div>
</div>
</div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'
import { brand } from '../data/lpi.js'
import { useSiteSettings } from '../composables/useSiteSettings'
import ThemeToggle from '../Components/ThemeToggle.vue'
const router = useRouter()
const { loading, error: authError, errorCode: authErrorCode, doLogin } = useAuth()
const { get, asset, load } = useSiteSettings()
load()
const logo = computed(() => asset(get('logo_path', null)) || '/images/profile/logo-lpi-transparent.png')
const brandName = brand.name
const showPwd = ref(false)
const showRecovery = ref(false)
const recoveryId = ref('')
const recoveryError = ref('')
const recoveryInfo = ref('')
const expiredParam = new URLSearchParams(location.search).get('expired') === '1'
const currentYear = new Date().getFullYear()
const urlParams = new URLSearchParams(location.search)
urlParams.delete('expired')
const cleanUrl = urlParams.toString() ? location.pathname + '?' + urlParams.toString() : location.pathname
const form = ref({ identity: '', password: '', remember: true })
const fieldErrors = ref({ identity: '', password: '' })
const attemptKey = ref(0)
const map = { admin: '/app/admin', guru: '/app/guru', bendahara: '/app/bendahara', bendahara_lpi: '/app/bendahara', bendahara_sma: '/app/bendahara', wali_murid: '/app/wali-murid' }

const errorMeta = computed(() => {
  switch (authErrorCode.value) {
    case 'account_not_found':
      return { icon: 'person_off', wrap: 'bg-error-container border-error text-on-error-container', hint: 'Periksa kembali email/NISN, atau hubungi admin untuk pendaftaran akun.' }
    case 'invalid_credentials':
      return { icon: 'lock_reset', wrap: 'bg-error-container border-error text-on-error-container', hint: 'Email/NISN atau kata sandi tidak cocok. Coba lagi atau gunakan Lupa Sandi.' }
    case 'account_inactive':
      return { icon: 'block', wrap: 'bg-warning-bg border-warning text-on-surface', hint: 'Hubungi administrator untuk mengaktifkan kembali akun Anda.' }
    default:
      return { icon: 'error', wrap: 'bg-error-container border-error text-on-error-container', hint: '' }
  }
})

function onFieldInput(field) {
  fieldErrors.value[field] = ''
  authError.value = ''
}

function validate() {
  fieldErrors.value = { identity: '', password: '' }
  let ok = true
  if (!form.value.identity.trim()) {
    fieldErrors.value.identity = 'Email atau NISN wajib diisi.'
    ok = false
  }
  if (!form.value.password) {
    fieldErrors.value.password = 'Kata sandi wajib diisi.'
    ok = false
  }
  return ok
}

async function submit() {
  authError.value = ''
  authErrorCode.value = ''
  if (!validate()) return
  attemptKey.value++
  try {
    const res = await doLogin(form.value.identity.trim(), form.value.password)
    router.push(map[res.user.role] || '/')
  } catch { /* pesan ditampilkan lewat banner authError */ }
}

function openRecovery() {
  recoveryError.value = ''
  recoveryInfo.value = ''
  showRecovery.value = true
}

function closeRecovery() {
  showRecovery.value = false
}

function doRecovery(ch) {
  recoveryError.value = ''
  recoveryInfo.value = ''
  if (!recoveryId.value.trim()) {
    recoveryError.value = 'Harap masukkan ID pengguna atau nomor ponsel terlebih dahulu.'
    return
  }
  recoveryInfo.value = `Permintaan pemulihan via ${ch} telah dibuat untuk ${recoveryId.value.trim()}. Silakan periksa pesan masuk Anda.`
  recoveryId.value = ''
}
</script>
<style scoped>
.shake {
  animation: madani-shake 0.4s ease-in-out;
}
@keyframes madani-shake {
  0%, 100% { transform: translateX(0); }
  20% { transform: translateX(-6px); }
  40% { transform: translateX(6px); }
  60% { transform: translateX(-4px); }
  80% { transform: translateX(4px); }
}
</style>
