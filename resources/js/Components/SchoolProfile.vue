<template>
  <div>
    <section v-if="show('about')" class="max-w-7xl mx-auto w-full px-gutter py-space-lg">
      <div class="bg-surface-container-lowest p-space-lg sm:p-space-xl shadow-md">
        <Reveal slow>
          <div class="border-b border-outline-variant pb-space-lg mb-space-lg">
            <span class="font-code-md text-code-md text-primary font-semibold">TENTANG SEKOLAH</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mt-1">Mengenal SMA Madani Al-Aziziyah</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2 leading-relaxed max-w-3xl">Pendidikan Islam terpadu yang memadukan kedalaman tradisi keilmuan pesantren dengan pendidikan formal dan wawasan akademik modern.</p>
          </div>
        </Reveal>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg">
          <div class="lg:col-span-7 flex flex-col justify-between gap-space-lg">
            <Reveal slow>
              <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">Dayah Madani Al-Aziziyah adalah lembaga pendidikan Islam terpadu yang didirikan pada tahun 2011 oleh <span class="font-semibold text-on-surface">Dr. Tgk. H. Muhammad Hatta, Lc., M.Ed. (Abiya Hatta)</span>.</p>
            </Reveal>
            <Reveal slow :delay="160">
              <div class="flex items-start gap-space-sm p-space-md bg-surface-container-low shadow-sm hover:shadow-md transition-shadow">
                <span class="material-symbols-outlined text-primary mt-0.5">location_on</span>
                <div>
                  <p class="font-label-sm text-label-sm text-outline uppercase tracking-wider">ALAMAT LEMBAGA</p>
                  <p class="font-title-md text-title-md text-on-surface font-semibold mt-0.5 leading-snug">Jl. T. Imum Hamzah Lr. Dayah, Lampeuneurut Ujong Blang, Kecamatan Darul Imarah, Kabupaten Aceh Besar.</p>
                </div>
              </div>
            </Reveal>
          </div>
          <div class="lg:col-span-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2 gap-space-md self-start">
            <Reveal slow v-for="(s, i) in identityStats" :key="s.label" :delay="i * 140">
              <div class="bg-surface-container-low p-space-md flex items-start gap-space-sm shadow-sm hover:shadow-md transition-shadow">
                <span class="material-symbols-outlined text-[22px]" :class="s.color">{{ s.icon }}</span>
                <div>
                  <span class="font-label-sm text-label-sm text-outline uppercase">{{ s.label }}</span>
                  <p class="font-headline-md text-headline-md font-bold text-on-surface mt-0.5">{{ s.value }}</p>
                  <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">{{ s.hint }}</p>
                </div>
              </div>
            </Reveal>
          </div>
        </div>
      </div>
    </section>

    <section v-if="show('history')" class="max-w-7xl mx-auto w-full px-gutter py-space-lg">
      <div class="bg-surface-container-lowest p-space-lg sm:p-space-xl shadow-md">
        <Reveal slow>
          <div class="flex flex-col md:flex-row md:items-end justify-between pb-space-lg gap-space-md border-b border-outline-variant mb-space-lg">
            <div>
              <span class="font-code-md text-code-md text-primary font-semibold">SEJARAH &amp; FILOSOFI</span>
              <h2 class="font-headline-lg text-headline-lg text-on-surface mt-1">Akar Keilmuan dan Semangat Kelembagaan</h2>
            </div>
          </div>
        </Reveal>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-space-lg">
          <Reveal slow>
            <div class="bg-surface-container-low p-space-lg h-full flex flex-col gap-space-sm">
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">link</span>
                <span class="font-label-sm text-label-sm font-bold text-primary uppercase tracking-wider">Jalinan Sanad &amp; Tabarruk</span>
              </div>
              <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">Sebagai lembaga yang berafiliasi dengan jaringan ulama alumni Dayah MUDI Mesjid Raya Samalanga, nama "Al-Aziziyah" digunakan sebagai bentuk keterkaitan dengan tradisi keilmuan pesantren tersebut.</p>
            </div>
          </Reveal>
          <Reveal slow :delay="160">
            <div class="bg-surface-container-low p-space-lg h-full flex flex-col gap-space-sm">
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">family_restroom</span>
                <span class="font-label-sm text-label-sm font-bold text-secondary uppercase tracking-wider">Menjawab Kebutuhan Masyarakat Urban</span>
              </div>
              <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">Dayah ini hadir sebagai respons atas kebutuhan masyarakat urban di sekitar Banda Aceh dan Aceh Besar yang menginginkan anak-anaknya mendapatkan pendidikan agama formal tanpa kehilangan kedalaman ilmu khas pesantren salafiyah.</p>
            </div>
          </Reveal>
        </div>
      </div>
    </section>

    <section v-if="show('visi')" class="max-w-7xl mx-auto w-full px-gutter py-space-lg">
      <div class="bg-surface-container-lowest p-space-lg sm:p-space-xl shadow-md">
        <Reveal slow>
          <div class="border-b border-outline-variant pb-space-lg mb-space-lg">
            <span class="font-code-md text-code-md text-primary font-semibold">VISI &amp; MISI</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mt-1">Soal Visi dan Misi Lembaga</h2>
          </div>
        </Reveal>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg">
          <Reveal slow class="lg:col-span-5">
            <div class="bg-primary text-on-primary p-space-lg relative overflow-hidden h-full">
              <div class="absolute -right-6 -top-6 w-28 h-28 rounded-full bg-on-primary/10"></div>
              <span class="font-label-sm text-label-sm font-bold uppercase tracking-widest opacity-80">VISI</span>
              <p class="font-headline-md text-headline-md font-semibold leading-relaxed mt-space-sm relative z-10">"Menjadi pusat pendidikan yang menghasilkan ulama dan intelektual yang berakhlak mulia."</p>
            </div>
          </Reveal>
          <div class="lg:col-span-7">
            <Reveal slow :delay="160">
              <span class="font-label-sm text-label-sm font-bold text-primary uppercase tracking-widest">MISI</span>
            </Reveal>
            <ul class="mt-space-sm space-y-space-sm">
              <li v-for="(m, i) in missions" :key="m" class="flex items-start gap-space-sm">
                <Reveal slow :delay="160 + i * 140" class="flex-1">
                  <div class="bg-surface-container-low p-space-md flex items-start gap-space-sm shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-[transform,box-shadow]">
                    <span class="material-symbols-outlined text-primary mt-0.5">{{ m.icon }}</span>
                    <p class="font-body-md text-body-md text-on-surface font-medium">{{ m.text }}</p>
                  </div>
                </Reveal>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <section v-if="show('kurikulum')" class="max-w-7xl mx-auto w-full px-gutter py-space-lg">
      <div class="bg-surface-container-lowest p-space-lg sm:p-space-xl shadow-md">
        <Reveal slow>
          <div class="flex flex-col md:flex-row md:items-end justify-between pb-space-lg gap-space-md border-b border-outline-variant mb-space-lg">
            <div>
              <span class="font-code-md text-code-md text-primary font-semibold">KURIKULUM</span>
              <h2 class="font-headline-lg text-headline-lg text-on-surface mt-1">Sistem Pendidikan Terpadu</h2>
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant max-w-md">Keduanya dipadukan dalam sistem pendidikan berasrama.</p>
          </div>
        </Reveal>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-lg">
          <Reveal slow v-for="(s, i) in systemPillars" :key="s.title" :delay="i * 140">
            <div class="bg-surface-container-low p-space-lg h-full flex flex-col justify-between" :class="s.cardClass">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="font-label-sm text-label-sm font-bold" :class="s.eyebrowClass">{{ s.eyebrow }} +</span>
                  <span class="material-symbols-outlined text-[24px]" :class="s.iconClass">{{ s.icon }}</span>
                </div>
                <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface">{{ s.title }}</h3>
                <p class="font-body-md text-body-md text-on-surface-variant mt-2 leading-relaxed">{{ s.desc }}</p>
              </div>
              <div class="mt-space-md pt-space-xs border-t font-code-md text-code-md" :class="s.footerClass">{{ s.footer }}</div>
            </div>
          </Reveal>
        </div>
      </div>
    </section>

    <section v-if="show('unit')" class="max-w-7xl mx-auto w-full px-gutter py-space-lg">
      <div class="bg-surface-container-lowest p-space-lg sm:p-space-xl shadow-md">
        <Reveal slow>
          <div class="border-b border-outline-variant pb-space-lg mb-space-lg">
            <span class="font-code-md text-code-md text-primary font-semibold">UNIT PENDIDIKAN</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mt-1">Unit Pendidikan</h2>
          </div>
        </Reveal>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-lg">
          <Reveal slow v-for="(u, i) in units" :key="u.name" :delay="i * 140">
            <div class="bg-surface-container-low p-space-lg flex flex-col gap-space-sm shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-[transform,box-shadow]">
              <div class="flex items-center justify-between">
                <span class="w-12 h-12 rounded-full bg-primary-fixed text-primary flex items-center justify-center shrink-0">
                  <span class="material-symbols-outlined text-[26px]">school</span>
                </span>
                <span class="font-label-sm text-label-sm font-bold text-primary uppercase tracking-wider">{{ u.code }}</span>
              </div>
              <div>
                <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface">{{ u.name }}</h3>
                <div class="mt-2 flex items-baseline gap-2">
                  <span class="font-label-sm text-label-sm text-outline uppercase">Akreditasi</span>
                  <span class="font-headline-lg text-headline-lg font-bold text-secondary">{{ u.akreditasi }}</span>
                </div>
              </div>
            </div>
          </Reveal>
        </div>
      </div>
    </section>

    <section v-if="show('bahasa')" class="max-w-7xl mx-auto w-full px-gutter py-space-lg">
      <div class="bg-surface-container-lowest p-space-lg sm:p-space-xl shadow-md">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-center">
          <Reveal slow class="lg:col-span-8">
            <div>
              <span class="font-code-md text-code-md text-primary font-semibold">BAHASA</span>
              <h3 class="font-headline-md text-headline-md font-bold text-on-surface mt-1">Penguasaan Bahasa Arab &amp; Inggris</h3>
              <p class="font-body-md text-body-md text-on-surface-variant mt-2 leading-relaxed max-w-2xl">Penguasaan bahasa Arab dan Inggris turut ditekankan untuk membekali santri menghadapi tantangan zaman.</p>
            </div>
          </Reveal>
          <Reveal slow :delay="160" class="lg:col-span-4">
            <div class="flex flex-row lg:flex-col xl:flex-row items-stretch gap-space-sm">
              <div v-for="b in languages" :key="b.label" class="flex-1 flex items-center gap-2 p-space-sm bg-surface-container-low rounded-xl border border-outline-variant transition-transform duration-200 ease-in-out hover:-translate-y-1">
                <span class="material-symbols-outlined text-[20px] text-primary">{{ b.icon }}</span>
                <span class="font-title-md text-title-md font-semibold text-on-surface">{{ b.label }}</span>
              </div>
            </div>
          </Reveal>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import Reveal from '../Components/Reveal.vue'

const props = defineProps({
  only: { type: Array, default: null },
})

const show = (key) => !props.only || props.only.includes(key)

const identityStats = [
  { label: 'TAHUN BERDIRI', value: '2011', hint: 'Didirikan oleh Abiya Hatta', icon: 'calendar_month', color: 'text-primary' },
  { label: 'LOKASI', value: 'Aceh Besar', hint: 'Kec. Darul Imarah', icon: 'map', color: 'text-secondary' },
]

const missions = [
  { icon: 'verified_user', text: "Menanamkan akidah Ahlussunnah Waljama'ah." },
  { icon: 'volunteer_activism', text: 'Membina kesalehan umat.' },
  { icon: 'history_edu', text: 'Menjaga nilai-nilai salafush-shalih.' },
  { icon: 'groups', text: 'Membentuk generasi yang mandiri dan berkarya.' },
]

const systemPillars = [
  {
    eyebrow: 'SALAFIYAH',
    eyebrowClass: 'text-primary',
    icon: 'menu_book',
    iconClass: 'text-primary',
    title: 'Kurikulum Salafiyah',
    desc: 'Pendalaman kitab kuning dan tradisi keilmuan pesantren sebagai pondasi kedalaman ilmu agama.',
    footer: 'Kitab Kuning & Tradisi Pesantren',
    footerClass: 'border-outline text-primary font-semibold',
    cardClass: '',
  },
  {
    eyebrow: 'FORMAL',
    eyebrowClass: 'text-secondary',
    icon: 'school',
    iconClass: 'text-secondary',
    title: 'Kurikulum Formal',
    desc: 'Pendidikan sekolah formal dengan standar akademik yang mendukung kebutuhan pendidikan modern.',
    footer: 'Standar Akademik Modern',
    footerClass: 'border-outline text-secondary font-semibold',
    cardClass: '',
  },
]

const units = [
  { code: 'SMP', name: 'SMP Madani Al-Aziziyah', akreditasi: 'B' },
  { code: 'SMA', name: 'SMA Madani Al-Aziziyah', akreditasi: 'B' },
]

const languages = [
  { label: 'Arab', icon: 'translate' },
  { label: 'Inggris', icon: 'language' },
]
</script>