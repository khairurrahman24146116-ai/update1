<template>
  <LpiLayout>
    <section class="w-full bg-surface-container-low px-gutter py-space-sm">
      <div class="max-w-5xl mx-auto flex flex-wrap items-center justify-between gap-space-xs">
        <div class="flex items-center gap-space-xs font-label-sm text-label-sm text-on-surface-variant">
          <router-link to="/" class="hover:text-primary transition-colors">Beranda</router-link>
          <span class="material-symbols-outlined text-[13px] text-outline">chevron_right</span>
          <router-link to="/ppdb" class="hover:text-primary transition-colors">PPDB</router-link>
          <span class="material-symbols-outlined text-[13px] text-outline">chevron_right</span>
          <span class="text-on-surface font-semibold">Pengumuman</span>
        </div>
        <span class="font-code-md text-code-md text-outline">PPDB LPI MADANI AL-AZIZIYAH</span>
      </div>
    </section>

    <section class="w-full max-w-5xl mx-auto px-gutter py-space-xl">
      <div class="flex flex-col gap-2 mb-6">
        <div class="inline-flex items-center gap-space-xs w-fit bg-primary-fixed text-primary px-space-md py-1 rounded-full font-label-sm text-label-sm">
          <span class="material-symbols-outlined text-[15px]">campaign</span>
          <span>PENGUMUMAN HASIL SELEKSI</span>
        </div>
        <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold">Pengumuman Penerimaan Santri Baru</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">Daftar pendaftar yang dinyatakan <strong>diterima</strong>. Gunakan filter gelombang, jalur, atau pencarian nama/nomor pendaftaran.</p>
      </div>

      <div class="bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-md p-space-md sm:p-space-lg">
        <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
          <div>
            <label class="lbl">Gelombang</label>
            <select v-model="filters.wave_id" class="inp">
              <option value="">Semua gelombang</option>
              <option v-for="w in waves" :key="w.id" :value="String(w.id)">{{ w.name }}</option>
            </select>
          </div>
          <div>
            <label class="lbl">Jalur</label>
            <select v-model="filters.jenjang" class="inp">
              <option value="">Semua jalur</option>
              <option v-for="(label, key) in jenjangLabels" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
          <div class="md:col-span-2 flex gap-2">
            <div class="flex-1">
              <label class="lbl">Cari</label>
              <input v-model="filters.search" type="text" class="inp" placeholder="Nama atau nomor pendaftaran" />
            </div>
            <button type="submit" :disabled="loading" class="btn-skeu inline-flex items-center gap-2 px-4 py-2 bg-primary text-on-primary rounded-full text-sm font-semibold disabled:opacity-60 self-end">
              <span class="material-symbols-outlined text-[18px]">search</span>
              Cari
            </button>
            <button type="button" @click="resetFilters" class="px-4 py-2 bg-surface-container-low border border-outline-variant rounded-full text-sm font-semibold self-end">Reset</button>
          </div>
        </form>

        <p v-if="error" class="mt-4 text-red-700 bg-red-50 border border-red-200 rounded-xl px-3 py-2 text-sm">{{ error }}</p>

        <div class="mt-5">
          <div v-if="loading" class="flex items-center justify-center gap-2 py-10 text-on-surface-variant font-body-sm text-body-sm">
            <span class="material-symbols-outlined animate-spin">progress_activity</span>
            Memuat pengumuman…
          </div>

          <div v-else-if="!rows.length" class="text-center py-10">
            <span class="material-symbols-outlined text-[40px] text-outline">inbox</span>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2">Belum ada pendaftar yang diterima sesuai filter ini.</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-left font-body-sm text-body-sm">
              <thead>
                <tr class="border-b border-outline-variant text-on-surface-variant font-label-sm text-label-sm">
                  <th class="py-2 pr-3 w-12">No</th>
                  <th class="py-2 pr-3">Nomor Pendaftaran</th>
                  <th class="py-2 pr-3">Nama</th>
                  <th class="py-2 pr-3">Jalur</th>
                  <th class="py-2">Gelombang</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(r, i) in rows" :key="r.registration_number || i" class="border-b border-outline-variant/60">
                  <td class="py-2.5 pr-3 text-on-surface-variant">{{ (meta.current_page - 1) * meta.per_page + i + 1 }}</td>
                  <td class="py-2.5 pr-3 font-code-md text-code-md">{{ r.registration_number || '—' }}</td>
                  <td class="py-2.5 pr-3 font-semibold text-on-surface">{{ r.name }}</td>
                  <td class="py-2.5 pr-3">{{ r.jenjang_label }}</td>
                  <td class="py-2.5">{{ r.wave?.name || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="meta.last_page > 1" class="mt-4 flex flex-wrap items-center justify-between gap-2">
          <p class="font-label-sm text-label-sm text-on-surface-variant">Halaman {{ meta.current_page }} dari {{ meta.last_page }} • {{ meta.total }} pendaftar diterima</p>
          <div class="flex gap-2">
            <button type="button" :disabled="meta.current_page <= 1 || loading" @click="goTo(meta.current_page - 1)" class="px-4 py-2 bg-surface-container-low border border-outline-variant rounded-full text-sm font-semibold disabled:opacity-50">Sebelumnya</button>
            <button type="button" :disabled="meta.current_page >= meta.last_page || loading" @click="goTo(meta.current_page + 1)" class="px-4 py-2 bg-surface-container-low border border-outline-variant rounded-full text-sm font-semibold disabled:opacity-50">Berikutnya</button>
          </div>
        </div>

        <div class="mt-6 pt-4 border-t border-outline-variant flex flex-wrap items-center justify-between gap-2">
          <p class="font-label-sm text-label-sm text-on-surface-variant">Pengumuman resmi hanya menampilkan pendaftar berstatus diterima.</p>
          <div class="flex flex-wrap gap-2">
            <router-link to="/ppdb/cek-status" class="px-4 py-2 bg-primary text-on-primary rounded-full text-sm font-semibold">Cek Status Saya</router-link>
            <router-link to="/ppdb" class="px-4 py-2 bg-surface-container-low border border-outline-variant rounded-full text-sm font-semibold">Kembali ke PPDB</router-link>
          </div>
        </div>
      </div>
    </section>
  </LpiLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import LpiLayout from '../Components/LpiLayout.vue'

const rows = ref([])
const waves = ref([])
const jenjangLabels = ref({})
const meta = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 })
const filters = ref({ wave_id: '', jenjang: '', search: '' })
const loading = ref(true)
const error = ref('')

async function load(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const q = new URLSearchParams({ page: String(page), per_page: '20' })
    if (filters.value.wave_id) q.set('wave_id', filters.value.wave_id)
    if (filters.value.jenjang) q.set('jenjang', filters.value.jenjang)
    if (filters.value.search.trim()) q.set('search', filters.value.search.trim())

    const r = await fetch('/api/ppdb-registrations/public-announcements?' + q.toString(), {
      headers: { Accept: 'application/json' },
    })
    const j = await r.json().catch(() => ({}))
    if (!r.ok) throw new Error(j.message || 'Gagal memuat pengumuman')

    rows.value = j.data || []
    waves.value = j.waves || []
    jenjangLabels.value = j.jenjang_labels || {}
    meta.value = j.meta || meta.value
  } catch (e) {
    error.value = e.message
    rows.value = []
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  load(1)
}

function resetFilters() {
  filters.value = { wave_id: '', jenjang: '', search: '' }
  load(1)
}

function goTo(page) {
  if (page < 1 || page > meta.value.last_page) return
  load(page)
}

onMounted(() => load(1))
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
