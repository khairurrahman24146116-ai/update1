<template>
  <LpiLayout>
    <section class="w-full bg-surface-container-low px-gutter py-space-sm">
      <div class="max-w-4xl mx-auto flex flex-wrap items-center justify-between gap-space-xs">
        <div class="flex items-center gap-space-xs font-label-sm text-label-sm text-on-surface-variant">
          <router-link to="/" class="hover:text-primary transition-colors">Beranda</router-link>
          <span class="material-symbols-outlined text-[13px] text-outline">chevron_right</span>
          <router-link to="/ppdb" class="hover:text-primary transition-colors">PPDB</router-link>
          <span class="material-symbols-outlined text-[13px] text-outline">chevron_right</span>
          <span class="text-on-surface font-semibold">Upload Pas Foto</span>
        </div>
        <span class="font-code-md text-code-md text-outline">PPDB LPI MADANI AL-AZIZIYAH</span>
      </div>
    </section>

    <section class="w-full max-w-3xl mx-auto px-gutter py-space-xl">
      <div class="bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-md p-space-lg">
        <div class="flex flex-col gap-2 mb-6">
          <div class="inline-flex items-center gap-space-xs w-fit bg-primary-fixed text-primary px-space-md py-1 rounded-full font-label-sm text-label-sm">
            <span class="material-symbols-outlined text-[15px]">upload_file</span>
            <span>UPLOAD PAS FOTO</span>
          </div>
          <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold">Unggah Pas Foto Pendaftar</h1>
          <p class="font-body-md text-body-md text-on-surface-variant">Masukkan <strong>Nomor Pendaftaran</strong> yang Anda terima setelah mengisi formulir, lalu unggah pas foto 3×4 (JPG/PNG/WebP, maks 2MB). Foto dapat diunggah selama gelombang masih dibuka.</p>
        </div>

        <form v-if="!success" @submit.prevent="submit" class="flex flex-col gap-4">
          <div>
            <label class="lbl">Nomor Pendaftaran *</label>
            <input v-model="registrationNumber" type="text" class="inp" placeholder="Contoh: PPDB-2026-001-0001" required />
          </div>
          <div>
            <label class="lbl">Pas Foto 3×4 (JPG/PNG/WebP, maks 2MB) *</label>
            <input ref="fileInput" type="file" accept=".jpg,.jpeg,.png,.webp" class="inp" required @change="onPhoto" />
            <img v-if="preview" :src="preview" alt="Pratinjau pas foto" class="w-24 h-32 object-cover rounded-xl border border-outline-variant mt-2" />
          </div>

          <p v-if="error" class="text-red-700 bg-red-50 border border-red-200 rounded-xl px-3 py-2 text-sm">{{ error }}</p>

          <div class="flex flex-wrap gap-2 pt-2">
            <button type="submit" :disabled="submitting" class="btn-skeu inline-flex items-center gap-2 px-5 py-2 bg-primary text-on-primary rounded-full text-sm font-semibold disabled:opacity-60">
              <span v-if="submitting" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
              {{ submitting ? 'Mengunggah…' : 'Unggah Foto' }}
            </button>
            <router-link to="/ppdb" class="px-5 py-2 bg-surface-container-low border border-outline-variant rounded-full text-sm font-semibold">Kembali ke PPDB</router-link>
          </div>
        </form>

        <div v-else class="text-center flex flex-col items-center gap-3 py-4">
          <span class="w-14 h-14 rounded-full bg-primary-fixed text-primary flex items-center justify-center"><span class="material-symbols-outlined text-[28px]">check_circle</span></span>
          <h2 class="font-headline-md text-headline-md font-bold">Foto Berhasil Diunggah</h2>
          <div class="w-full bg-surface-container-low border border-outline-variant rounded-2xl p-space-md text-left font-body-md text-body-md grid grid-cols-1 sm:grid-cols-2 gap-2">
            <p><strong>Nomor pendaftaran:</strong><br />{{ success.registration_number }}</p>
            <p><strong>Nama santri:</strong><br />{{ success.name }}</p>
            <p><strong>Jalur:</strong><br />{{ jenjangLabel(success.jenjang) }}</p>
          </div>
          <div class="flex flex-wrap justify-center gap-2">
            <button @click="resetForm" class="px-5 py-2 bg-primary text-on-primary rounded-full text-sm font-semibold">Upload Foto Lain</button>
            <router-link to="/ppdb" class="px-5 py-2 bg-surface-container-low border border-outline-variant rounded-full text-sm font-semibold">Kembali ke PPDB</router-link>
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
const photoFile = ref(null)
const preview = ref('')
const fileInput = ref(null)
const submitting = ref(false)
const error = ref('')
const success = ref(null)

const jenjangLabels = {
  lanjut: 'Jenjang Lanjut',
  sma: 'Pendaftaran SMA',
  smp: 'Pendaftaran SMP',
  salafiyah: 'Salafiyah Santri Putri',
}
function jenjangLabel(key) { return jenjangLabels[key] || '—' }

onMounted(() => {
  if (route.query.registration_number) {
    registrationNumber.value = String(route.query.registration_number)
  }
})

function onPhoto(e) {
  const f = e.target.files?.[0]
  error.value = ''
  if (!f) return
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(f.type)) {
    error.value = 'Pas foto harus JPG/PNG/WebP.'
    photoFile.value = null
    preview.value = ''
    return
  }
  if (f.size > 2 * 1024 * 1024) {
    error.value = 'Ukuran pas foto maksimal 2MB.'
    photoFile.value = null
    preview.value = ''
    return
  }
  photoFile.value = f
  preview.value = URL.createObjectURL(f)
}

async function submit() {
  error.value = ''
  if (!registrationNumber.value || !photoFile.value) {
    error.value = 'Nomor pendaftaran dan pas foto wajib diisi.'
    return
  }
  submitting.value = true
  try {
    const fd = new FormData()
    fd.append('registration_number', registrationNumber.value.trim())
    fd.append('photo', photoFile.value)
    const r = await fetch('/api/ppdb-registrations/photo-upload', { method: 'POST', body: fd })
    const j = await r.json().catch(() => ({}))
    if (!r.ok) {
      const list = j.errors ? Object.values(j.errors).flat() : [j.message || 'Gagal mengunggah foto']
      throw new Error(list[0])
    }
    success.value = j
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (e) {
    error.value = e.message
  } finally {
    submitting.value = false
  }
}

function resetForm() {
  registrationNumber.value = ''
  photoFile.value = null
  preview.value = ''
  error.value = ''
  success.value = null
  if (fileInput.value) fileInput.value.value = ''
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
  font-size: 14px;
  outline: none;
  box-shadow: inset 0 1px 2px rgba(50, 104, 84, 0.05);
}
.inp:focus { border-color: var(--color-primary); box-shadow: inset 0 1px 2px rgba(50, 104, 84, 0.08), 0 0 0 3px var(--color-primary-fixed); }
</style>
