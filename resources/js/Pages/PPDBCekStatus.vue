<template>
  <LpiLayout>
    <section class="w-full bg-surface-container-low px-gutter py-space-sm">
      <div class="max-w-4xl mx-auto flex flex-wrap items-center justify-between gap-space-xs">
        <div class="flex items-center gap-space-xs font-label-sm text-label-sm text-on-surface-variant">
          <router-link to="/" class="hover:text-primary transition-colors">Beranda</router-link>
          <span class="material-symbols-outlined text-[13px] text-outline">chevron_right</span>
          <router-link to="/ppdb" class="hover:text-primary transition-colors">PPDB</router-link>
          <span class="material-symbols-outlined text-[13px] text-outline">chevron_right</span>
          <span class="text-on-surface font-semibold">Cek Status</span>
        </div>
        <span class="font-code-md text-code-md text-outline">PPDB LPI MADANI AL-AZIZIYAH</span>
      </div>
    </section>

    <section class="w-full max-w-3xl mx-auto px-gutter py-space-xl">
      <div class="bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-md p-space-lg">
        <div class="flex flex-col gap-2 mb-6">
          <div class="inline-flex items-center gap-space-xs w-fit bg-primary-fixed text-primary px-space-md py-1 rounded-full font-label-sm text-label-sm">
            <span class="material-symbols-outlined text-[15px]">fact_check</span>
            <span>CEK STATUS PENDAFTARAN</span>
          </div>
          <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold">Cek Status Pendaftaran</h1>
          <p class="font-body-md text-body-md text-on-surface-variant">Masukkan <strong>Nomor Pendaftaran</strong> atau <strong>NIK</strong> yang Anda gunakan saat mendaftar untuk melihat status terkini. Cukup isi salah satu.</p>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-4">
          <div>
            <label class="lbl">Nomor Pendaftaran</label>
            <input v-model="registrationNumber" type="text" class="inp" placeholder="Contoh: PPDB-2026-001-0001" />
          </div>
          <div class="flex items-center gap-2 text-on-surface-variant">
            <span class="h-px flex-1 bg-outline-variant"></span>
            <span class="font-label-sm text-label-sm">atau</span>
            <span class="h-px flex-1 bg-outline-variant"></span>
          </div>
          <div>
            <label class="lbl">NIK (16 digit)</label>
            <input v-model="nik" type="text" inputmode="numeric" maxlength="16" class="inp" placeholder="16 digit NIK pendaftar" />
          </div>

          <p v-if="error" class="text-error bg-error-bg border border-error rounded-xl px-3 py-2 text-sm">{{ error }}</p>

          <div class="flex flex-wrap gap-2 pt-2">
            <button type="submit" :disabled="submitting" class="btn-skeu inline-flex items-center gap-2 px-5 py-2 bg-primary text-on-primary rounded-full text-sm font-semibold disabled:opacity-60">
              <span v-if="submitting" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
              {{ submitting ? 'Memeriksa…' : 'Cek Status' }}
            </button>
            <router-link to="/ppdb/pengumuman" class="px-5 py-2 bg-surface-container-low border border-outline-variant rounded-full text-sm font-semibold">Lihat Pengumuman</router-link>
            <router-link to="/ppdb" class="px-5 py-2 bg-surface-container-low border border-outline-variant rounded-full text-sm font-semibold">Kembali ke PPDB</router-link>
          </div>
        </form>

        <div v-if="result" class="mt-6 border-t border-outline-variant pt-6">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="font-body-sm text-body-sm text-on-surface-variant">Nomor Pendaftaran</p>
              <p class="font-code-md text-code-md text-on-surface font-bold">{{ result.registration_number || '—' }}</p>
            </div>
            <span class="inline-flex items-center gap-1 px-space-sm py-1 rounded-full font-label-sm text-label-sm font-bold" :class="statusClass(result.status)">
              <span class="material-symbols-outlined text-[16px]">{{ statusIcon(result.status) }}</span>
              {{ result.status_label }}
            </span>
          </div>

          <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-space-sm">
              <p class="font-label-sm text-label-sm text-on-surface-variant">Nama Santri</p>
              <p class="font-title-md text-title-md text-on-surface font-semibold">{{ result.name || '—' }}</p>
            </div>
            <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-space-sm">
              <p class="font-label-sm text-label-sm text-on-surface-variant">Jalur Pendaftaran</p>
              <p class="font-title-md text-title-md text-on-surface font-semibold">{{ result.jenjang_label || '—' }}</p>
            </div>
            <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-space-sm">
              <p class="font-label-sm text-label-sm text-on-surface-variant">Gelombang</p>
              <p class="font-title-md text-title-md text-on-surface font-semibold">{{ result.wave?.name || '—' }}</p>
            </div>
            <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-space-sm">
              <p class="font-label-sm text-label-sm text-on-surface-variant">Tanggal Daftar</p>
              <p class="font-title-md text-title-md text-on-surface font-semibold">{{ fmtDateTime(result.registered_at) }}</p>
            </div>
          </div>

          <p class="mt-4 rounded-2xl px-4 py-3 font-body-sm text-body-sm" :class="statusClass(result.status)">{{ result.status_message }}</p>

          <div class="flex flex-wrap gap-2 mt-4">
            <router-link to="/ppdb/upload-foto" class="px-5 py-2 bg-primary text-on-primary rounded-full text-sm font-semibold">Upload Pas Foto</router-link>
            <button type="button" @click="reset" class="px-5 py-2 bg-surface-container-low border border-outline-variant rounded-full text-sm font-semibold">Cek Lagi</button>
          </div>
        </div>
      </div>
    </section>
  </LpiLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import LpiLayout from '../Components/LpiLayout.vue'

const route = useRoute()
const registrationNumber = ref('')
const nik = ref('')
const submitting = ref(false)
const error = ref('')
const result = ref(null)

onMounted(() => {
  if (route.query.nomor) {
    registrationNumber.value = String(route.query.nomor)
  }
})

function statusClass(status) {
  return {
    diterima: 'bg-success-bg text-success border border-success-solid',
    cadangan: 'bg-warning-bg text-warning border border-warning-solid',
    ditolak: 'bg-error-bg text-error border border-error-solid',
    pending: 'bg-primary-fixed text-primary border border-primary/20',
  }[status] || 'bg-surface-container-low text-on-surface-variant border border-outline-variant'
}

function statusIcon(status) {
  return {
    diterima: 'check_circle',
    cadangan: 'hourglass_top',
    ditolak: 'cancel',
    pending: 'schedule',
  }[status] || 'info'
}

function fmtDateTime(d) {
  try { return new Date(d).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) } catch { return d || '—' }
}

async function submit() {
  error.value = ''
  result.value = null
  const payload = {}
  if (registrationNumber.value.trim()) payload.registration_number = registrationNumber.value.trim()
  if (nik.value.trim()) payload.nik = nik.value.trim()

  if (!payload.registration_number && !payload.nik) {
    error.value = 'Isi nomor pendaftaran atau NIK terlebih dahulu.'
    return
  }

  submitting.value = true
  try {
    const r = await fetch('/api/ppdb-registrations/check-status', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(payload),
    })
    const j = await r.json().catch(() => ({}))
    if (!r.ok) {
      const list = j.errors ? Object.values(j.errors).flat() : [j.message || 'Gagal memeriksa status']
      throw new Error(list[0])
    }
    result.value = j
  } catch (e) {
    error.value = e.message
  } finally {
    submitting.value = false
  }
}

function reset() {
  registrationNumber.value = ''
  nik.value = ''
  error.value = ''
  result.value = null
}
</script>

<style scoped>
.lbl { display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--color-on-surface); }
.inp {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid var(--color-outline-variant);
  border-radius: 10px;
  background: var(--color-surface-container-lowest);
  color: var(--color-on-surface);
  font-size: 14px;
  outline: none;
  box-shadow: inset 0 1px 2px rgba(50, 104, 84, 0.05);
}
.inp:focus { border-color: var(--color-primary); box-shadow: inset 0 1px 2px rgba(50, 104, 84, 0.08), 0 0 0 3px color-mix(in srgb, var(--color-primary) 25%, transparent); }
</style>
