<template>
  <LpiLayout>
    <div class="ppdb-page">
      <!-- HERO -->
      <section class="ppdb-hero">
        <span class="ppdb-deco ppdb-deco--a"></span>
        <span class="ppdb-deco ppdb-deco--b"></span>
        <div class="ppdb-hero-inner">
          <div class="ppdb-hero-logo ppdb-anim" style="--d: 0ms">
            <img src="/images/profile/logo-lpi-transparent.png" alt="Logo LPI Dayah Madani Al-Aziziyah" />
          </div>
          <span class="ppdb-hero-eyebrow ppdb-anim" style="--d: 70ms">LPI Dayah Madani Al-Aziziyah</span>
          <h1 class="ppdb-hero-title ppdb-anim" style="--d: 140ms">Penerimaan Santri Baru</h1>
          <p class="ppdb-hero-sub ppdb-anim" style="--d: 210ms">Tahun Ajaran 2027-2028 M / 1448-1449 H</p>
          <router-link :to="formLink" class="ppdb-btn ppdb-btn--gold ppdb-hero-cta ppdb-anim" style="--d: 280ms">
            <span class="material-symbols-outlined">how_to_reg</span>
            Daftar Sekarang
          </router-link>
        </div>
      </section>

      <!-- TAUTAN CEPAT -->
      <section class="ppdb-section ppdb-section--tight ppdb-anim" style="--d: 120ms">
        <div class="ppdb-container">
          <div class="ppdb-quick">
            <router-link to="/ppdb/cek-status" class="ppdb-quick-link">
              <span class="material-symbols-outlined">fact_check</span>
              Sudah mendaftar? Cek status
            </router-link>
            <router-link to="/ppdb/pengumuman" class="ppdb-quick-link">
              <span class="material-symbols-outlined">campaign</span>
              Pengumuman hasil seleksi
            </router-link>
            <router-link to="/ppdb/upload-foto" class="ppdb-quick-link">
              <span class="material-symbols-outlined">upload_file</span>
              Upload pas foto
            </router-link>
          </div>
        </div>
      </section>

      <!-- INFORMASI PENDAFTARAN -->
      <section id="ppdb-daftar" class="ppdb-section">
        <div class="ppdb-container">
          <div class="ppdb-section-head ppdb-anim">
            <span class="ppdb-label">Informasi Pendaftaran</span>
            <h2 class="ppdb-section-title">Jadwal &amp; Gelombang</h2>
            <p class="ppdb-section-sub">Pilih gelombang yang sedang dibuka, lalu lanjutkan ke formulir pendaftaran.</p>
          </div>

          <div v-if="!loaded" class="ppdb-card ppdb-empty">Memuat gelombang…</div>
          <div v-else-if="!waves.length" class="ppdb-card ppdb-empty">Belum ada gelombang PPDB yang dibuka. Pantau laman ini secara berkala.</div>
          <div v-else class="ppdb-info-grid">
            <button
              v-for="(w, wi) in waves"
              :key="w.id"
              type="button"
              class="ppdb-card ppdb-wave-card ppdb-anim"
              :class="{ 'is-selected': selectedWave && selectedWave.id === w.id }"
              :style="{ '--d': (wi * 80) + 'ms' }"
              @click="selectWave(w)"
            >
              <span class="ppdb-label ppdb-label--sm">Gelombang {{ wi + 1 }}</span>
              <h3 class="ppdb-wave-title">{{ w.name }}</h3>
              <p class="ppdb-wave-date">{{ fmtDate(w.start_date) }} — {{ fmtDate(w.end_date) }}</p>
              <span class="ppdb-badge">{{ statusLabel(w.status) }}</span>
              <p v-if="w.description" class="ppdb-wave-desc">{{ w.description }}</p>
              <span class="ppdb-wave-action">
                {{ selectedWave && selectedWave.id === w.id ? 'Gelombang dipilih' : 'Pilih gelombang' }}
                <span class="material-symbols-outlined">arrow_forward</span>
              </span>
            </button>

            <article class="ppdb-card ppdb-schedule-card ppdb-anim" :style="{ '--d': (waves.length * 80) + 'ms' }">
              <span class="ppdb-label ppdb-label--sm">Jadwal Pendaftaran</span>
              <p class="ppdb-schedule-text">Setiap hari <strong>Sabtu s/d Kamis</strong></p>
              <p class="ppdb-schedule-text">Pukul <strong>09.00–12.00 WIB</strong> dan <strong>14.00–17.00 WIB</strong></p>
            </article>
          </div>
        </div>
      </section>

      <!-- PILIH JALUR -->
      <section class="ppdb-section">
        <div class="ppdb-container">
          <div class="ppdb-section-head ppdb-section-head--row ppdb-anim">
            <div>
              <span class="ppdb-label">Pilih Jalur</span>
              <h2 class="ppdb-section-title">Pilih Jalur Pendaftaran</h2>
              <p class="ppdb-section-sub">Pilih salah satu jalur sesuai jenjang yang dituju. Pilihan dapat diubah sebelum formulir dikirim.</p>
            </div>
            <span v-if="selectedJenjang" class="ppdb-selected-badge">
              <span class="material-symbols-outlined">check_circle</span>
              {{ jenjangLabel(selectedJenjang) }}
            </span>
          </div>

          <div class="ppdb-jalur-grid">
            <button
              v-for="(j, ji) in jenjangOptions"
              :key="j.key"
              type="button"
              class="ppdb-jalur-card ppdb-anim"
              :class="{ 'is-selected': selectedJenjang === j.key }"
              :style="{ '--d': (ji * 70) + 'ms' }"
              @click="selectJenjang(j.key)"
            >
              <div class="ppdb-jalur-top">
                <span class="ppdb-jalur-icon" :class="{ 'is-on': selectedJenjang === j.key }">
                  <span class="material-symbols-outlined">{{ j.icon }}</span>
                </span>
                <span v-if="selectedJenjang === j.key" class="material-symbols-outlined ppdb-jalur-check">check_circle</span>
                <span v-else-if="j.badge" class="ppdb-badge-soft">{{ j.badge }}</span>
              </div>
              <h3 class="ppdb-jalur-title">{{ j.label }}</h3>
              <p class="ppdb-jalur-desc">{{ j.description }}</p>
              <p v-if="j.note" class="ppdb-note">
                <span class="material-symbols-outlined">info</span>{{ j.note }}
              </p>
              <span class="ppdb-jalur-cta" :class="{ 'is-on': selectedJenjang === j.key }">
                {{ selectedJenjang === j.key ? 'Jalur dipilih' : 'Pilih jalur' }}
                <span class="material-symbols-outlined">arrow_forward</span>
              </span>
            </button>
          </div>
        </div>
      </section>

      <!-- CTA FORMULIR -->
      <section class="ppdb-section">
        <div class="ppdb-container">
          <div class="ppdb-card ppdb-cta-card ppdb-anim">
            <div>
              <span class="ppdb-label">Siap Mendaftar?</span>
              <h2 class="ppdb-cta-title">Isi Formulir Pendaftaran</h2>
              <p class="ppdb-cta-text">Lengkapi data santri, orang tua, dan dokumen pendukung melalui formulir online.</p>
              <div class="ppdb-cta-summary">
                <span class="ppdb-cta-chip">
                  <span class="material-symbols-outlined">calendar_month</span>
                  {{ selectedWave ? selectedWave.name : 'Gelombang belum dipilih' }}
                </span>
                <span class="ppdb-cta-chip">
                  <span class="material-symbols-outlined">route</span>
                  {{ selectedJenjang ? jenjangLabel(selectedJenjang) : 'Jalur belum dipilih' }}
                </span>
              </div>
            </div>
            <router-link :to="formLink" class="ppdb-btn ppdb-btn--primary ppdb-cta-btn">
              <span class="material-symbols-outlined">edit_document</span>
              Isi Formulir
            </router-link>
          </div>
        </div>
      </section>
    </div>
  </LpiLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import LpiLayout from '../Components/LpiLayout.vue'

const waves = ref([])
const loaded = ref(false)
const selectedWave = ref(null)
const selectedJenjang = ref('')

const jenjangOptions = [
  { key: 'lanjut', label: 'Jenjang Lanjut', icon: 'trending_up', badge: '', description: 'Pendaftaran santri yang melanjutkan ke jenjang berikutnya di lingkungan LPI Dayah Madani Al-Aziziyah.', note: '' },
  { key: 'sma', label: 'Pendaftaran SMA', icon: 'account_balance', badge: '', description: 'Pendaftaran santri baru untuk jenjang SMA Dayah Madani Al-Aziziyah.', note: '' },
  { key: 'smp', label: 'Pendaftaran SMP', icon: 'school', badge: '', description: 'Pendaftaran santri baru untuk jenjang SMP Dayah Madani Al-Aziziyah.', note: '' },
  { key: 'salafiyah', label: 'Salafiyah Santri Putri', icon: 'female', badge: 'Khusus Putri', description: 'Pendaftaran khusus santri putri pada program Salafiyah.', note: 'Jenis kelamin otomatis Perempuan' },
]
const jenjangMap = Object.fromEntries(jenjangOptions.map((j) => [j.key, j.label]))
function jenjangLabel(key) { return jenjangMap[key] || '—' }

const statusMap = { buka: 'Dibuka', tutup: 'Ditutup', archived: 'Arsip' }
function statusLabel(s) { return statusMap[s] || s || '—' }
function fmtDate(d) { return d ? String(d).slice(0, 10) : '—' }

const formLink = computed(() => {
  const query = {}
  if (selectedWave.value) query.wave = selectedWave.value.id
  if (selectedJenjang.value) query.jenjang = selectedJenjang.value
  return { path: '/ppdb/daftar', query }
})

onMounted(async () => {
  try {
    const wr = await fetch('/api/ppdb-waves/public')
    if (wr.ok) waves.value = await wr.json()
  } catch { /* abaikan */ } finally { loaded.value = true }
})

function selectWave(w) {
  selectedWave.value = selectedWave.value && selectedWave.value.id === w.id ? null : w
}

function selectJenjang(key) {
  selectedJenjang.value = selectedJenjang.value === key ? '' : key
}
</script>

<style scoped src="../../css/ppdb.css"></style>
