<template>
  <div class="min-h-screen bg-surface font-body text-on-surface antialiased flex items-center justify-center p-4">
    <main class="w-full max-w-lg bg-surface-container-lowest rounded-[28px] border border-outline-variant shadow-card p-8 text-center space-y-5">
      <div class="mx-auto w-20 h-20 rounded-full bg-warning-bg border border-warning flex items-center justify-center">
        <span class="material-symbols-outlined text-[40px] text-warning">hourglass_top</span>
      </div>
      <div class="space-y-2">
        <h1 class="font-headline text-xl font-extrabold">Menunggu Hak Akses</h1>
        <p class="text-sm text-on-surface-variant">
          Akun Anda sudah terdaftar, tetapi peran dan unit belum ditetapkan oleh Admin Utama.
          Silakan hubungi administrator untuk menyelesaikan pengaturan akun.
        </p>
      </div>
      <div v-if="user" class="bg-surface-container-low border border-outline-variant rounded-xl p-4 text-left text-sm space-y-1">
        <p class="font-bold">{{ user.name }}</p>
        <p class="text-on-surface-variant">{{ user.email }}</p>
        <p v-if="user.unit" class="text-on-surface-variant">Unit: {{ user.unit }}</p>
      </div>
      <button @click="logout" class="w-full h-12 bg-primary text-on-primary font-semibold rounded-full hover:bg-primary-hover transition-colors">
        Keluar
      </button>
    </main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'

const router = useRouter()
const { user, doLogout } = useAuth()

async function logout() {
  doLogout()
  await router.replace('/login')
}
</script>
