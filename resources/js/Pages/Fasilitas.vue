<template>
  <PublicLayout>
    <!-- Breadcrumb & Top Utility Header -->
    <div class="w-full bg-surface-container-low px-gutter py-space-sm">
      <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-space-xs">
        <div class="flex items-center gap-space-xs font-label-sm text-label-sm text-on-surface-variant">
          <router-link to="/sma" class="hover:text-primary transition-colors">Beranda</router-link>
          <span class="material-symbols-outlined text-[13px] text-outline" aria-hidden="true">chevron_right</span>
          <span class="text-on-surface font-semibold">Fasilitas &amp; Kehidupan Santri</span>
        </div>
        <div class="flex items-center gap-space-md">
          <span class="inline-flex items-center gap-1 bg-surface-container-lowest px-2 py-0.5 rounded-full font-code-md text-code-md shadow-sm text-on-surface">
            <span class="material-symbols-outlined text-[15px] text-primary" aria-hidden="true">domain</span>
            SMP &amp; SMA Madani Al-Aziziyah
          </span>
        </div>
      </div>
    </div>

    <!-- Editorial Headpiece Section -->
    <section class="max-w-7xl mx-auto w-full px-gutter pt-space-xl pb-space-lg">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-end">
        <div class="lg:col-span-8 flex flex-col gap-space-xs">
          <Reveal slow>
            <span class="font-label-sm text-label-sm text-primary uppercase tracking-widest font-bold">Fasilitas &amp; Kehidupan Santri</span>
            <h1 class="font-display-lg text-display-lg text-on-surface leading-tight tracking-tight">Fasilitas Terpadu untuk Pendidikan &amp; Kehidupan Santri</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant max-w-3xl pt-space-xs leading-relaxed">Fasilitas Dayah Madani Al-Aziziyah dirancang untuk mendukung pola kehidupan santri mukim yang menyeimbangkan pendidikan formal, pengajian kitab kuning, ibadah, literasi, serta pembinaan karakter.</p>
          </Reveal>
        </div>
        <div class="lg:col-span-4">
          <Reveal slow :delay="240" class="bg-primary text-on-primary p-space-md sm:p-space-lg rounded shadow-md relative overflow-hidden landing-card h-full">
            <div class="absolute -right-6 -top-6 w-28 h-28 rounded-full bg-on-primary/10 pointer-events-none landing-float-slow" aria-hidden="true"></div>
            <div class="relative z-10">
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px] text-secondary-fixed" aria-hidden="true">hotel_class</span>
                <span class="font-label-sm text-label-sm font-bold uppercase tracking-widest opacity-80">Kehidupan Santri</span>
              </div>
              <h4 class="font-headline-sm text-headline-sm font-bold leading-snug mt-1.5">Pendidikan Terpadu &amp; Kehidupan Mukim</h4>
              <p class="font-body-sm text-body-sm text-on-primary/80 leading-relaxed mt-1">Santri menjalani pendidikan formal dan kepesantrenan dalam lingkungan asrama dengan pembagian waktu yang terstruktur.</p>
            </div>
          </Reveal>
        </div>
      </div>
    </section>

    <!-- Section Navigation Strip -->
    <section class="max-w-7xl mx-auto w-full px-gutter pb-space-lg">
      <div class="bg-surface-container-lowest p-space-xs rounded shadow-sm">
        <div class="flex items-center flex-wrap gap-1.5">
          <button
            v-for="s in sectionNav"
            :key="s.id"
            type="button"
            @click="scrollToSection(s.id)"
            class="px-space-md py-1.5 rounded-full font-title-md text-body-md font-semibold transition-[background-color,color,box-shadow] duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
            :class="activeSection === s.id ? 'bg-primary text-on-primary shadow-sm hover:bg-primary-hover' : 'bg-surface-container-low text-on-surface hover:bg-surface-container'"
          >
            {{ s.label }}
          </button>
        </div>
      </div>
    </section>

    <!-- Section 1: Prestasi -->
    <section id="prestasi" data-section="prestasi" class="max-w-7xl mx-auto w-full px-gutter py-space-lg scroll-mt-28">
      <div class="bg-surface-container-lowest p-space-lg sm:p-space-xl shadow-md">
        <Reveal slow>
          <div class="border-b border-outline-variant pb-space-lg mb-space-lg">
            <span class="font-code-md text-code-md text-primary font-semibold">PRESTASI</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mt-1">Prestasi &amp; Pencapaian</h2>
            <p class="font-body-sm text-body-sm text-on-surface-variant max-w-3xl mt-2 leading-relaxed">Prestasi lingkungan Dayah Madani Al-Aziziyah mencakup pencapaian kelembagaan, akademik pimpinan, serta kontribusi sosial dan kemanusiaan.</p>
          </div>
        </Reveal>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg">
          <Reveal slow v-for="(a, i) in achievements" :key="a.title" :delay="i * 140" class="h-full">
            <article class="h-full landing-card bg-surface-container-lowest rounded shadow-md p-space-md sm:p-space-lg border border-outline-variant flex flex-col gap-space-sm">
              <span class="w-11 h-11 rounded-full bg-primary-fixed text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[24px]" aria-hidden="true">{{ a.icon }}</span>
              </span>
              <div>
                <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface">{{ a.title }}</h3>
                <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mt-1.5">{{ a.desc }}</p>
              </div>
              <div v-if="a.units" class="mt-auto pt-space-sm flex flex-col gap-space-xs">
                <a
                  v-for="u in a.units"
                  :key="u.label"
                  :href="u.href"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="rounded-xl bg-surface-container-low px-space-md py-1.5 flex items-center justify-between gap-2 hover:bg-surface-container transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                >
                  <span class="font-label-sm text-label-sm font-semibold text-on-surface">{{ u.label }}</span>
                  <span class="shrink-0 inline-flex items-center gap-1">
                    <span class="font-code-md text-code-md text-secondary font-bold">Akreditasi B</span>
                    <span class="material-symbols-outlined text-[15px] text-primary" aria-hidden="true">open_in_new</span>
                  </span>
                </a>
              </div>
            </article>
          </Reveal>
        </div>
      </div>
    </section>

    <!-- Section 2: Fasilitas -->
    <section id="fasilitas" data-section="fasilitas" class="max-w-7xl mx-auto w-full px-gutter py-space-lg scroll-mt-28">
      <div class="bg-surface-container-lowest p-space-lg sm:p-space-xl shadow-md">
        <Reveal slow>
          <div class="border-b border-outline-variant pb-space-lg mb-space-lg">
            <span class="font-code-md text-code-md text-primary font-semibold">FASILITAS</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mt-1">Fasilitas Dayah Madani Al-Aziziyah</h2>
            <p class="font-body-sm text-body-sm text-on-surface-variant max-w-3xl mt-2 leading-relaxed">Fasilitas terintegrasi mendukung pola kehidupan santri mukim yang memadukan sekolah formal, pengajian kitab kuning, ibadah, literasi, dan pembinaan kehidupan sehari-hari.</p>
          </div>
        </Reveal>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-lg">
          <Reveal slow v-for="(f, i) in facilities" :key="f.title" :delay="i * 140" class="h-full">
            <article class="h-full landing-card bg-surface-container-low p-space-md rounded shadow-sm flex flex-col gap-space-sm">
              <span class="w-11 h-11 rounded-full bg-primary-fixed text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[24px]" aria-hidden="true">{{ f.icon }}</span>
              </span>
              <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface leading-snug">{{ f.title }}</h3>
              <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">{{ f.desc }}</p>
            </article>
          </Reveal>
        </div>
      </div>
    </section>

    <!-- Section 3: Pola Kehidupan Santri -->
    <section id="kehidupan" data-section="kehidupan" class="max-w-7xl mx-auto w-full px-gutter py-space-lg scroll-mt-28">
      <div class="bg-surface-container-lowest p-space-lg sm:p-space-xl shadow-md">
        <Reveal slow>
          <div class="border-b border-outline-variant pb-space-lg mb-space-lg">
            <span class="font-code-md text-code-md text-primary font-semibold">POLA KEHIDUPAN</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mt-1">Kehidupan Santri Mukim</h2>
            <p class="font-body-sm text-body-sm text-on-surface-variant max-w-3xl mt-2 leading-relaxed">Sebagai dayah terpadu, kehidupan santri diatur dengan tata tertib dan pembagian waktu yang menyeimbangkan pendidikan formal, pengajian, ibadah, istirahat, dan pembinaan karakter.</p>
          </div>
        </Reveal>
        <Reveal slow>
          <div class="bg-primary text-on-primary rounded shadow-md p-space-md sm:p-space-lg relative overflow-hidden">
            <div class="absolute -left-6 -top-8 w-40 h-40 rounded-full bg-on-primary/10 pointer-events-none" aria-hidden="true"></div>
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center gap-space-sm">
              <span class="w-12 h-12 rounded-full bg-on-primary/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[26px]" aria-hidden="true">home</span>
              </span>
              <div>
                <span class="font-label-sm text-label-sm font-bold uppercase tracking-widest opacity-80">Wajib Mukim</span>
                <p class="font-title-md text-title-md text-on-primary font-medium leading-snug mt-0.5">Seluruh siswa SMP dan SMA terintegrasi wajib tinggal di asrama pesantren selama masa pendidikan.</p>
              </div>
            </div>
          </div>
        </Reveal>
      </div>
    </section>

    <!-- Section 4: Peraturan Umum Santri -->
    <section id="peraturan" data-section="peraturan" class="max-w-7xl mx-auto w-full px-gutter py-space-lg scroll-mt-28">
      <div class="bg-surface-container-lowest p-space-lg sm:p-space-xl shadow-md">
        <Reveal slow>
          <div class="border-b border-outline-variant pb-space-lg mb-space-lg">
            <span class="font-code-md text-code-md text-primary font-semibold">PERATURAN</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mt-1">Peraturan Umum Santri</h2>
            <p class="font-body-sm text-body-sm text-on-surface-variant max-w-3xl mt-2 leading-relaxed">Peraturan diarahkan untuk membentuk akhlak, kedisiplinan, tanggung jawab, dan fokus belajar santri.</p>
          </div>
        </Reveal>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-lg">
          <Reveal slow v-for="(r, i) in rules" :key="r.title" :delay="i * 140" class="h-full">
            <article class="h-full landing-card bg-surface-container-low rounded shadow-sm p-space-md flex flex-col gap-space-sm">
              <span class="w-11 h-11 rounded-full bg-primary-fixed text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[24px]" aria-hidden="true">{{ r.icon }}</span>
              </span>
              <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface leading-snug">{{ r.title }}</h3>
              <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">{{ r.desc }}</p>
            </article>
          </Reveal>
        </div>
      </div>
    </section>

    <!-- Section 5: Seragam Santri -->
    <section id="seragam" data-section="seragam" class="max-w-7xl mx-auto w-full px-gutter py-space-lg scroll-mt-28">
      <div class="bg-surface-container-lowest p-space-lg sm:p-space-xl shadow-md">
        <Reveal slow>
          <div class="border-b border-outline-variant pb-space-lg mb-space-lg">
            <span class="font-code-md text-code-md text-primary font-semibold">SERAGAM</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mt-1">Seragam &amp; Tata Busana Santri</h2>
            <p class="font-body-sm text-body-sm text-on-surface-variant max-w-3xl mt-2 leading-relaxed">Ketentuan berpakaian diterapkan untuk mendukung kenyamanan belajar sekaligus menjaga tata busana yang sesuai dengan ketentuan syariat.</p>
          </div>
        </Reveal>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-space-lg">
          <Reveal slow v-for="(g, i) in uniformGroups" :key="g.label" :delay="i * 140" class="h-full">
            <article class="h-full landing-card bg-surface-container-low rounded shadow-sm p-space-md sm:p-space-lg flex flex-col gap-space-md">
              <div class="flex items-center gap-2 border-b border-surface-container-high pb-space-sm">
                <span class="material-symbols-outlined text-[22px] text-primary" aria-hidden="true">{{ g.icon }}</span>
                <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface uppercase">{{ g.label }}</h3>
              </div>
              <div v-for="s in g.items" :key="s.eyebrow" class="rounded bg-surface-container-lowest border border-outline-variant p-space-sm shadow-sm">
                <span class="font-label-sm text-label-sm text-secondary font-bold uppercase tracking-wider">{{ s.eyebrow }}</span>
                <p class="font-body-sm text-body-sm text-on-surface leading-relaxed mt-1">{{ s.desc }}</p>
              </div>
            </article>
          </Reveal>
        </div>
      </div>
    </section>

    <!-- Section 6: Aktivitas Harian Santri -->
    <section id="aktivitas" data-section="aktivitas" class="max-w-7xl mx-auto w-full px-gutter py-space-lg scroll-mt-28">
      <div class="bg-surface-container-lowest p-space-lg sm:p-space-xl shadow-md">
        <Reveal slow>
          <div class="border-b border-outline-variant pb-space-lg mb-space-lg">
            <span class="font-code-md text-code-md text-primary font-semibold">AKTIVITAS HARIAN</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mt-1">Rutinitas Harian Santri</h2>
            <p class="font-body-sm text-body-sm text-on-surface-variant max-w-3xl mt-2 leading-relaxed">Kegiatan santri berlangsung secara terstruktur dari sebelum Subuh hingga malam hari untuk menyeimbangkan pendidikan formal, ibadah, pengajian, dan waktu istirahat.</p>
          </div>
        </Reveal>
        <Reveal slow>
          <div class="relative max-w-3xl space-y-space-lg">
            <span class="absolute left-2 top-3 bottom-3 w-0.5 bg-surface-container-high" aria-hidden="true"></span>
            <div v-for="(a, i) in dailyActivities" :key="a.time" class="relative pl-space-lg">
              <span class="absolute left-2 top-2 w-3 h-3 -translate-x-1/2 rounded-full border-2 border-primary bg-surface-container-lowest" aria-hidden="true"></span>
              <Reveal slow :delay="i * 140">
                <div class="bg-surface-container-low landing-card rounded shadow-sm p-space-md flex flex-col gap-1">
                  <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-primary" aria-hidden="true">{{ a.icon }}</span>
                    <span class="font-code-md text-code-md text-primary font-bold">{{ a.time }}</span>
                  </div>
                  <h4 class="font-title-md text-title-md font-bold text-on-surface">{{ a.title }}</h4>
                  <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">{{ a.detail }}</p>
                </div>
              </Reveal>
            </div>
          </div>
        </Reveal>
      </div>
    </section>

    <!-- Section 7: Kegiatan Berkala -->
    <section id="berkala" data-section="berkala" class="max-w-7xl mx-auto w-full px-gutter py-space-lg scroll-mt-28">
      <div class="bg-surface-container-lowest p-space-lg sm:p-space-xl shadow-md">
        <Reveal slow>
          <div class="border-b border-outline-variant pb-space-lg mb-space-lg">
            <span class="font-code-md text-code-md text-primary font-semibold">KEGIATAN BERKALA</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mt-1">Aktivitas Tambahan Santri</h2>
            <p class="font-body-sm text-body-sm text-on-surface-variant max-w-3xl mt-2 leading-relaxed">Pada hari tertentu, seperti Selasa malam atau malam Jumat, kegiatan dapat meliputi pengajian akbar/rutin bersama Abiya Hatta, latihan pidato (muhadharah), serta pembacaan Dalail Khairat.</p>
          </div>
        </Reveal>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-space-lg">
          <Reveal slow v-for="(b, i) in berkala" :key="b.title" :delay="i * 140" class="h-full">
            <article class="h-full landing-card bg-surface-container-low rounded shadow-sm p-space-md flex items-start gap-space-sm">
              <span class="w-11 h-11 rounded-full bg-primary-fixed text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[24px]" aria-hidden="true">{{ b.icon }}</span>
              </span>
              <div>
                <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface leading-snug">{{ b.title }}</h3>
                <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed mt-1">{{ b.desc }}</p>
              </div>
            </article>
          </Reveal>
        </div>
      </div>
    </section>

    <!-- Section 8: Closing -->
    <section class="max-w-7xl mx-auto w-full px-gutter py-space-lg">
      <Reveal slow>
        <div class="bg-primary text-on-primary rounded shadow-md p-space-lg sm:p-space-xl relative overflow-hidden">
          <div class="absolute -right-10 -top-10 w-44 h-44 rounded-full bg-on-primary/10 pointer-events-none" aria-hidden="true"></div>
          <div class="absolute -left-8 -bottom-12 w-32 h-32 rounded-full bg-secondary-fixed/20 pointer-events-none" aria-hidden="true"></div>
          <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-space-md">
            <div>
              <span class="font-label-sm text-label-sm font-bold uppercase tracking-widest opacity-80">Satu Ekosistem</span>
              <h2 class="font-headline-lg text-headline-lg font-bold mt-1">Pendidikan, Ibadah &amp; Kehidupan dalam Satu Ekosistem</h2>
              <p class="font-body-md text-body-md text-on-primary/80 leading-relaxed mt-2 max-w-2xl">Dayah Madani Al-Aziziyah membangun kehidupan santri melalui integrasi pendidikan formal, tradisi keilmuan dayah, pembinaan ibadah, kedisiplinan, dan kehidupan asrama.</p>
            </div>
            <span class="w-14 h-14 rounded-full bg-on-primary/10 flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[30px]" aria-hidden="true">partner_exchange</span>
            </span>
          </div>
        </div>
      </Reveal>
    </section>
  </PublicLayout>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import PublicLayout from '../Components/PublicLayout.vue'
import Reveal from '../Components/Reveal.vue'

const SIKOLA_PROFILE = {
  smp: 'https://sekolah.data.kemendikdasmen.go.id/profil-sekolah/BEA3C7A0-A217-46D4-BD87-191D72FD8522',
  sma: 'https://sekolah.data.kemendikdasmen.go.id/profil-sekolah/9050593B-E3B1-4C1A-9970-7159A5479B1D',
}

const sectionNav = [
  { id: 'prestasi', label: 'Prestasi' },
  { id: 'fasilitas', label: 'Fasilitas' },
  { id: 'kehidupan', label: 'Kehidupan Santri' },
  { id: 'peraturan', label: 'Peraturan' },
  { id: 'seragam', label: 'Seragam' },
  { id: 'aktivitas', label: 'Aktivitas Harian' },
  { id: 'berkala', label: 'Kegiatan Berkala' },
]

const activeSection = ref('prestasi')

const achievements = [
  {
    icon: 'verified',
    title: 'Akreditasi B Nasional',
    desc: 'Secara kelembagaan, pendidikan formal di bawah naungan Yayasan Dayah Madani Al-Aziziyah telah meraih Akreditasi B pada tingkat SMP Madani Al-Aziziyah dan SMA Madani Al-Aziziyah.',
    units: [
      { label: 'Profil SMP Madani Al-Aziziyah', href: SIKOLA_PROFILE.smp },
      { label: 'Profil SMA Madani Al-Aziziyah', href: SIKOLA_PROFILE.sma },
    ],
  },
  {
    icon: 'school',
    title: 'Pencapaian Akademik Pimpinan',
    desc: "Dr. Tgk. H. Muhammad Hatta, Lc., M.Ed. (Abiya Hatta) meraih gelar Doktor (S3) di UIN Ar-Raniry Banda Aceh dengan predikat 'Terpuji' (Cumlaude).",
  },
  {
    icon: 'volunteer_activism',
    title: 'Kontribusi Sosial & Kemanusiaan',
    desc: 'Dayah turut berpartisipasi dalam aksi sosial dan kemanusiaan, termasuk penggalangan solidaritas dan penyerahan bantuan dana kemanusiaan untuk Palestina melalui MPU Aceh.',
  },
]

const facilities = [
  {
    icon: 'home',
    title: 'Kompleks Asrama Santri Terpadu',
    desc: 'Menyediakan fasilitas tempat tinggal bagi santri mukim dengan pemisahan blok putra dan putri.',
  },
  {
    icon: 'school',
    title: 'Gedung Ruang Kelas Pembelajaran',
    desc: 'Ruang belajar digunakan untuk pembelajaran formal pada pagi hingga siang hari serta kegiatan kepesantrenan dan halaqah pada waktu yang telah ditentukan.',
  },
  {
    icon: 'science',
    title: 'Laboratorium & Perpustakaan',
    desc: 'Tersedianya sarana laboratorium untuk mendukung praktikum sains dan komputer serta perpustakaan/maktabah untuk menunjang literasi umum dan referensi kitab kuning santri.',
  },
  {
    icon: 'water_drop',
    title: 'Sanitasi & Lingkungan',
    desc: 'Fasilitas sanitasi dan pengelolaan lingkungan mendukung kebersihan serta kesehatan lingkungan asrama, termasuk fasilitas Instalasi Pengolahan Air Limbah (IPAL) Komunal.',
  },
]

const rules = [
  {
    icon: 'home',
    title: 'Wajib Mukim',
    desc: 'Santri SMP dan SMA terintegrasi wajib tinggal di asrama pesantren selama masa pendidikan.',
  },
  {
    icon: 'phone_disabled',
    title: 'Larangan Gadget & Elektronik',
    desc: 'Santri dilarang membawa smartphone, laptop pribadi, atau perangkat elektronik hiburan lainnya, kecuali fasilitas laboratorium sekolah sesuai ketentuan.',
  },
  {
    icon: 'schedule',
    title: 'Izin Khuruj',
    desc: 'Santri hanya diperbolehkan keluar atau pulang pada jadwal kunjungan/libur resmi dan mengikuti ketentuan perizinan yang berlaku.',
  },
  {
    icon: 'cleaning_services',
    title: 'Ibadah, Bahasa & Kebersihan',
    desc: 'Santri wajib mengikuti salat berjamaah, menerapkan disiplin bahasa Arab dan Inggris di lingkungan yang ditentukan, serta menjaga kebersihan lingkungan.',
  },
]

const uniformGroups = [
  {
    label: 'Putra',
    icon: 'male',
    items: [
      { eyebrow: 'Sekolah Formal', desc: 'Kemeja sekolah formal sesuai hari/tingkatan, celana kain panjang dan longgar, serta peci hitam nasional.' },
      { eyebrow: 'Pengajian Dayah', desc: 'Kemeja koko atau baju muslim polos berwarna putih/terang, kain sarung, serta peci/kopiah.' },
    ],
  },
  {
    label: 'Putri',
    icon: 'female',
    items: [
      { eyebrow: 'Sekolah Formal', desc: "Baju kurung/blus longgar dan panjang, rok kain formal yang tidak ketat, serta jilbab syar'i yang menutupi dada." },
      { eyebrow: 'Pengajian Dayah', desc: 'Baju kurung muslimah atau gamis longgar, kain bawahan, serta jilbab instan/bergo besar.' },
    ],
  },
]

const dailyActivities = [
  {
    time: '04:30 – 06:00',
    icon: 'wb_sunny',
    title: 'Spiritual Pagi',
    detail: "Bangun tidur, salat Subuh berjamaah, dilanjutkan zikir dan halaqah tahfizh/tahsin Al-Qur'an.",
  },
  {
    time: '06:00 – 07:15',
    icon: 'coffee',
    title: 'Persiapan & Sarapan',
    detail: 'Piket kebersihan asrama, mandi, sarapan pagi, dan bersiap menggunakan seragam sekolah formal.',
  },
  {
    time: '07:30 – 13:30',
    icon: 'school',
    title: 'Sekolah Formal',
    detail: 'KBM SMP/SMA dengan materi kurikulum nasional seperti Sains, Matematika, Bahasa, dan bidang pembelajaran lainnya, diselingi salat Dzuhur berjamaah.',
  },
  {
    time: '13:30 – 16:00',
    icon: 'restaurant',
    title: 'Istirahat & Makan Siang',
    detail: 'Makan siang, istirahat/tidur siang, dan persiapan salat Ashar.',
  },
  {
    time: '16:30 – 17:30',
    icon: 'menu_book',
    title: 'Pengajian Sore',
    detail: 'Pembelajaran dasar Kutubut Turats seperti Nahwu, Sharaf, dan Fiqih dasar setelah jamaah Ashar.',
  },
  {
    time: '17:30 – 18:30',
    icon: 'nightlight',
    title: 'Persiapan Maghrib',
    detail: 'Mandi sore dan persiapan menuju waktu Maghrib dengan kegiatan zikir/selawat di lingkungan masjid.',
  },
  {
    time: '18:30 – 22:00',
    icon: 'auto_stories',
    title: 'Pengajian Malam & Makan',
    detail: 'Salat Maghrib berjamaah, makan malam, kemudian dilanjutkan pengajian malam kitab kuning hingga sekitar pukul 21:30 atau 22:00 WIB.',
  },
  {
    time: '22:00 – 04:30',
    icon: 'bedtime',
    title: 'Istirahat Malam',
    detail: 'Santri diwajibkan beristirahat di kamar asrama masing-masing untuk menjaga kondisi fisik.',
  },
]

const berkala = [
  {
    icon: 'groups',
    title: 'Pengajian Akbar/Rutin',
    desc: 'Pengajian akbar maupun rutin yang diisi langsung bersama Abiya Hatta.',
  },
  {
    icon: 'record_voice_over',
    title: 'Muhadharah',
    desc: 'Latihan pidato santri di hadapan seluruh santri.',
  },
  {
    icon: 'auto_stories',
    title: 'Dalail Khairat',
    desc: 'Pembacaan Dalail Khairat pada malam tersebut.',
  },
]

let scrollSpyObserver = null

function scrollToSection(id) {
  const el = document.getElementById(id)
  if (!el) return
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  el.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'start' })
}

onMounted(() => {
  if (typeof IntersectionObserver !== 'undefined') {
    scrollSpyObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            activeSection.value = entry.target.dataset.section
          }
        })
      },
      { rootMargin: '-40% 0px -55% 0px', threshold: 0 }
    )
    document.querySelectorAll('section[data-section]').forEach((el) => scrollSpyObserver.observe(el))
  }
})

onUnmounted(() => {
  if (scrollSpyObserver) scrollSpyObserver.disconnect()
})
</script>