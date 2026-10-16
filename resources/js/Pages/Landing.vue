<template>
  <PublicLayout>
    <!-- Hero Section -->
    <section class="w-full bg-surface border-b border-outline-variant px-gutter-desktop py-space-xl">
      <div class="max-w-7xl mx-auto flex flex-col gap-space-xl">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-xl items-start">
          <div class="lg:col-span-7 flex flex-col gap-space-md">
            <Reveal slow>
              <div class="landing-float inline-flex items-center gap-space-xs px-space-sm py-1 bg-surface-container-highest border border-outline-variant shadow-sm w-fit">
                <span class="material-symbols-outlined text-primary text-[18px]">verified_user</span>
                <span class="font-label-sm text-label-sm text-on-surface uppercase tracking-wider">Satuan Pendidikan Menengah · Terakreditasi B (BAN-S/M)</span>
              </div>
            </Reveal>
            <Reveal slow :delay="120">
              <h1 class="font-display-lg text-display-lg text-on-surface leading-tight tracking-tight">{{ heroTitle }}</h1>
            </Reveal>
            <Reveal slow :delay="240">
              <p class="font-body-lg text-body-lg text-on-surface-variant leading-relaxed max-w-2xl">{{ heroBody }}</p>
            </Reveal>
            <Reveal slow :delay="360">
              <div class="flex flex-wrap items-center gap-space-md pt-space-xs">
                <router-link to="/sma/ppdb" class="landing-cta landing-glow inline-flex items-center justify-center gap-space-xs px-space-lg py-space-sm bg-primary text-on-primary font-title-md text-title-md rounded-full shadow-sm hover:bg-primary-hover hover:shadow-md">
                  <span>Daftar PPDB Online</span>
                  <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </router-link>
                <a href="#pendaftaran" class="landing-cta inline-flex items-center justify-center gap-space-xs px-space-lg py-space-sm bg-surface-container-lowest text-on-surface font-title-md text-title-md rounded-full shadow-sm hover:bg-surface-container hover:shadow-md">
                  <span class="material-symbols-outlined text-[18px]">how_to_reg</span>
                  <span>Cara Mendaftar</span>
                </a>
                <router-link to="/sma/fasilitas" class="inline-flex items-center gap-1 font-title-md text-title-md text-on-surface-variant hover:text-primary transition-colors duration-500 underline decoration-1 underline-offset-4">
                  <span>Jelajahi Fasilitas</span>
                  <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </router-link>
              </div>
              <div class="pt-space-md flex flex-wrap items-center gap-space-lg">
                <div class="landing-float-slow flex items-center gap-2"><span class="material-symbols-outlined text-primary text-[20px]">menu_book</span><span class="font-label-md text-label-md text-on-surface uppercase">Tahfizhul Qur'an</span></div>
                <div class="landing-float-slow flex items-center gap-2" style="animation-delay: 1.8s"><span class="material-symbols-outlined text-primary text-[20px]">science</span><span class="font-label-md text-label-md text-on-surface uppercase">Penguatan Sains &amp; Teknologi</span></div>
                <div class="landing-float-slow flex items-center gap-2" style="animation-delay: 3.2s"><span class="material-symbols-outlined text-primary text-[20px]">translate</span><span class="font-label-md text-label-md text-on-surface uppercase">Arab &amp; Inggris Aktif</span></div>
              </div>
            </Reveal>
          </div>
          <div class="lg:col-span-5 flex flex-col gap-space-md">
            <Reveal slow :delay="200">
              <div class="relative bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-2 overflow-hidden landing-glow">
                <div class="w-full h-80 bg-surface-container relative overflow-hidden border border-outline-variant landing-img-wrap">
                  <img class="w-full h-full object-cover landing-ken" :src="heroImage" alt="Kegiatan belajar santri" />
                  <div class="absolute bottom-2 left-2 right-2 bg-surface-container-lowest/95 border border-outline-variant p-space-xs flex items-center justify-between">
                    <div>
                      <p class="font-label-sm text-label-sm text-on-surface uppercase font-bold">Suasana Belajar Santri</p>
                      <p class="font-code-md text-code-md text-on-surface-variant">Kegiatan pembelajaran di lingkungan pesantren</p>
                    </div>
                    <span class="material-symbols-outlined text-primary landing-float">school</span>
                  </div>
                </div>
              </div>
            </Reveal>
            <div class="grid grid-cols-2 gap-space-md">
              <Reveal slow :delay="320" class="h-full">
                <div class="landing-card h-full bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-2 flex flex-col gap-1">
                  <div class="w-full h-28 bg-surface-container overflow-hidden border border-outline-variant landing-img-wrap">
                    <img class="w-full h-full object-cover" src="/images/landing/achievement.jpeg" alt="Kegiatan santri" />
                  </div>
                  <p class="font-label-sm text-label-sm text-on-surface font-semibold truncate pt-1">Halaqah Tahfizh</p>
                  <span class="font-code-md text-code-md text-primary">Rutinitas harian santri</span>
                </div>
              </Reveal>
              <Reveal slow :delay="440" class="h-full">
                <div class="landing-card h-full bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-2 flex flex-col gap-1">
                  <div class="w-full h-28 bg-surface-container overflow-hidden border border-outline-variant landing-img-wrap">
                    <img class="w-full h-full object-cover" src="/images/landing/hero-campus.jpeg" alt="Eksterior kampus Dayah Madani Al-Aziziyah" />
                  </div>
                  <p class="font-label-sm text-label-sm text-on-surface font-semibold truncate pt-1">Kawasan Kampus</p>
                  <span class="font-code-md text-code-md text-primary">Dayah Madani Al-Aziziyah</span>
                </div>
              </Reveal>
            </div>
          </div>
        </div>

        <!-- Institutional Indicator Bar -->
        <div class="w-full bg-surface-container-lowest border border-outline-variant shadow-sm rounded-[28px] p-space-md grid grid-cols-2 md:grid-cols-4 gap-space-md divide-y md:divide-y-0 md:divide-x divide-outline-variant">
          <Reveal slow v-for="(stat, i) in heroStats" :key="stat.label" :delay="i * 140">
            <div class="flex flex-col gap-0.5 px-space-xs" :class="stat.mobilePad">
              <span class="font-code-md text-code-md uppercase font-bold tracking-wider" :class="stat.accent">{{ stat.kicker }}</span>
              <span class="font-headline-lg text-headline-lg text-on-surface font-bold">{{ stat.value }}</span>
              <span class="font-body-sm text-body-sm text-on-surface-variant">{{ stat.caption }}</span>
            </div>
          </Reveal>
        </div>
      </div>
    </section>

    <!-- Pilar Kurikulum & Program Unggulan -->
    <section class="w-full bg-surface-container-low border-b border-outline-variant px-gutter-desktop py-space-xl">
      <div class="max-w-7xl mx-auto flex flex-col gap-space-lg">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-space-sm border-b border-outline-variant pb-space-sm">
          <Reveal slow>
            <div>
              <span class="font-code-md text-code-md text-primary uppercase font-bold tracking-wider">Fondasi Pendidikan Pesantren</span>
              <h2 class="font-headline-lg text-headline-lg text-on-surface">{{ profilTitle }}</h2>
            </div>
          </Reveal>
          <Reveal slow :delay="140">
            <p class="font-body-sm text-body-sm text-on-surface-variant max-w-md">{{ profilBody }}</p>
          </Reveal>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-space-lg">
          <Reveal slow v-for="(p, i) in pillars" :key="p.no" :delay="i * 160" class="h-full">
            <div class="landing-card h-full bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm flex flex-col justify-between">
              <div class="p-space-lg border-b border-outline-variant">
                <div class="flex items-center justify-between pb-space-sm">
                  <span class="landing-float w-10 h-10 border border-outline-variant rounded-full flex items-center justify-center font-bold text-on-surface" :class="p.chipBg">{{ p.no }}</span>
                  <span class="font-code-md text-code-md px-2 py-0.5 bg-surface-container border border-outline-variant rounded">{{ p.badge }}</span>
                </div>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-2">{{ p.title }}</h3>
                <p class="font-body-sm text-body-sm text-on-surface-variant mb-space-md">{{ p.desc }}</p>
                <ul class="flex flex-col gap-space-xs font-body-sm text-body-sm text-on-surface">
                  <li v-for="f in p.features" :key="f" class="flex items-start gap-2">
                    <span class="material-symbols-outlined text-[16px] mt-0.5" :class="p.iconColor">check_circle</span>
                    <span>{{ f }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </Reveal>
        </div>
      </div>
    </section>

    <!-- Ritme Kehidupan Santri -->
    <section class="w-full bg-surface border-b border-outline-variant px-gutter-desktop py-space-xl">
      <div class="max-w-7xl mx-auto flex flex-col gap-space-lg">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-space-sm border-b border-outline-variant pb-space-sm">
          <Reveal slow>
            <div>
              <span class="font-code-md text-code-md text-primary uppercase font-bold tracking-wider">Disiplin &amp; Rutinitas</span>
              <h2 class="font-headline-lg text-headline-lg text-on-surface">Jadwal Aktivitas Harian Santri</h2>
            </div>
          </Reveal>
          <Reveal slow :delay="140">
            <span class="font-code-md text-code-md px-space-sm py-1 bg-surface-container border border-outline-variant rounded text-on-surface-variant">Rutinitas terpadu pesantren &amp; formal</span>
          </Reveal>
        </div>
        <Reveal slow :delay="200">
          <div class="bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm overflow-hidden">
            <div class="bg-surface-container px-space-md py-space-sm border-b border-outline-variant">
              <span class="font-title-md text-title-md text-on-surface font-semibold">Pembagian Waktu Sehari-hari</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-outline-variant bg-surface-container-low font-label-sm text-label-sm text-on-surface uppercase tracking-wider">
                    <th class="py-space-xs px-space-md">Rentang Waktu</th>
                    <th class="py-space-xs px-space-md">Aktivitas</th>
                  </tr>
                </thead>
                <tbody class="font-body-sm text-body-sm divide-y divide-outline-variant">
                  <tr
                    v-for="(row, i) in schedule"
                    :key="row.time"
                    class="stagger-slow hover:bg-surface-container-low transition-colors duration-500"
                    :style="{ animationDelay: `${i * 90}ms` }"
                  >
                    <td class="py-space-xs px-space-md font-code-md text-code-md font-semibold text-primary whitespace-nowrap">{{ row.time }}</td>
                    <td class="py-space-xs px-space-md">{{ row.activity }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </Reveal>
      </div>
    </section>

    <!-- Prestasi & Pengakuan -->
    <section class="w-full bg-surface-container-low border-b border-outline-variant px-gutter-desktop py-space-xl">
      <div class="max-w-7xl mx-auto flex flex-col gap-space-lg">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-space-sm border-b border-outline-variant pb-space-sm">
          <Reveal slow>
            <div>
              <span class="font-code-md text-code-md text-primary uppercase font-bold tracking-wider">Rekam Jejak &amp; Pengakuan</span>
              <h2 class="font-headline-lg text-headline-lg text-on-surface">Prestasi Madani Al Aziziyah</h2>
            </div>
          </Reveal>
          <Reveal slow :delay="140">
            <router-link to="/sma/prestasi" class="font-title-md text-title-md text-primary hover:text-primary-hover inline-flex items-center gap-1 underline decoration-2 transition-colors duration-500">
              <span>Lihat Halaman Prestasi</span>
              <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </router-link>
          </Reveal>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-space-md">
          <Reveal slow v-for="(a, i) in awards" :key="a.tag + a.title" :delay="i * 150" class="h-full">
            <div class="landing-card h-full bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-md flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="font-code-md text-code-md px-2 py-0.5 border border-outline-variant rounded text-on-surface font-bold" :class="a.badgeClass">{{ a.tag }}</span>
                  <span v-if="a.date" class="font-code-md text-code-md text-on-surface-variant">{{ a.date }}</span>
                </div>
                <h4 class="font-headline-sm text-headline-sm text-on-surface mb-2">{{ a.title }}</h4>
                <p class="font-body-sm text-body-sm text-on-surface-variant mb-space-sm">{{ a.desc }}</p>
              </div>
              <div class="border-t border-outline-variant pt-2 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] landing-float-slow" :class="a.iconClass">{{ a.icon }}</span>
                <span class="font-label-sm text-label-sm text-on-surface uppercase font-semibold">{{ a.level }}</span>
              </div>
            </div>
          </Reveal>
        </div>
      </div>
    </section>

    <!-- Penerimaan Peserta Didik Baru -->
    <section class="w-full bg-surface border-b border-outline-variant px-gutter-desktop py-space-xl" id="pendaftaran">
      <div class="max-w-7xl mx-auto flex flex-col gap-space-lg">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-space-sm border-b border-outline-variant pb-space-sm">
          <Reveal slow>
            <div>
              <span class="font-code-md text-code-md text-primary uppercase font-bold tracking-wider">Mekanisme Seleksi Penerimaan</span>
              <h2 class="font-headline-lg text-headline-lg text-on-surface">Penerimaan Peserta Didik Baru (PPDB)</h2>
            </div>
          </Reveal>
          <Reveal slow :delay="140">
            <div class="flex items-center gap-2">
              <span class="landing-pulse w-3 h-3 rounded-full bg-primary inline-block"></span>
              <span class="font-body-sm text-body-sm font-semibold text-on-surface">Pendaftaran Daring</span>
            </div>
          </Reveal>
        </div>
        <Reveal slow :delay="220">
          <div class="w-full bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-xl flex flex-col items-center text-center gap-space-sm landing-card">
            <span class="landing-float w-14 h-14 rounded-full bg-primary-fixed text-primary flex items-center justify-center">
              <span class="material-symbols-outlined text-[28px]">assignment_turned_in</span>
            </span>
            <h3 class="font-headline-md text-headline-md text-on-surface font-semibold">Informasi Alur &amp; Gelombang PPDB</h3>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl">Jadwal gelombang dan alur pendaftaran ditetapkan oleh panitia sekolah dan diumumkan melalui laman ini. Periksa kembali halaman ini secara berkala untuk informasi terbaru.</p>
            <router-link to="/sma/ppdb" class="landing-cta landing-glow inline-flex items-center justify-center gap-space-xs px-space-lg py-space-sm bg-primary text-on-primary font-title-md text-title-md rounded-full shadow-sm hover:bg-primary-hover hover:shadow-md">
              <span>Buka Halaman PPDB</span>
              <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </router-link>
          </div>
        </Reveal>
        <Reveal slow :delay="340">
          <div class="w-full bg-surface-container border border-outline-variant rounded-[28px] shadow-sm p-space-lg flex flex-col md:flex-row items-center justify-between gap-space-md landing-card">
            <div class="flex items-center gap-space-md">
              <div class="landing-float w-12 h-12 bg-primary text-on-primary rounded-full border border-outline-variant flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[28px]">support_agent</span>
              </div>
              <div>
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold">Butuh Informasi Pendaftaran?</h3>
                <p class="font-body-sm text-body-sm text-on-surface-variant">Sekretariat sekolah siap membantu tanya-jawab seputar alur seleksi dan mekanisme pendaftaran.</p>
              </div>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-space-sm shrink-0">
              <router-link to="/sma/kontak" class="landing-cta inline-flex items-center gap-space-xs px-space-md py-space-sm bg-surface-container-lowest text-on-surface font-title-md text-title-md rounded-full shadow-sm hover:bg-surface hover:shadow-md">
                <span class="material-symbols-outlined text-primary text-[18px]">mail</span>
                <span>Hubungi Sekretariat</span>
              </router-link>
              <router-link to="/sma/ppdb" class="landing-cta inline-flex items-center gap-space-xs px-space-md py-space-sm bg-primary text-on-primary font-title-md text-title-md rounded-full shadow-sm hover:bg-primary-hover hover:shadow-md">
                <span>Daftar Sekarang</span>
                <span class="material-symbols-outlined text-[18px]">login</span>
              </router-link>
            </div>
          </div>
        </Reveal>
      </div>
    </section>

    <!-- CTA Penutup -->
    <section class="w-full bg-surface border-b border-outline-variant px-gutter-desktop py-space-xl">
      <div class="max-w-7xl">
        <Reveal slow>
          <div class="w-full bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-xl flex flex-col lg:flex-row items-center justify-between gap-space-lg landing-glow">
            <div class="flex flex-col gap-space-xs max-w-2xl">
              <span class="font-code-md text-code-md text-primary uppercase font-bold tracking-wider">Langkah Pertama Masa Depan Gemilang</span>
              <h2 class="font-headline-lg text-headline-lg text-on-surface font-bold">Bergabunglah Menjadi Bagian dari Keluarga Besar SMA Madani Al Aziziyah.</h2>
              <p class="font-body-md text-body-md text-on-surface-variant">Lengkapi pendaftaran secara daring dan pantau pengumuman resmi sekolah untuk informasi jadwal, berkas, dan tahapan seleksi.</p>
            </div>
            <div class="flex flex-col sm:flex-row items-center gap-space-md w-full lg:w-auto shrink-0">
              <router-link to="/sma/ppdb" class="landing-cta landing-glow w-full sm:w-auto inline-flex items-center justify-center gap-space-xs px-space-lg py-space-sm bg-primary text-on-primary font-title-md text-title-md rounded-full shadow-sm hover:bg-primary-hover hover:shadow-md">
                <span>Registrasi Santri Baru</span>
                <span class="material-symbols-outlined text-[18px]">how_to_reg</span>
              </router-link>
              <router-link to="/sma/kontak" class="landing-cta w-full sm:w-auto inline-flex items-center justify-center gap-space-xs px-space-lg py-space-sm bg-surface-container-low text-on-surface font-title-md text-title-md border border-outline-variant rounded-full shadow-sm hover:bg-surface-container hover:shadow-md">
                <span class="material-symbols-outlined text-[18px]">mail</span>
                <span>Hubungi Sekretariat</span>
              </router-link>
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import PublicLayout from '../Components/PublicLayout.vue'
import Reveal from '../Components/Reveal.vue'
import { useSiteSettings } from '../composables/useSiteSettings'
import { useSeo } from '../composables/useSeo.js'

const { load, asset } = useSiteSettings()
const sections = ref([])

function sec(key) {
  if (!sections.value.length) return null
  const s = sections.value.find((x) => x.key === key)
  return s && s.is_visible ? s : null
}

const hero = computed(() => sec('hero'))
const heroTitle = computed(() => hero.value?.title || 'Membina Generasi Qurani Berwawasan Sains Global dan Berakhlak Mulia.')
const heroBody = computed(() => hero.value?.body || "Pendidikan menengah atas berbasis pesantren yang memadukan kurikulum nasional dengan pembinaan tahfizhul Qur'an, penguatan sains dan teknologi, serta pembiasaan aktif berbahasa Arab dan Inggris dalam kehidupan sehari-hari santri.")
const heroImage = computed(() => (hero.value?.image_path ? asset(hero.value.image_path) : '/images/landing/lab-activity.jpeg'))

const profil = computed(() => sec('profil'))
const profilTitle = computed(() => profil.value?.title || 'Trilogi Kurikulum SMA Madani Al Aziziyah')
const profilBody = computed(() => profil.value?.body || "Perpaduan kedalaman ilmu-ilmu syar'i, ketajaman nalar sains modern, dan kemandirian kepemimpinan santri.")

onMounted(async () => {
  useSeo({ title: 'Beranda SMA', description: 'Situs resmi SMA Madani Al-Aziziyah.', url: location.href, brand: 'SMA Madani Al-Aziziyah' })
  await load()
  try {
    const r = await fetch('/api/landing-sections/public')
    if (r.ok) sections.value = await r.json()
  } catch { /* konten fallback tetap tampil saat API tidak tersedia */ }
})

const heroStats = [
  { kicker: 'Status Akreditasi', value: 'B (BAN-S/M)', caption: 'SMP & SMA Madani Al-Aziziyah', accent: 'text-primary', mobilePad: '' },
  { kicker: 'NPSN SMA', value: '69986030', caption: 'Nomor Pokok Sekolah Nasional', accent: 'text-secondary', mobilePad: 'pt-space-xs md:pt-0' },
  { kicker: 'Tahun Berdiri', value: '2011', caption: 'Cabang resmi Dayah MUDI sejak 2015', accent: 'text-tertiary', mobilePad: 'pt-space-xs md:pt-0' },
  { kicker: 'Pilar Kurikulum', value: '3 Pilar', caption: 'Tahfizh · Sains · Bilingual', accent: 'text-primary-container', mobilePad: 'pt-space-xs md:pt-0' },
]

const pillars = [
  {
    no: '01', chipBg: 'bg-primary-fixed', iconColor: 'text-primary', badge: 'Jalur Syar\'iyyah',
    title: 'Tahfizhul Qur\'an & Dirasah Islamiyyah',
    desc: 'Pembinaan hafalan Al-Qur\'an dan penguasaan ilmu-ilmu syar\'i yang berakar pada tradisi dayah salafiyah dengan halaqah rutin sebagai pembiasaan harian santri.',
    features: ['Halaqah tahfizh & tahsin Al-Qur\'an rutin', 'Kajian kitab kuning (fiqih Syafi\'iyyah & aqidah Ahlussunnah Waljama\'ah)', 'Pembiasaan ibadah dan akhlak mulia santri'],
  },
  {
    no: '02', chipBg: 'bg-secondary-fixed', iconColor: 'text-secondary', badge: 'Jalur Sains & Teknologi',
    title: 'Sains, Riset & Rekayasa Komputasi',
    desc: 'Penerapan kurikulum nasional dengan penguatan bidang sains, matematika, dan teknologi sebagai bekal santri mengembangkan potensi akademik dan keterampilan abad 21.',
    features: ['Kurikulum nasional yang sesuai standar pendidikan', 'Penguatan sains, matematika, dan teknologi informasi', 'Pembinaan berpikir kritis, riset, dan kreativitas'],
  },
  {
    no: '03', chipBg: 'bg-tertiary-fixed', iconColor: 'text-tertiary', badge: 'Jalur Bahasa & Kepemimpinan',
    title: 'Bilingual Aktif & Kepemimpinan Santri',
    desc: 'Pembiasaan komunikasi aktif bahasa Arab dan Inggris dalam keseharian santri, ditunjang latihan kepemimpinan dan kemandirian melalui organisasi serta kegiatan asrama.',
    features: ['Komunikasi aktif bahasa Arab (Fushah) & bahasa Inggris', 'Latihan kepemimpinan dan kemandirian santri', 'Organisasi santri & kegiatan pengembangan diri'],
  },
]

const schedule = [
  { time: '04:30 – 06:00', activity: 'Subuh berjamaah, dzikir, dan halaqah tahfizh-tahsin' },
  { time: '06:00 – 07:15', activity: 'Bersih diri, mandi, sarapan, dan persiapan sekolah' },
  { time: '07:30 – 13:30', activity: 'Pembelajaran formal (sekolah)' },
  { time: '13:30 – 16:00', activity: 'Makan siang dan istirahat' },
  { time: '16:30 – 17:30', activity: 'Kajian kitab sore' },
  { time: '17:30 – 18:30', activity: 'Persiapan shalat Maghrib' },
  { time: '18:30 – 22:00', activity: 'Maghrib berjamaah, makan malam, dan kajian kitab malam' },
  { time: '22:00 – 04:30', activity: 'Istirahat / tidur' },
]

const awards = [
  {
    tag: 'AKREDITASI', badgeClass: 'bg-primary-fixed', icon: 'verified', iconClass: 'text-primary',
    title: 'Terakreditasi B — SMP Madani Al-Aziziyah',
    desc: 'SMP Madani Al-Aziziyah meraih peringkat akreditasi B dari Badan Akreditasi Nasional Sekolah/Madrasah (BAN-S/M).', level: 'Akreditasi Nasional',
  },
  {
    tag: 'AKREDITASI', badgeClass: 'bg-secondary-fixed', icon: 'verified', iconClass: 'text-secondary',
    title: 'Terakreditasi B — SMA Madani Al-Aziziyah',
    desc: 'SMA Madani Al-Aziziyah meraih peringkat akreditasi B dari Badan Akreditasi Nasional Sekolah/Madrasah (BAN-S/M).', level: 'Akreditasi Nasional',
  },
  {
    tag: 'KEPENDIDIKAN', badgeClass: 'bg-tertiary-fixed', icon: 'school', iconClass: 'text-tertiary',
    title: 'Doktoral "Terpuji (Cumlaude)" Dr. Tgk. H. Muhammad Hatta, Lc., M.Ed.',
    desc: 'Pengasuh Dayah Madani Al-Aziziyah meraih gelar Doktor dari UIN Ar-Raniry Banda Aceh dengan predikat Terpuji (Cumlaude).', level: 'Pendidikan',
  },
  {
    tag: 'KEMANUSIAAN', badgeClass: 'bg-primary-fixed-dim', icon: 'volunteer_activism', iconClass: 'text-primary',
    title: 'Kontribusi Kemanusiaan untuk Palestina',
    desc: 'Kegiatan dukungan kemanusiaan untuk Palestina melalui Majelis Permusyawaratan Ulama (MPU) Aceh.', level: 'Sosial & Kemanusiaan',
  },
]
</script>
