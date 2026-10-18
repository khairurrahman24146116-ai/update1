import { SMA_BASE } from '../site-paths.js'
export const smaUrl = SMA_BASE

export const brand = {
  name: 'LPI Dayah Madani Al-Aziziyah',
  short: 'LPI Madani Al-Aziziyah',
  tagline: 'Lembaga Pendidikan Islam terpadu: Dayah Salafiyah dan pendidikan formal yang bermutu.',
}

export const visi = 'Visi LPI Dayah Madani Al-Aziziyah akan ditetapkan kemudian.'

export const misi = [
  'Mengintegrasikan keilmuan Dayah Salafiyah dengan pendidikan formal secara sistematis dan bermutu',
  'Mengembangkan tafaqquh fiddin melalui pengkajian Al-Qur\u2019an, Sunnah, dan kitab-kitab turats.',
  'Membentuk santri yang berakhlakul karimah, disiplin, mandiri, bertanggung jawab, dan peduli terhadap masyarakat.',
  'Mengembangkan kompetensi akademik, keterampilan, kreativitas, kepemimpinan, dan potensi santri secara optimal.',
  'Menguatkan penguasaan Bahasa Arab dan Bahasa Inggris',
  'Mengembangkan pembelajaran yang inovatif dan adaptif terhadap perkembangan ilmu pengetahuan dan teknologi dengan tetap berlandaskan nilai-nilai Islam.',
  'Mengembangkan kaderisasi ulama dan cendekiawan Muslim melalui penguatan pendidikan Dayah Salafiyah dan perguruan tinggi',
  'Melahirkan lulusan yang berintegritas, kompeten, berwawasan luas, dan mampu memberikan kontribusi nyata bagi kemaslahatan umat, bangsa, dan kemanusiaan.',
  'Meningkatkan profesionalisme pendidik, kualitas tata kelola, sarana prasarana, dan mutu layanan pendidikan secara berkelanjutan.',
  'Memperluas akses pendidikan serta memperkuat kemitraan dengan masyarakat dan berbagai pemangku kepentingan.',
]

export const profil = [
  'Profil LPI Dayah Madani Al-Aziziyah akan dilengkapi kemudian.',
]

export const programs = [
  {
    key: 'sma',
    name: 'SMA Madani Al-Aziziyah',
    desc: 'SMA Sains dan Tahfidz berasrama penuh. Kunjungi situs resmi SMA.',
    href: smaUrl,
    active: true,
    icon: 'school',
  },
  {
    key: 'smp',
    name: 'SMP Madani Al-Aziziyah',
    desc: 'Segera hadir',
    href: null,
    active: false,
    icon: 'schedule',
  },
]

export const akademikMenu = [
  { key: 'kurikulum', label: 'Kurikulum', href: '/akademik/kurikulum', icon: 'menu_book' },
  { key: 'kalender', label: 'Kalender Akademik', href: '/akademik/kalender', icon: 'calendar_month' },
]

export const layanan = [
  { name: 'Penerimaan Santri Baru (PSB)', desc: 'Layanan informasi dan pendaftaran santri baru. Detail menyusul.', icon: 'how_to_reg' },
  { name: 'Akademik dan Kurikulum', desc: 'Layanan administrasi akademik. Detail menyusul.', icon: 'menu_book' },
  { name: 'Kesantrian dan Asrama', desc: 'Layanan pembinaan dan kehidupan asrama. Detail menyusul.', icon: 'home' },
  { name: 'Konsultasi dan Bantuan', desc: 'Layanan komunikasi wali dan bantuan. Detail menyusul.', icon: 'support_agent' },
]

export const navigation = [
  { name: 'Beranda', href: '/' },
  {
    name: 'Profil',
    href: '/tentang',
    children: [
      { name: 'Profil Dayah', href: '/tentang' },
      { name: 'Struktur Organisasi', href: '/struktur' },
    ],
  },
  {
    name: 'Program',
    href: '/program',
    children: [
      { name: 'SMA Madani', href: '/sma' },
      { name: 'SMP Madani', href: null, comingSoon: true },
    ],
  },
  {
    name: 'Akademik',
    children: [
      { name: 'Kurikulum', href: '/akademik/kurikulum' },
      { name: 'Kalender Akademik', href: '/akademik/kalender' },
    ],
  },
  { name: 'Layanan', href: '/layanan' },
]

export const footerLinks = [
  { name: 'Beranda', href: '/' },
  { name: 'Profil Dayah', href: '/tentang' },
  { name: 'Struktur Organisasi', href: '/struktur' },
  { name: 'Program', href: '/program' },
  { name: 'Akademik', href: '/akademik/kurikulum' },
  { name: 'Layanan', href: '/layanan' },
]
