<template>
  <PublicLayout>
    <section class="w-full bg-surface-container-low px-gutter py-space-sm">
      <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-space-sm">
        <nav aria-label="Breadcrumb" class="flex items-center gap-space-xs font-label-sm text-label-sm text-on-surface-variant">
          <router-link to="/sma" class="hover:text-primary transition-colors">Beranda</router-link>
          <span class="material-symbols-outlined text-[14px]">chevron_right</span>
          <span class="text-primary font-semibold">Kontak &amp; Lokasi Kampus</span>
        </nav>
      </div>
    </section>

    <section class="w-full max-w-7xl mx-auto px-gutter pt-space-xl pb-space-lg">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-end">
        <div class="lg:col-span-8 flex flex-col gap-space-xs">
          <Reveal slow>
            <div class="inline-flex items-center gap-2 text-primary font-label-sm text-label-sm tracking-widest uppercase">
              <span class="w-6 h-0.5 bg-primary"></span>
              <span>Pusat Administrasi &amp; Pelayanan Publik</span>
            </div>
            <h1 class="font-display-lg text-display-lg text-on-surface font-bold tracking-tight mt-space-xs">
              Kunjungi Kampus Kami &amp; Hubungi Layanan Terpadu SMA Madani
            </h1>
          </Reveal>
        </div>
        <div class="lg:col-span-4 flex flex-col justify-end">
          <Reveal slow :delay="200" class="block">
            <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
              Kami menyambut hangat para calon wali santri dan mitra pendidikan. Silakan hubungi unit terkait untuk informasi lebih lanjut.
            </p>
          </Reveal>
        </div>
      </div>
    </section>

    <section class="w-full max-w-7xl mx-auto px-gutter pb-space-xl">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg">
        <div class="lg:col-span-5 flex flex-col gap-space-md">
          <Reveal slow :delay="100">
            <div class="bg-surface-container-lowest p-space-lg rounded-[28px] shadow-sm flex flex-col gap-space-sm relative overflow-hidden landing-card">
              <div class="flex items-center justify-between">
                <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline font-semibold">Sekretariat Utama</span>
                <span class="font-code-md text-code-md bg-secondary-fixed text-on-secondary-fixed px-space-xs py-0.5 rounded">SMA Madani Al-Aziziyah</span>
              </div>
              <div class="flex items-start gap-space-sm mt-1">
                <div class="p-2 bg-primary-container text-on-primary-container rounded flex-shrink-0 landing-float">
                  <span class="material-symbols-outlined text-[22px]">location_on</span>
                </div>
                <div class="flex flex-col">
                  <span class="font-title-md text-title-md text-on-surface font-semibold">Alamat Kampus</span>
                  <p class="font-body-md text-body-md text-on-surface-variant mt-1 leading-snug">{{ address }}</p>
                </div>
              </div>
            </div>
          </Reveal>

          <Reveal slow v-if="mapEmbed" :delay="200">
            <div class="bg-surface-container-lowest p-space-lg rounded-[28px] shadow-sm flex flex-col gap-space-sm landing-card">
              <div class="flex items-center justify-between">
                <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline font-semibold">Peta Lokasi Kampus</span>
                <span class="material-symbols-outlined text-outline text-[18px] landing-float-slow">map</span>
              </div>
              <iframe :src="mapEmbed" class="w-full h-64 border-0 rounded-[28px]" loading="lazy" title="Peta lokasi kampus SMA Madani Al-Aziziyah"></iframe>
            </div>
          </Reveal>

          <Reveal slow :delay="300">
            <div class="bg-surface-container-lowest p-space-lg rounded-[28px] shadow-sm flex flex-col gap-space-md landing-card">
              <div class="flex items-center justify-between">
                <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline font-semibold">Saluran Komunikasi Resmi</span>
                <span class="material-symbols-outlined text-outline text-[18px]">contact_phone</span>
              </div>
              <div v-if="channels.length" class="flex flex-col gap-space-xs">
                <a v-for="c in channels" :key="c.label" :href="c.href" target="_blank" rel="noopener" class="flex items-center gap-space-xs group">
                  <span class="p-2 bg-primary-container text-on-primary-container rounded flex-shrink-0">
                    <span class="material-symbols-outlined text-[20px]">{{ c.icon }}</span>
                  </span>
                  <span class="flex flex-col">
                    <span class="font-label-sm text-label-sm text-outline font-semibold">{{ c.label }}</span>
                    <span class="font-title-md text-title-md text-on-surface font-semibold group-hover:text-primary transition-colors duration-500">{{ c.value }}</span>
                  </span>
                </a>
              </div>
              <p v-else class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
                Nomor telepon, WhatsApp, dan alamat email resmi sekolah akan diumumkan segera setelah tersedia.
              </p>
              <div v-if="socials.length" class="flex flex-wrap gap-2 border-t border-outline-variant pt-space-md">
                <a v-for="s in socials" :key="s.k" :href="s.href" target="_blank" rel="noopener" class="landing-cta inline-flex items-center gap-1 px-3 py-1 bg-surface-container-low border border-outline-variant rounded-full font-label-sm text-label-sm font-semibold hover:bg-surface-container">
                  <span class="material-symbols-outlined text-[16px] text-primary">{{ s.icon }}</span>
                  <span class="uppercase">{{ s.k }}</span>
                </a>
              </div>
            </div>
          </Reveal>

          <Reveal slow :delay="400">
            <div class="bg-surface-container-highest p-space-lg rounded-[28px] shadow-sm flex flex-col gap-space-sm landing-card">
              <div class="flex items-center gap-space-xs text-on-surface font-title-md text-title-md font-semibold">
                <span class="material-symbols-outlined text-primary text-[20px]">schedule</span>
                <span>Jam Pelayanan</span>
              </div>
              <p class="font-body-sm text-body-sm text-on-surface-variant">
                Jam layanan administrasi sekolah akan diumumkan bersamaan dengan informasi kontak resmi.
              </p>
            </div>
          </Reveal>
        </div>

        <div class="lg:col-span-7 flex flex-col">
          <Reveal slow :delay="240" class="h-full">
            <div class="bg-surface-container-lowest p-space-xl rounded-[28px] shadow-md flex flex-col gap-space-md h-full landing-card">
              <div class="flex flex-col gap-1">
                <div class="flex items-center justify-between">
                  <span class="font-headline-md text-headline-md text-on-surface font-bold">Formulir Pertanyaan &amp; Janji Temu Kunjungan</span>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                  Silakan ajukan pertanyaan melalui kanal resmi sekolah ketika telah diumumkan, atau kunjungi langsung sekretariat pada jam layanan.
                </p>
              </div>
              <EmptyState
                icon="forum"
                title="Modul pengiriman pesan belum tersedia"
                description="Fitur formulir pertanyaan & janji temu belum terhubung ke backend. Untuk saat ini silakan hubungi sekretariat sekolah melalui kanal resmi."
                :show-gap="true"
              >
              </EmptyState>
            </div>
          </Reveal>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useSiteSettings } from '../composables/useSiteSettings'
import EmptyState from '../Components/EmptyState.vue'
import PublicLayout from '../Components/PublicLayout.vue'
import Reveal from '../Components/Reveal.vue'

const { get, load } = useSiteSettings()

onMounted(() => { load() })

const address = computed(() => get('address', 'Jl. T. Imum Hamzah Lr. Dayah, Lampeuneurut Ujong Blang, Kec. Darul Imarah, Kab. Aceh Besar'))

function digits(v) { return String(v || '').replace(/[^\d+]/g, '') }

const channels = computed(() => {
  const out = []
  const phone = get('phone', '')
  const pstn = get('pstn', '')
  const wa = get('wa_helpdesk', '')
  const email = get('email', '')
  if (phone) out.push({ icon: 'call', label: 'Telepon', value: phone, href: `tel:${digits(phone)}` })
  if (pstn) out.push({ icon: 'call', label: 'PSTN / Fax', value: pstn, href: `tel:${digits(pstn)}` })
  if (wa) out.push({ icon: 'chat', label: 'WhatsApp', value: wa, href: `https://wa.me/${digits(wa)}` })
  if (email) out.push({ icon: 'mail', label: 'Surel Resmi', value: email, href: `mailto:${email}` })
  return out
})

const socials = computed(() => {
  const items = [['instagram', 'camera'], ['youtube', 'play_circle'], ['tiktok', 'music_note'], ['facebook', 'thumb_up']]
  return items
    .map(([k, icon]) => {
      const v = get(k, '')
      if (!v) return null
      const href = /^https?:\/\//.test(v) ? v : `https://${k}.com/${v.replace(/^@/, '')}`
      return { k, icon, href }
    })
    .filter(Boolean)
})

const mapLat = computed(() => get('map_lat', ''))
const mapLng = computed(() => get('map_lng', ''))
const mapEmbed = computed(() => {
  const lat = parseFloat(mapLat.value)
  const lng = parseFloat(mapLng.value)
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return ''
  const d = 0.004
  return `https://www.openstreetmap.org/export/embed.html?bbox=${lng - d}%2C${lat - d}%2C${lng + d}%2C${lat + d}&layer=mapnik&marker=${lat}%2C${lng}`
})
</script>