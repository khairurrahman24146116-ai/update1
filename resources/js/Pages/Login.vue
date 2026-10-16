<template>
<div class="min-h-screen bg-surface font-body text-on-surface antialiased flex items-center justify-center p-4 md:p-6 relative overflow-hidden">
<div class="pointer-events-none absolute -top-24 -left-24 w-96 h-96 bg-secondary-container/40 rounded-full blur-3xl"></div>
<div class="pointer-events-none absolute -bottom-24 -right-24 w-80 h-80 bg-primary-container/10 rounded-full blur-3xl"></div>

<main class="w-full max-w-[30rem] relative">
<div v-if="expiredParam" class="px-4 py-2.5 bg-error-container border border-error rounded-full text-xs font-semibold text-on-error-container text-center mb-3">Sesi login telah berakhir. Silakan login kembali.</div>
<div class="flex items-center justify-between px-1 mb-4">
<div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-on-surface font-code text-[11px] font-semibold shadow-sm border border-outline-variant">
<span class="w-2 h-2 rounded-full bg-primary-container animate-pulse"></span>
Secure Auth Layer
</div>
</div>

<div class="w-full bg-surface-container-lowest rounded-[28px] border border-outline-variant shadow-card p-6 md:p-8 flex flex-col gap-5 relative overflow-hidden">
<div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-primary-container via-secondary-container to-tertiary-fixed"></div>

<div class="flex flex-col items-center text-center gap-3 pt-1">
<div class="relative group">
<div class="w-20 h-20 rounded-full bg-surface-container-low border border-outline-variant shadow-sm p-1.5 flex items-center justify-center overflow-hidden transition-transform duration-200 group-hover:-translate-y-0.5">
<img :src="logo" alt="Logo SMA Madani" class="w-full h-full object-contain"/>
</div>
</div>
<div>
<h1 class="font-headline text-lg font-extrabold text-on-surface tracking-tight uppercase leading-none">{{ brand }}</h1>
<p class="text-sm text-on-surface-variant mt-1">Sistem Informasi Akademik & Pembelajaran Terpadu</p>
</div>
</div>

<form @submit.prevent="submit" class="flex flex-col gap-4">
<!-- Identity Input -->
<div class="flex flex-col gap-1.5">
<div class="flex justify-between items-center">
<label class="font-code text-[11px] font-semibold text-on-surface uppercase tracking-wide">Email / NISN (Wali Murid)</label>
<span class="font-code text-[11px] text-primary font-bold">[Wajib]</span>
</div>
<div class="relative">
<input v-model="form.identity" class="w-full h-12 px-4 pl-11 rounded-md bg-white border border-outline-variant font-body text-sm text-on-surface placeholder:text-on-surface-variant/70 focus:bg-white focus:border-primary-container focus:ring-4 focus:ring-primary-container/10 focus:outline-none shadow-inset transition-colors" placeholder="admin@madani.test atau NISN wali" required />
<span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px] pointer-events-none z-10 flex items-center justify-center w-6 h-6">mail</span>
</div>
</div>

<!-- Password -->
<div class="flex flex-col gap-1.5">
<div class="flex justify-between items-center">
<label class="font-code text-[11px] font-semibold text-on-surface uppercase tracking-wide">Kata Sandi</label>
<a href="#" @click.prevent="showRecovery=true" class="font-code text-[11px] text-primary hover:underline font-bold">Lupa Sandi?</a>
</div>
<div class="relative">
<input v-model="form.password" :type="showPwd?'text':'password'" class="w-full h-12 px-4 pl-11 pr-11 rounded-md bg-white border border-outline-variant font-body text-sm text-on-surface placeholder:text-on-surface-variant/70 focus:bg-white focus:border-primary-container focus:ring-4 focus:ring-primary-container/10 focus:outline-none shadow-inset transition-colors" placeholder="••••••••" required/>
<span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px] pointer-events-none z-10 flex items-center justify-center w-6 h-6">lock</span>
<button type="button" @click="showPwd=!showPwd" class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface p-1 flex items-center justify-center w-7 h-7 rounded-full" aria-label="Tampilkan sandi">
<span class="material-symbols-outlined text-[20px] flex items-center justify-center w-6 h-6">{{ showPwd?'visibility_off':'visibility' }}</span>
</button>
</div>
</div>

<!-- Remember + SSL -->
<div class="flex items-center justify-between pt-1">
<label class="flex items-center gap-2 cursor-pointer select-none">
<input v-model="form.remember" type="checkbox" checked class="w-4 h-4 rounded border border-outline-variant accent-primary cursor-pointer"/>
<span class="text-sm text-on-surface">Ingat sesi di perangkat ini</span>
</label>
<span class="font-code text-[11px] text-on-surface-variant flex items-center gap-0.5">
<span class="material-symbols-outlined text-[14px] text-primary flex items-center justify-center w-5 h-5">verified_user</span> SSL v3
</span>
</div>

  <!-- CTA -->
<button type="submit" :disabled="loading" class="w-full mt-1 h-12 bg-primary-container text-on-primary font-headline text-base font-semibold rounded-full shadow-sm hover:bg-primary hover:-translate-y-0.5 hover:shadow-md active:translate-y-0 active:shadow-none transition-[transform,box-shadow,background-color,opacity] flex items-center justify-center gap-2 whitespace-nowrap disabled:opacity-50 disabled:hover:translate-y-0 disabled:hover:shadow-sm">
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
<p class="font-code text-[11px] text-on-surface-variant text-center opacity-80">© {{ currentYear }} SMA Madani Al-Aziziyah. Seluruh Hak Cipta Dilindungi.</p>
<router-link to="/" class="text-xs text-primary hover:underline">← Kembali ke Beranda</router-link>
</div>
</main>

<div v-if="showRecovery" class="fixed inset-0 bg-inverse-surface/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="showRecovery=false">
<div class="bg-white max-w-md w-full rounded-[28px] border border-outline-variant shadow-card p-6 space-y-4">
<div class="flex items-center justify-between pb-2 border-b border-outline-variant">
<h3 class="font-bold flex items-center gap-2 text-on-surface">Pusat Pemulihan Akun</h3>
<button @click="showRecovery=false" class="w-8 h-8 rounded-full text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low flex items-center justify-center">✕</button>
</div>
<p class="text-sm text-on-surface-variant">Masukkan NISN, NUPTK, atau No. HP terdaftar. OTP akan dikirim otomatis.</p>
<label class="block text-xs font-bold uppercase tracking-wide">ID Pengguna / Nomor Ponsel<input v-model="recoveryId" class="mt-1.5 w-full bg-surface-container-low px-4 py-3 rounded-md text-sm border border-outline-variant focus:outline-none focus:border-primary-container focus:ring-4 focus:ring-primary-container/10" placeholder="0078129482 / 08123456789"/></label>
<button @click="doRecovery('WhatsApp')" class="w-full bg-secondary text-on-secondary py-3 rounded-full text-sm font-semibold flex items-center justify-center gap-2 shadow-sm hover:bg-secondary-hover">Kirim via WhatsApp</button>
<button @click="doRecovery('Email')" class="w-full bg-surface-container-low py-3 rounded-full text-sm font-semibold hover:bg-surface-container transition-colors">Kirim via Email</button>
<button @click="showRecovery=false" class="w-full text-xs text-on-surface-variant hover:underline">Tutup Jendela</button>
</div>
</div>
</div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'
import { useSiteSettings } from '../composables/useSiteSettings'
const router = useRouter()
const { loading, error: authError, doLogin } = useAuth()
const { get, asset, load } = useSiteSettings()
load()
const logo = computed(() => asset(get('logo_path', null)) || '/images/emblem.svg')
const brand = computed(() => get('app_name', 'SMA Madani Al-Aziziyah'))
const showPwd = ref(false)
const showRecovery = ref(false)
const recoveryId = ref('')
const expiredParam = new URLSearchParams(location.search).get('expired') === '1'
const currentYear = new Date().getFullYear()
const urlParams = new URLSearchParams(location.search)
urlParams.delete('expired')
const cleanUrl = urlParams.toString() ? location.pathname + '?' + urlParams.toString() : location.pathname
const form = ref({ identity: '', password: '', remember: true })
const map = { admin: '/app/admin', guru: '/app/guru', bendahara: '/app/bendahara', wali_murid: '/app/wali-murid' }
async function submit() {
  try {
    const res = await doLogin(form.value.identity, form.value.password)
    router.push(map[res.user.role] || '/')
  } catch {}
}
function doRecovery(ch) {
  if (!recoveryId.value.trim()) { alert('Harap masukkan ID terlebih dahulu.'); return }
  alert('Tautan pemulihan via ' + ch + ' ke: ' + recoveryId.value)
  showRecovery.value = false
}
</script>