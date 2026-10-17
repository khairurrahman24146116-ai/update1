/**
 * Sumber konten resmi halaman Profil Dayah.
 *
 * Seluruh fakta, nama, tahun, dan angka pada berkas ini mengacu pada
 * dokumen profil LPI Dayah Madani Al-Aziziyah. Jangan menambah visi,
 * misi, prestasi, atau angka yang tidak tercantum pada dokumen.
 */

export const profilMeta = {
  badge: 'Profil Lembaga',
  title: 'PROFIL LPI DAYAH MADANI AL-AZIZIYAH',
  institution: 'LPI Dayah Madani Al-Aziziyah',
  location: 'Gampong Lampeuneurut Ujong Blang, Kecamatan Darul Imarah, Kabupaten Aceh Besar, Provinsi Aceh',
  established: '2011',
  founder: 'Abiya Dr. H. Muhammad Hatta Mahmud Affan, Lc., M.Ed.',
  intro: 'Lembaga pendidikan Islam yang memadukan tradisi keilmuan Dayah Salafiyah dengan pendidikan formal secara terpadu.',
}

export const profilStats = [
  { value: '2011', label: 'Tahun Berdiri' },
  { value: '± 2 Ha', label: 'Luas Kompleks' },
  { value: '20', label: 'Balai Pengajian' },
  { value: '819', label: 'Santriwan & Santriwati' },
]

/**
 * Tonggak perjalanan lembaga. Hanya memuat kejadian yang disebutkan
 * pada dokumen profil. Jangan menambah tanggal atau peristiwa lain.
 */
export const profilTimeline = [
  {
    year: '2011',
    title: 'Pendirian Lembaga',
    desc: 'LPI Dayah MADANI Al-Aziziyah didirikan oleh Abiya Dr. H. Muhammad Hatta Mahmud Affan, Lc., M.Ed. pada sebidang tanah kurang lebih 1.137 meter persegi.',
  },
  {
    year: '1432 H',
    title: 'Pengajian Perdana',
    desc: 'Proses pengajian diaktifkan dengan 20 santri perdana pada malam 10 Syawal 1432 H, sebagian besar berasal dari lingkungan sekitar dayah.',
  },
  {
    year: '± 3 Tahun',
    title: 'Program Pemondokan',
    desc: 'Setelah kurang lebih tiga tahun berjalan, dibuka program pemondokan bagi santri kurang mampu serta santri yatim dan piatu, diawali 16 santri laki-laki.',
  },
  {
    year: '2021',
    title: 'Kompleks Pendidikan Putri',
    desc: 'Kompleks pendidikan khusus putri diresmikan pada 5 Januari 2021 oleh Almukarram Syekh H. Hasanul Basri bin H. Gadeng (Abu MUDI).',
  },
]

export const profilSections = [
  {
    id: 'pengenalan',
    icon: 'diversity_3',
    title: 'Pengenalan Lembaga',
    paragraphs: [
      'LPI Dayah MADANI Al-Aziziyah merupakan lembaga pendidikan Islam yang memadukan tradisi keilmuan Dayah Salafiyah (pesantren tradisional) dengan sistem pendidikan formal secara terpadu. Lembaga ini dibangun atas kesadaran bahwa pendidikan Islam memiliki peran yang sangat strategis dalam membentuk generasi yang memiliki kedalaman ilmu agama, keluasan wawasan, kematangan intelektual, serta akhlak yang mulia.',
      'Pola pendidikan yang dikembangkan LPI Dayah MADANI Al-Aziziyah berakar pada tradisi pendidikan dayah yang telah tumbuh dan berkembang dalam kehidupan masyarakat Aceh. Tradisi tersebut kemudian diperkaya dengan pendidikan formal dan pendalaman bahasa asing, khususnya Bahasa Arab dan Bahasa Inggris, sebagai sarana untuk memperluas akses peserta didik terhadap khazanah keilmuan Islam dan perkembangan ilmu pengetahuan secara global.',
      'Dengan pola tersebut, LPI Dayah MADANI Al-Aziziyah berupaya menghadirkan sebuah model pendidikan yang mengintegrasikan antara pendidikan agama dan pendidikan umum. Pendidikan Dayah Salafiyah memberikan fondasi keagamaan dan pembentukan karakter, sedangkan pendidikan formal memberikan ruang bagi peserta didik untuk mengembangkan kemampuan akademik, ilmu pengetahuan, keterampilan, dan kompetensi yang dibutuhkan dalam kehidupan modern.',
      'Integrasi tersebut diharapkan dapat melahirkan generasi yang memiliki keseimbangan antara kedalaman spiritual, keluasan intelektual, kematangan emosional, keterampilan sosial, dan kesiapan menghadapi perkembangan zaman.',
      'Keberadaan lembaga ini menjadi semakin strategis karena terletak di Gampong Lampeuneurut Ujong Blang, Kecamatan Darul Imarah, Kabupaten Aceh Besar, yang berada di kawasan penyangga Kota Banda Aceh. Lingkungan sosial masyarakat di sekitar lembaga memiliki kedekatan yang kuat dengan tradisi pendidikan Islam, sehingga keberadaan dayah yang mengintegrasikan pendidikan keagamaan dan pendidikan formal memperoleh dukungan serta respons positif dari masyarakat.',
    ],
  },
  {
    id: 'visi-misi',
    icon: 'flag',
    title: 'Visi & Misi',
    note: 'Visi resmi lembaga belum tercantum pada dokumen profil. Pernyataan misi di bawah ini mengikuti data lembaga yang telah tersedia.',
    paragraphs: [],
    mission: [
      'Mengintegrasikan keilmuan Dayah Salafiyah dengan pendidikan formal secara sistematis dan bermutu.',
      'Mengembangkan tafaqquh fiddin melalui pengkajian Al-Qur\u2019an, Sunnah, dan kitab-kitab turats.',
      'Membentuk santri yang berakhlakul karimah, disiplin, mandiri, bertanggung jawab, dan peduli terhadap masyarakat.',
      'Mengembangkan kompetensi akademik, keterampilan, kreativitas, kepemimpinan, dan potensi santri secara optimal.',
      'Menguatkan penguasaan Bahasa Arab dan Bahasa Inggris.',
      'Mengembangkan pembelajaran yang inovatif dan adaptif terhadap perkembangan ilmu pengetahuan dan teknologi dengan tetap berlandaskan nilai-nilai Islam.',
      'Mengembangkan kaderisasi ulama dan cendekiawan Muslim melalui penguatan pendidikan Dayah Salafiyah dan perguruan tinggi.',
      'Melahirkan lulusan yang berintegritas, kompeten, berwawasan luas, dan mampu memberikan kontribusi nyata bagi kemaslahatan umat, bangsa, dan kemanusiaan.',
      'Meningkatkan profesionalisme pendidik, kualitas tata kelola, sarana prasarana, dan mutu layanan pendidikan secara berkelanjutan.',
      'Memperluas akses pendidikan serta memperkuat kemitraan dengan masyarakat dan berbagai pemangku kepentingan.',
    ],
  },
  {
    id: 'sejarah',
    icon: 'history_edu',
    title: 'Sejarah Berdirinya Dayah',
    paragraphs: [
      'LPI Dayah MADANI Al-Aziziyah didirikan pada tahun 2011 oleh Abiya Dr. H. Muhammad Hatta Mahmud Affan, Lc., M.Ed. Pendirian lembaga ini dilandasi oleh cita-cita untuk menghadirkan sebuah pusat pendidikan Islam yang mampu mempertahankan tradisi keilmuan dayah sekaligus merespons kebutuhan pendidikan masyarakat yang terus berkembang.',
      'Pada masa awal berdirinya, LPI Dayah MADANI Al-Aziziyah hanya memiliki sebidang tanah seluas kurang lebih 1.137 meter persegi, dengan fasilitas yang sangat sederhana berupa dua balai pengajian dan satu asrama kecil yang sebagian besar masih menggunakan konstruksi kayu.',
      'Keterbatasan fasilitas tersebut tidak menjadi penghalang bagi para pendiri dan pengelola untuk mengembangkan pendidikan. Justru dari kesederhanaan tersebut tumbuh semangat untuk membangun sebuah lembaga pendidikan Islam yang lebih besar, mandiri, dan mampu memberikan manfaat yang luas kepada masyarakat.',
      'Proses pengajian mulai diaktifkan dengan jumlah santri perdana sebanyak 20 orang pada malam 10 Syawal 1432 H. Pada tahap awal, para santri berasal dari lingkungan sekitar dayah. Kegiatan pendidikan dilaksanakan dengan pola pengajian khas dayah yang menempatkan penguasaan ilmu agama, pembentukan akhlak, kedisiplinan, dan penghormatan kepada guru sebagai bagian penting dari proses pendidikan.',
      'Setelah proses pendidikan berjalan kurang lebih tiga tahun, LPI Dayah MADANI Al-Aziziyah mulai mengembangkan program pemondokan bagi santri yang berasal dari keluarga kurang mampu serta santri yatim dan piatu. Program tersebut diawali dengan menerima 16 orang santri laki-laki.',
      'Seiring berjalannya waktu, perkembangan lembaga berlangsung secara bertahap. Tanah yang pada awalnya hanya sekitar 1.137 meter persegi berkembang hingga mencapai hampir dua hektare lebih. Demikian pula fasilitas pendidikan terus mengalami peningkatan, dari bangunan sederhana menjadi kompleks pendidikan yang dilengkapi berbagai fasilitas pendukung.',
      'Perkembangan tersebut menjadi bukti bahwa keberadaan LPI Dayah MADANI Al-Aziziyah tidak hanya bertumpu pada pembangunan fisik, tetapi juga tumbuh melalui kepercayaan masyarakat, dukungan para ulama, partisipasi orang tua, serta komitmen seluruh unsur lembaga.',
      'Dalam perjalanan perkembangannya, LPI Dayah MADANI Al-Aziziyah mengalami perubahan yang cukup signifikan, baik dari aspek jumlah santri, tenaga pendidik, program pendidikan, maupun sarana dan prasarana.',
      'Dari sebuah lembaga yang bermula dengan fasilitas sederhana, LPI Dayah MADANI Al-Aziziyah kini telah berkembang menjadi kompleks pendidikan Islam dengan berbagai fasilitas, antara lain gedung asrama permanen, asrama bertingkat, ruang belajar, laboratorium, musalla, unit kesehatan sekolah/UKS, dapur, 20 balai pengajian, sarana pendukung kegiatan santri, serta fasilitas pendidikan dan administrasi lainnya.',
      'Saat ini, berdasarkan dokumen profil, jumlah santri yang mengikuti pendidikan di LPI Dayah MADANI Al-Aziziyah telah mencapai sekitar 819 santriwan dan santriwati, termasuk 66 santri yatim/piatu. Pertumbuhan jumlah santri tersebut menunjukkan meningkatnya kepercayaan masyarakat terhadap sistem pendidikan yang dikembangkan oleh LPI Dayah MADANI Al-Aziziyah.',
    ],
    highlights: [
      { value: '2011', label: 'Tahun pendirian lembaga' },
      { value: '1.137 m²', label: 'Luas tanah awal' },
      { value: '20', label: 'Santri perdana (10 Syawal 1432 H)' },
      { value: '16', label: 'Santri laki-laki program pemondokan awal' },
      { value: '± 2 Ha', label: 'Luas kompleks saat ini' },
      { value: '20', label: 'Balai pengajian' },
      { value: '819', label: 'Santriwan & santriwati' },
      { value: '66', label: 'Santri yatim/piatu' },
    ],
  },
  {
    id: 'sistem',
    icon: 'menu_book',
    title: 'Sistem Pendidikan dan Kurikulum Dayah',
    paragraphs: [
      'LPI Dayah MADANI Al-Aziziyah mengembangkan pendidikan yang berlandaskan pada nilai-nilai keislaman, tradisi keilmuan dayah, pembentukan akhlakul karimah, kedisiplinan, dan pengembangan wawasan peserta didik.',
      'Sebagai lembaga pendidikan yang berbasis Dayah Salafiyah, proses pembelajaran agama tetap menempatkan kitab turats sebagai salah satu sumber utama pembelajaran. Kurikulum pendidikan keagamaan LPI Dayah MADANI Al-Aziziyah berpedoman pada tradisi keilmuan dayah induk, yaitu LPI MUDI Masjid Raya Samalanga.',
      'Materi pembelajaran mencakup berbagai cabang ilmu keislaman. Pembelajaran dimulai dari kitab-kitab dasar, seperti Matan Taqrib dan Fath al-Qarib, kemudian dilanjutkan kepada kitab-kitab tingkat menengah dan tinggi, antara lain Al-Bajuri, I\u2019anatut Thalibin, Al-Mahalli, serta berbagai kitab mu\u2019tabar lainnya.',
    ],
    subjectsTitle: 'Bidang keilmuan yang dipelajari',
    subjects: [
      'Fikih',
      'Ushul fikih',
      'Tauhid',
      'Tasawuf',
      'Nahwu',
      'Sharaf',
      'Balaghah',
      'Tafsir',
      'Hadis',
      'Akhlak',
      'Ilmu-ilmu alat',
      'Berbagai disiplin ilmu keislaman lainnya',
    ],
  },
  {
    id: 'formal',
    icon: 'school',
    title: 'Pendidikan Formal',
    paragraphs: [
      'LPI Dayah MADANI Al-Aziziyah menyelenggarakan pendidikan formal secara terstruktur dan berjenjang. Jenjang pendidikan formal yang diselenggarakan meliputi:',
    ],
    cards: [
      { icon: 'school', title: 'Sekolah Menengah Pertama (SMP)', desc: 'Pendidikan formal tingkat pertama yang memadukan kurikulum nasional dengan pembinaan keagamaan dayah.' },
      { icon: 'account_balance', title: 'Sekolah Menengah Atas (SMA)', desc: 'Pendidikan formal tingkat atas dengan penguatan akademik, keagamaan, dan pengembangan bahasa asing.' },
      { icon: 'castle', title: 'Pendidikan Tinggi Keislaman', desc: 'Program pendidikan tinggi keislaman melalui sekolah tinggi bagi jenjang tertentu.' },
    ],
    note: 'Nama sekolah tinggi, program studi, akreditasi, dan status kelembagaan tidak dicantumkan karena belum tersedia pada dokumen profil.',
  },
  {
    id: 'bahasa',
    icon: 'translate',
    title: 'Pengembangan Bahasa Arab dan Bahasa Inggris',
    paragraphs: [
      'Menyadari pentingnya penguasaan bahasa dalam pengembangan ilmu pengetahuan, LPI Dayah MADANI Al-Aziziyah memberikan perhatian khusus terhadap pembelajaran Bahasa Arab dan Bahasa Inggris.',
      'Bahasa Arab memiliki posisi strategis karena merupakan bahasa utama dalam sumber-sumber keislaman, terutama Al-Qur\u2019an, hadis, dan kitab-kitab turats. Sementara itu, Bahasa Inggris dikembangkan sebagai sarana untuk membuka akses santri terhadap perkembangan ilmu pengetahuan, teknologi, informasi, dan komunikasi internasional.',
      'Program pengembangan bahasa tersebut dilaksanakan melalui program Arabic Online Corse (AOC) untuk guru dan santri. Program ini diharapkan dapat membentuk santri yang mampu membaca dan memahami literatur keislaman sekaligus memiliki kemampuan komunikasi yang relevan dengan perkembangan dunia modern.',
    ],
  },
  {
    id: 'karakter',
    icon: 'volunteer_activism',
    title: 'Pembentukan Karakter dan Akhlak',
    paragraphs: [
      'Pendidikan di LPI Dayah MADANI Al-Aziziyah tidak hanya berorientasi pada pencapaian akademik. Pembentukan karakter dan akhlak menjadi bagian integral dari keseluruhan proses pendidikan.',
      'Kehidupan santri dalam lingkungan dayah memberikan ruang yang luas untuk membangun kebiasaan positif. Dengan demikian, pendidikan diarahkan untuk membentuk pribadi yang memiliki keselarasan antara ilmu, iman, amal, dan akhlak.',
    ],
    list: [
      'Kedisiplinan',
      'Kemandirian',
      'Tanggung jawab',
      'Penghormatan kepada guru',
      'Kepedulian terhadap sesama',
      'Kebersamaan',
      'Kesederhanaan',
      'Ketekunan',
      'Kejujuran',
      'Pengamalan nilai-nilai keislaman',
    ],
  },
  {
    id: 'ekstrakurikuler',
    icon: 'sports_soccer',
    title: 'Ekstrakurikuler dan Pengembangan Potensi Santri',
    paragraphs: [
      'LPI Dayah MADANI Al-Aziziyah juga memberikan ruang kepada santri untuk mengembangkan minat, bakat, keterampilan, dan kreativitas melalui berbagai kegiatan ekstrakurikuler.',
      'Program ekstrakurikuler dirancang sebagai pelengkap pendidikan akademik dan pendidikan dayah. Melalui kegiatan tersebut, santri memperoleh kesempatan untuk mengembangkan kemampuan nonakademik yang bermanfaat bagi kehidupan bermasyarakat.',
      'Pengembangan potensi santri diarahkan agar setiap individu mampu mengenali dan mengembangkan kemampuan dirinya secara optimal. Dengan demikian, pendidikan tidak hanya menghasilkan santri yang memiliki kemampuan dalam bidang keagamaan, tetapi juga memiliki keterampilan sosial, komunikasi, kepemimpinan, dan kemampuan beradaptasi dengan lingkungan.',
    ],
  },
  {
    id: 'putri',
    icon: 'diversity_1',
    title: 'Pengembangan Kompleks Pendidikan Putri',
    paragraphs: [
      'Sebagai bagian dari perkembangan kelembagaan, LPI Dayah MADANI Al-Aziziyah juga mengembangkan kompleks pendidikan khusus putri.',
      'Kompleks putri tersebut secara resmi diresmikan pada 5 Januari 2021 oleh Almukarram Syekh H. Hasanul Basri bin H. Gadeng atau yang dikenal dengan Abu MUDI.',
      'Pengembangan kompleks putri menjadi salah satu tonggak penting dalam perjalanan LPI Dayah MADANI Al-Aziziyah. Kehadiran kompleks tersebut memberikan ruang pendidikan dan pembinaan yang lebih terarah bagi santri putri.',
      'Dengan pengembangan tersebut, pelayanan pendidikan bagi santriwan dan santriwati dapat dilakukan secara lebih optimal sesuai dengan kebutuhan masing-masing.',
    ],
  },
  {
    id: 'arah',
    icon: 'trending_up',
    title: 'Arah Pengembangan Lembaga',
    paragraphs: [
      'LPI Dayah MADANI Al-Aziziyah memiliki orientasi pengembangan lembaga yang menempatkan pendidikan agama sebagai fondasi utama sekaligus membuka ruang bagi pengembangan ilmu pengetahuan dan keterampilan.',
      'Dalam jangka panjang, lembaga diarahkan menjadi pusat pendidikan Islam yang mampu melahirkan generasi yang berilmu, beriman, berakhlak mulia, mandiri, terampil, serta mampu menghadapi perkembangan zaman tanpa kehilangan identitas keislaman dan tradisi keilmuan dayah.',
      'Orientasi tersebut menjadi dasar dalam pengembangan berbagai program pendidikan, pembinaan santri, pengembangan bahasa, pendidikan formal, serta penguatan kelembagaan.',
    ],
  },
  {
    id: 'sosial',
    icon: 'handshake',
    title: 'Peran Sosial dan Keagamaan',
    paragraphs: [
      'LPI Dayah MADANI Al-Aziziyah tidak hanya berfungsi sebagai lembaga pendidikan, tetapi juga menjadi bagian dari kehidupan sosial dan keagamaan masyarakat.',
      'Sebagai lembaga yang tumbuh dari lingkungan masyarakat Aceh, dayah memiliki tanggung jawab moral untuk memberikan kontribusi terhadap pembinaan kehidupan keagamaan, pendidikan, sosial, dan moral masyarakat.',
      'Keberadaan pimpinan dan unsur pendidikan LPI Dayah MADANI Al-Aziziyah juga telah tercatat dalam berbagai kegiatan keagamaan dan sosial di Aceh.',
    ],
    note: 'Dokumen profil menyebutkan bahwa pimpinan lembaga, Abiya Dr. H. Muhammad Hatta, Lc., M.Ed., menjabat sebagai Wakil III Majelis Permusyawaratan Ulama (MPU) Provinsi Aceh serta terlibat dalam kegiatan kemasyarakatan, pengajian, dan dialog keislaman di Provinsi Aceh. Informasi ini bersumber dari dokumen profil.',
  },
  {
    id: 'nilai',
    icon: 'favorite',
    title: 'Nilai Dasar Lembaga',
    paragraphs: [
      'Dalam menjalankan seluruh proses pendidikan, LPI Dayah MADANI Al-Aziziyah berupaya menanamkan sejumlah nilai dasar kepada seluruh santri:',
    ],
    chips: [
      'Keimanan',
      'Keilmuan',
      'Akhlakul karimah',
      'Kedisiplinan',
      'Kemandirian',
      'Kebersamaan',
      'Pengabdian',
      'Pelestarian tradisi keilmuan',
    ],
  },
  {
    id: 'karakteristik',
    icon: 'workspace_premium',
    title: 'Karakteristik dan Keunggulan Lembaga',
    paragraphs: [
      'LPI Dayah MADANI Al-Aziziyah memiliki beberapa karakteristik yang menjadi kekuatan lembaga, antara lain:',
    ],
    ordered: [
      'Memadukan pendidikan Dayah Salafiyah dengan pendidikan formal.',
      'Mempertahankan pembelajaran berbasis kitab turats.',
      'Mengembangkan pendidikan SMP dan SMA secara terstruktur.',
      'Memberikan perhatian terhadap penguasaan Bahasa Arab dan Bahasa Inggris.',
      'Memberikan pendidikan dan pemondokan bagi santri yatim/piatu serta santri kurang mampu.',
      'Mengembangkan pendidikan bagi santri putra dan putri.',
      'Memiliki lingkungan pendidikan yang berorientasi pada pembentukan akhlak dan karakter.',
      'Terus mengembangkan sarana dan prasarana pendidikan.',
    ],
  },
  {
    id: 'komitmen',
    icon: 'verified',
    title: 'Komitmen Masa Depan',
    paragraphs: [
      'Ke depan, LPI Dayah MADANI Al-Aziziyah berkomitmen untuk terus meningkatkan kualitas pendidikan dan pelayanan kepada santri.',
      'Pengembangan lembaga diarahkan pada peningkatan kualitas sumber daya manusia, penguatan kurikulum, peningkatan mutu pembelajaran, pengembangan bahasa asing, peningkatan sarana dan prasarana, serta pembenahan sistem manajemen kelembagaan.',
      'Di tengah perubahan sosial dan perkembangan teknologi yang berlangsung sangat cepat, LPI Dayah MADANI Al-Aziziyah berupaya menjaga keseimbangan antara tradisi dan inovasi.',
      'Tradisi keilmuan dayah tetap menjadi akar pendidikan, sedangkan inovasi dan perkembangan ilmu pengetahuan menjadi sarana untuk memperkuat kemampuan santri dalam menghadapi tantangan masa depan.',
      'Dengan prinsip tersebut, lembaga berharap dapat melahirkan generasi yang tidak tercerabut dari akar tradisi keislamannya, tetapi pada saat yang sama mampu berinteraksi dengan perkembangan zaman secara cerdas, kritis, bijaksana, dan berakhlak.',
    ],
  },
  {
    id: 'penutup',
    icon: 'mosque',
    title: 'Penutup',
    paragraphs: [
      'Semoga LPI Dayah MADANI Al-Aziziyah senantiasa mendapatkan keberkahan, kekuatan, dan dukungan dari Allah Swt. dalam melanjutkan perjuangan pendidikan dan mencetak generasi penerus yang berilmu, beriman, berakhlak mulia, serta bermanfaat bagi umat dan bangsa.',
    ],
  },
]
