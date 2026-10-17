<template>
<AppShell title="Pengaturan Beranda">
  <BackButton fallback="/app/admin" />

  <div class="relative bg-white p-6 md:p-8 rounded-xl border border-outline-variant shadow-md flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4 overflow-hidden">
    <div class="space-y-2 max-w-3xl relative z-10">
      <p class="text-sm text-on-surface-variant">Kelola hero beranda publik LPI: logo, teks, media latar, dan kartu hitung mundur waktu salat.</p>
    </div>
    <div class="flex flex-wrap gap-3 relative z-10">
      <router-link to="/" target="_blank" class="btn btn--ghost"><span class="material-symbols-outlined !w-4 !h-4">open_in_new</span> Lihat Beranda</router-link>
      <button @click="saveSettings" :disabled="saving" class="btn btn--primary disabled:opacity-50"><span class="material-symbols-outlined !w-4 !h-4">save</span> {{ saving ? 'Menyimpan…' : 'Simpan Teks & Lokasi' }}</button>
    </div>
  </div>

  <section class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
    <div class="bg-surface-container-low border-b border-outline-variant px-4 py-3 flex items-center gap-2">
      <span class="w-8 h-8 rounded-full bg-primary text-on-primary flex items-center justify-center border border-outline-variant"><span class="material-symbols-outlined !w-5 !h-5">image</span></span>
      <div><h2 class="font-bold text-sm">1. Logo Hero</h2><p class="text-xs text-on-surface-variant">PNG/JPG/WebP. Kosongkan untuk memakai logo bawaan.</p></div>
    </div>
    <div class="p-4 md:p-6 flex flex-col sm:flex-row items-start gap-6">
      <div class="flex flex-col items-center gap-3 p-4 bg-surface border border-dashed border-outline-variant rounded-xl">
        <div class="flex items-end gap-3">
          <figure class="flex flex-col items-center gap-1.5">
            <div class="logo-plate rounded-2xl p-3">
              <img :src="asset(form.homepage_logo_path) || asset(form.logo_path) || '/images/profile/logo-lpi-transparent.png'" alt="Pratinjau logo di kartu putih" width="112" height="112" class="w-28 h-28 object-contain" />
            </div>
            <figcaption class="text-[10px] font-bold uppercase tracking-wide text-on-surface-variant">Kartu putih</figcaption>
          </figure>
          <figure class="flex flex-col items-center gap-1.5">
            <div class="checkerboard rounded-2xl border border-outline-variant overflow-hidden">
              <img :src="asset(form.homepage_logo_path) || asset(form.logo_path) || '/images/profile/logo-lpi-transparent.png'" alt="Pratinjau transparansi logo" width="112" height="112" class="w-28 h-28 object-contain p-3" />
            </div>
            <figcaption class="text-[10px] font-bold uppercase tracking-wide text-on-surface-variant">Transparan</figcaption>
          </figure>
        </div>
        <label class="btn btn--ghost btn--sm cursor-pointer">
          <span class="material-symbols-outlined !w-4 !h-4">upload</span>
          {{ logoUploading ? 'Mengunggah…' : 'Ganti Logo' }}
          <input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" :disabled="logoUploading" @change="uploadLogo" />
        </label>
        <button v-if="form.homepage_logo_path" @click="resetLogo" class="btn btn--ghost btn--sm text-error">Kembalikan Default</button>
      </div>
      <div class="max-w-sm space-y-1">
        <p class="text-xs text-on-surface-variant">Logo tampil di kartu persegi membulat di atas judul beranda.</p>
        <p class="text-xs text-on-surface-variant"><span class="font-bold">Gunakan PNG latar transparan</span> (mis. 512×512 px) agar logo bersih tanpa kotak latar. Petak papan catur di atas menunjukkan area transparan.</p>
      </div>
    </div>
  </section>

  <section class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
    <div class="bg-surface-container-low border-b border-outline-variant px-4 py-3 flex items-center gap-2">
      <span class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center border border-outline-variant"><span class="material-symbols-outlined !w-5 !h-5">text_fields</span></span>
      <div><h2 class="font-bold text-sm">2. Teks Hero</h2><p class="text-xs text-on-surface-variant">Badge, judul, dan tagline.</p></div>
    </div>
    <div class="p-4 md:p-6 space-y-4">
      <div class="space-y-1.5"><label class="block text-xs font-bold">Teks Badge</label><input v-model="form.homepage_badge_text" class="input" placeholder="Lembaga Pendidikan Islam" /></div>
      <div class="space-y-1.5"><label class="block text-xs font-bold">Judul</label><input v-model="form.homepage_title" class="input" placeholder="LPI Dayah Madani Al-Aziziyah" /></div>
      <div class="space-y-1.5"><label class="block text-xs font-bold">Tagline</label><textarea v-model="form.homepage_tagline" rows="2" class="input h-auto py-3" placeholder="Tagline beranda"></textarea></div>
    </div>
  </section>

  <section class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
    <div class="bg-surface-container-low border-b border-outline-variant px-4 py-3 flex items-center justify-between gap-2">
      <div class="flex items-center gap-2">
        <span class="w-8 h-8 rounded-full bg-tertiary-fixed flex items-center justify-center border border-outline-variant"><span class="material-symbols-outlined !w-5 !h-5">landscape</span></span>
        <div><h2 class="font-bold text-sm">3. Media Latar</h2><p class="text-xs text-on-surface-variant">Gambar png/jpg/webp maks 5 MB, video mp4/webm maks 30 MB.</p></div>
      </div>
      <label class="btn btn--ghost btn--sm cursor-pointer shrink-0">
        <span class="material-symbols-outlined !w-4 !h-4">add_photo_alternate</span>
        {{ mediaUploading ? 'Mengunggah…' : 'Unggah Media' }}
        <input type="file" accept="image/png,image/jpeg,image/webp,video/mp4,video/webm" class="sr-only" :disabled="mediaUploading" @change="uploadMedia" />
      </label>
    </div>
    <div class="p-4 md:p-6 space-y-4">
      <div class="space-y-2">
        <label class="block text-xs font-bold">Tingkat Gelap Overlay ({{ form.hero_overlay_opacity }}%)</label>
        <input type="range" min="0" max="80" v-model.number="form.hero_overlay_opacity" class="w-full" />
      </div>

      <p v-if="!media.length" class="text-xs text-on-surface-variant">Belum ada media. Latar bawaan (gradasi bergerak) akan dipakai.</p>
      <ul v-else class="space-y-2">
        <li v-for="(m, i) in media" :key="m.id" class="p-3 bg-surface-container-low border border-outline-variant rounded-xl flex items-center gap-3">
          <div class="w-16 h-12 rounded-lg bg-white border border-outline-variant overflow-hidden flex items-center justify-center shrink-0">
            <img v-if="m.file_type === 'image'" :src="asset(m.file_path)" alt="" class="w-full h-full object-cover" />
            <span v-else class="material-symbols-outlined text-on-surface-variant">movie</span>
          </div>
          <div class="min-w-0 flex-1">
            <div class="font-bold text-xs uppercase">{{ m.file_type === 'video' ? 'Video' : 'Gambar' }}</div>
            <div class="text-xs text-on-surface-variant truncate">{{ fileNameOf(m.file_path) }}</div>
          </div>
          <div class="flex items-center gap-1 shrink-0">
            <button @click="move(i, -1)" :disabled="i === 0" class="w-8 h-8 rounded-full border border-outline-variant bg-white disabled:opacity-40" aria-label="Naikkan"><span class="material-symbols-outlined !w-4 !h-4">arrow_upward</span></button>
            <button @click="move(i, 1)" :disabled="i === media.length - 1" class="w-8 h-8 rounded-full border border-outline-variant bg-white disabled:opacity-40" aria-label="Turunkan"><span class="material-symbols-outlined !w-4 !h-4">arrow_downward</span></button>
            <label class="flex items-center gap-1 text-xs px-2"><input type="checkbox" :checked="m.is_active" @change="toggleActive(i)" /> Aktif</label>
            <button @click="removeMedia(m)" class="w-8 h-8 rounded-full border border-error text-error bg-white" aria-label="Hapus"><span class="material-symbols-outlined !w-4 !h-4">delete</span></button>
          </div>
        </li>
      </ul>
      <div v-if="media.length" class="flex justify-end">
        <button @click="saveMedia" :disabled="mediaSaving" class="btn btn--primary btn--sm disabled:opacity-50">{{ mediaSaving ? 'Menyimpan…' : 'Simpan Urutan & Status' }}</button>
      </div>
    </div>
  </section>

  <section class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
    <div class="bg-surface-container-low border-b border-outline-variant px-4 py-3 flex items-center justify-between gap-2">
      <div class="flex items-center gap-2">
        <span class="w-8 h-8 rounded-full bg-primary-fixed flex items-center justify-center border border-outline-variant"><span class="material-symbols-outlined !w-5 !h-5">mosque</span></span>
        <div><h2 class="font-bold text-sm">4. Waktu Salat</h2><p class="text-xs text-on-surface-variant">Nama kota, koordinat, dan zona waktu.</p></div>
      </div>
      <label class="flex items-center gap-1 text-xs"><input type="checkbox" v-model="form.prayer_card_visible" /> Tampilkan kartu</label>
    </div>
    <div class="p-4 md:p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="space-y-1.5 md:col-span-2"><label class="block text-xs font-bold">Nama Kota / Lokasi</label><input v-model="form.prayer_city_name" class="input" placeholder="Banda Aceh" /></div>
      <div class="space-y-1.5"><label class="block text-xs font-bold">Latitude</label><input v-model="form.prayer_latitude" type="number" step="any" class="input" /></div>
      <div class="space-y-1.5"><label class="block text-xs font-bold">Longitude</label><input v-model="form.prayer_longitude" type="number" step="any" class="input" /></div>
      <div class="space-y-1.5 md:col-span-2"><label class="block text-xs font-bold">Zona Waktu</label><input v-model="form.prayer_timezone" class="input" placeholder="Asia/Jakarta" /></div>
      <p class="md:col-span-2 text-xs text-on-surface-variant">Sudut Subuh 20° dan Isya 18° (standar Kemenag), dihitung di perangkat pengunjung tanpa API pihak ketiga.</p>
    </div>
  </section>

  <Transition name="toast">
    <div v-if="toast" class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-full text-sm font-bold border border-outline-variant shadow-sm" :class="toastType === 'error' ? 'bg-error-container text-on-error-container' : 'bg-primary-fixed text-on-surface'">{{ toast }}</div>
  </Transition>
</AppShell>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import AppShell from '../../Components/AppShell.vue'
import BackButton from '../../Components/BackButton.vue'

const token = () => localStorage.getItem('madani_token')

const form = reactive({
  homepage_badge_text: '',
  homepage_title: '',
  homepage_tagline: '',
  homepage_logo_path: '',
  logo_path: '',
  prayer_city_name: '',
  prayer_latitude: '',
  prayer_longitude: '',
  prayer_timezone: 'Asia/Jakarta',
  prayer_card_visible: true,
  hero_overlay_opacity: 40,
})

const loading = ref(false)
const saving = ref(false)
const logoUploading = ref(false)
const mediaUploading = ref(false)
const mediaSaving = ref(false)
const media = ref([])
const toast = ref('')
const toastType = ref('success')

function notify(msg, type = 'success') {
  toast.value = msg
  toastType.value = type
  setTimeout(() => { toast.value = '' }, 3200)
}

function asset(path) {
  if (!path) return null
  return /^https?:\/\//.test(path) ? path : `${location.origin}/storage/${path}`
}
function fileNameOf(path) {
  if (!path) return ''
  return String(path).split(/[\\/]/).pop() || path
}
async function readError(r, fallback) {
  const j = await r.json().catch(() => ({}))
  return j.message || (j.errors && Object.values(j.errors).flat().join(', ')) || fallback
}

async function fetchData() {
  loading.value = true
  try {
    const r = await fetch('/api/homepage-settings', { headers: { Authorization: `Bearer ${token()}` } })
    if (!r.ok) throw new Error(await readError(r, 'Gagal memuat pengaturan beranda.'))
    const j = await r.json()
    const s = j.settings || {}
    Object.keys(form).forEach((k) => {
      if (s[k] !== undefined && s[k] !== null) form[k] = s[k]
    })
    media.value = Array.isArray(j.hero_media) ? j.hero_media : []
  } catch (e) {
    notify(e.message, 'error')
  } finally {
    loading.value = false
  }
}

async function saveSettings() {
  saving.value = true
  try {
    const payload = {
      homepage_badge_text: form.homepage_badge_text || null,
      homepage_title: form.homepage_title || null,
      homepage_tagline: form.homepage_tagline || null,
      prayer_city_name: form.prayer_city_name || null,
      prayer_latitude: form.prayer_latitude === '' ? null : Number(form.prayer_latitude),
      prayer_longitude: form.prayer_longitude === '' ? null : Number(form.prayer_longitude),
      prayer_timezone: form.prayer_timezone || 'Asia/Jakarta',
      prayer_card_visible: form.prayer_card_visible,
      hero_overlay_opacity: Number(form.hero_overlay_opacity),
    }
    const r = await fetch('/api/homepage-settings', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token()}` },
      body: JSON.stringify(payload),
    })
    if (!r.ok) throw new Error(await readError(r, 'Gagal menyimpan pengaturan.'))
    notify('Pengaturan beranda tersimpan.')
    await fetchData()
  } catch (e) {
    notify(e.message, 'error')
  } finally {
    saving.value = false
  }
}

async function uploadLogo(event) {
  const file = event.target.files?.[0]
  if (!file) return
  logoUploading.value = true
  try {
    const fd = new FormData()
    fd.append('file', file)
    const r = await fetch('/api/homepage-settings/upload-logo', { method: 'POST', headers: { Authorization: `Bearer ${token()}` }, body: fd })
    if (!r.ok) throw new Error(await readError(r, 'Gagal mengunggah logo.'))
    const j = await r.json()
    form.homepage_logo_path = j.path || form.homepage_logo_path
    notify('Logo berhasil diunggah.')
  } catch (e) {
    notify(e.message, 'error')
  } finally {
    logoUploading.value = false
    event.target.value = ''
  }
}

async function resetLogo() {
  try {
    const r = await fetch('/api/homepage-settings/reset-logo', { method: 'DELETE', headers: { Authorization: `Bearer ${token()}` } })
    if (!r.ok) throw new Error(await readError(r, 'Gagal mengembalikan logo.'))
    form.homepage_logo_path = ''
    notify('Logo dikembalikan ke default.')
  } catch (e) {
    notify(e.message, 'error')
  }
}

async function uploadMedia(event) {
  const file = event.target.files?.[0]
  if (!file) return
  mediaUploading.value = true
  try {
    const fd = new FormData()
    fd.append('file', file)
    const r = await fetch('/api/homepage-settings/upload-media', { method: 'POST', headers: { Authorization: `Bearer ${token()}` }, body: fd })
    if (!r.ok) throw new Error(await readError(r, 'Gagal mengunggah media.'))
    const j = await r.json()
    if (j.media) media.value.push(j.media)
    notify('Media berhasil diunggah.')
  } catch (e) {
    notify(e.message, 'error')
  } finally {
    mediaUploading.value = false
    event.target.value = ''
  }
}

function move(index, delta) {
  const target = index + delta
  if (target < 0 || target >= media.value.length) return
  const items = media.value.slice()
  const [item] = items.splice(index, 1)
  items.splice(target, 0, item)
  media.value = items
}
function toggleActive(index) {
  media.value[index].is_active = !media.value[index].is_active
}
async function saveMedia() {
  mediaSaving.value = true
  try {
    const payload = { media: media.value.map((m, i) => ({ id: m.id, sort_order: i, is_active: !!m.is_active })) }
    const r = await fetch('/api/homepage-settings/update-media', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token()}` },
      body: JSON.stringify(payload),
    })
    if (!r.ok) throw new Error(await readError(r, 'Gagal menyimpan urutan media.'))
    notify('Urutan & status media tersimpan.')
    await fetchData()
  } catch (e) {
    notify(e.message, 'error')
  } finally {
    mediaSaving.value = false
  }
}
async function removeMedia(item) {
  if (!confirm('Hapus media ini?')) return
  try {
    const r = await fetch(`/api/homepage-settings/media/${item.id}`, { method: 'DELETE', headers: { Authorization: `Bearer ${token()}` } })
    if (!r.ok) throw new Error(await readError(r, 'Gagal menghapus media.'))
    media.value = media.value.filter((m) => m.id !== item.id)
    notify('Media dihapus.')
  } catch (e) {
    notify(e.message, 'error')
  }
}

onMounted(fetchData)
</script>
