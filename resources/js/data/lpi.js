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

export const struktur = [
  { jabatan: 'Ketua Yayasan', nama: '—' },
  { jabatan: 'Pimpinan Dayah', nama: '—' },
  { jabatan: 'Kepala SMA', nama: '—' },
  { jabatan: 'Kepala SMP', nama: 'Segera hadir' },
]

export const tentangMenu = [
  { key: 'profil', label: 'Profil', hash: '#profil', icon: 'person' },
  { key: 'visi', label: 'Visi', hash: '#visi', icon: 'visibility' },
  { key: 'misi', label: 'Misi', hash: '#misi', icon: 'checklist' },
  { key: 'struktur', label: 'Struktur', hash: '#struktur', icon: 'account_tree' },
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

export const layanan = [
  { name: 'Penerimaan Santri Baru (PSB)', desc: 'Layanan informasi dan pendaftaran santri baru. Detail menyusul.', icon: 'how_to_reg' },
  { name: 'Akademik dan Kurikulum', desc: 'Layanan administrasi akademik. Detail menyusul.', icon: 'menu_book' },
  { name: 'Kesantrian dan Asrama', desc: 'Layanan pembinaan dan kehidupan asrama. Detail menyusul.', icon: 'home' },
  { name: 'Konsultasi dan Bantuan', desc: 'Layanan komunikasi wali dan bantuan. Detail menyusul.', icon: 'support_agent' },
]
