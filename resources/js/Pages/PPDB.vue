<template>
  <PublicLayout>
    <section class="w-full bg-surface-container-low px-gutter py-space-sm">
      <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-space-xs">
        <div class="flex items-center gap-space-xs font-label-sm text-label-sm text-on-surface-variant">
          <router-link to="/sma" class="hover:text-primary transition-colors">Beranda</router-link>
          <span class="material-symbols-outlined text-[13px] text-outline">chevron_right</span>
          <span class="text-on-surface font-semibold">Pendaftaran Santri Baru 2026–2027</span>
        </div>
        <span class="font-code-md text-code-md text-outline">PPDB SMA MADANI AL-AZIZIYAH</span>
      </div>
    </section>

    <section class="w-full max-w-7xl mx-auto px-gutter pt-space-xl pb-space-lg">
      <div class="flex flex-col gap-space-xs">
        <div class="inline-flex items-center gap-space-xs w-fit bg-primary-fixed text-primary px-space-md py-1 rounded-full font-label-sm text-label-sm">
          <span class="material-symbols-outlined text-[15px]">how_to_reg</span>
          <span>PENDAFTARAN SANTRI BARU</span>
        </div>
        <h1 class="font-display-lg text-display-lg text-on-surface font-bold tracking-tight">Pendaftaran Santri Baru Tahun Pelajaran 2026–2027</h1>
        <p class="font-body-md text-body-md text-on-surface-variant max-w-3xl leading-relaxed">Isi formulir berikut dengan data yang benar. Nomor pendaftaran dan tanggal pendaftaran dibuat otomatis oleh sistem. Data sensitif hanya digunakan untuk keperluan penerimaan santri.</p>
      </div>
    </section>

    <section class="w-full max-w-7xl mx-auto px-gutter pb-space-xl">
      <div v-if="!success" class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">
        <div class="lg:col-span-4 flex flex-col gap-space-md">
          <div class="bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-md">
            <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold mb-2">Gelombang Pendaftaran</h3>
            <div v-if="!loaded" class="font-body-sm text-body-sm text-on-surface-variant">Memuat gelombang…</div>
            <div v-else-if="!waves.length" class="font-body-sm text-body-sm text-on-surface-variant">Belum ada gelombang PPDB yang dibuka. Pantau laman ini secara berkala.</div>
            <div v-else class="flex flex-col gap-space-xs">
              <button v-for="w in waves" :key="w.id" @click="selectWave(w)" :class="selected && selected.id === w.id ? 'border-primary bg-primary-fixed/30' : 'border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low'" class="text-left border rounded-2xl p-space-sm transition-colors">
                <div class="font-title-md text-title-md text-on-surface font-semibold">{{ w.name }}</div>
                <div class="font-code-md text-code-md text-on-surface-variant">{{ fmtDate(w.start_date) }} — {{ fmtDate(w.end_date) }} • {{ statusLabel(w.status) }}</div>
                <p v-if="w.description" class="font-body-sm text-body-sm text-on-surface-variant mt-1">{{ w.description }}</p>
              </button>
            </div>
          </div>
          <div class="bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-md">
            <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold mb-2">Progres Pengisian</h3>
            <ol class="flex flex-col gap-1">
              <li v-for="(s, i) in steps" :key="s.key" class="flex items-center gap-2">
                <span :class="i < step ? 'bg-primary text-on-primary' : i === step ? 'bg-primary-container text-on-primary' : 'bg-surface-container text-on-surface-variant'" class="w-6 h-6 rounded-full flex items-center justify-center font-label-sm text-label-sm font-bold shrink-0">{{ i < step ? '✓' : (i + 1) }}</span>
                <span :class="i === step ? 'text-on-surface font-semibold' : 'text-on-surface-variant'" class="font-body-sm text-body-sm">{{ s.label }}</span>
              </li>
            </ol>
          </div>
        </div>

        <div class="lg:col-span-8">
          <div v-if="!selected" class="bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-sm p-space-lg text-center">
            <span class="material-symbols-outlined text-[40px] text-primary">calendar_month</span>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2">Pilih gelombang pendaftaran di samping untuk mulai mengisi formulir.</p>
          </div>
          <div v-else class="bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-md p-space-md sm:p-space-lg">
            <div class="flex items-center justify-between pb-2 border-b border-outline-variant mb-4">
              <h3 class="font-headline-md text-headline-md text-on-surface font-semibold">{{ steps[step].label }}</h3>
              <span class="font-code-md text-code-md text-on-surface-variant">Langkah {{ step + 1 }} / {{ steps.length }}</span>
            </div>

            <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden mb-4">
              <div class="h-full bg-primary transition-all duration-500" :style="{ width: ((step + 1) / steps.length * 100) + '%' }"></div>
            </div>

            <!-- Step 0: Identitas Santri -->
            <div v-if="steps[step].key === 'santri'" class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div class="md:col-span-2"><label class="lbl">Nama lengkap *</label><input v-model="form.name" class="inp" placeholder="Nama lengkap santri" /></div>
              <div><label class="lbl">NIK (16 digit) *</label><input v-model="form.nik" class="inp" inputmode="numeric" maxlength="16" placeholder="3271…" /></div>
              <div><label class="lbl">NISN</label><input v-model="form.nisn" class="inp" placeholder="NISN" /></div>
              <div><label class="lbl">Jenis kelamin *</label><select v-model="form.gender" class="inp"><option value="">Pilih</option><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></div>
              <div class="grid grid-cols-2 gap-2">
                <div><label class="lbl">Tempat lahir *</label><input v-model="form.birth_place" class="inp" /></div>
                <div><label class="lbl">Tanggal lahir *</label><input v-model="form.birth_date" type="date" class="inp" /></div>
              </div>
              <div><label class="lbl">Kewarganegaraan *</label><select v-model="form.nationality" class="inp"><option value="">Pilih</option><option>Indonesia</option><option>Lainnya</option></select></div>
              <div v-if="form.nationality === 'Lainnya'"><label class="lbl">Sebutkan *</label><input v-model="form.nationality_other" class="inp" /></div>
              <div><label class="lbl">Anak ke *</label><input v-model.number="form.child_order" type="number" min="1" class="inp" /></div>
              <div><label class="lbl">Jumlah saudara *</label><input v-model.number="form.sibling_count" type="number" min="0" class="inp" /></div>
              <div><label class="lbl">Status anak *</label><select v-model="form.orphan_status" class="inp"><option value="">Pilih</option><option>Yatim</option><option>Yatim Piatu</option><option>Piatu</option><option>Bukan Yatim</option></select></div>
              <div><label class="lbl">Bahasa sehari-hari *</label><select v-model="form.daily_language" class="inp"><option value="">Pilih</option><option>Bahasa Aceh/Daerah</option><option>Bahasa Indonesia</option><option>Lainnya</option></select></div>
              <div v-if="form.daily_language === 'Lainnya'"><label class="lbl">Sebutkan bahasa *</label><input v-model="form.daily_language_other" class="inp" /></div>
              <div class="md:col-span-2"><label class="lbl">Alamat lengkap *</label><textarea v-model="form.address" rows="2" class="inp"></textarea></div>
              <div><label class="lbl">Desa *</label><input v-model="form.village" class="inp" /></div>
              <div><label class="lbl">Kecamatan *</label><input v-model="form.district" class="inp" /></div>
              <div><label class="lbl">Kabupaten/Kota *</label><input v-model="form.city" class="inp" /></div>
              <div><label class="lbl">Provinsi *</label><input v-model="form.province" class="inp" /></div>
              <div><label class="lbl">Kode pos</label><input v-model="form.postal_code" class="inp" /></div>
              <div><label class="lbl">No. HP</label><input v-model="form.phone" class="inp" /></div>
              <div class="md:col-span-2"><label class="lbl">Email</label><input v-model="form.email" type="email" class="inp" /></div>
              <div class="md:col-span-2">
                <label class="lbl">Pas foto 3×4 latar biru (JPG/PNG/WebP, maks 2MB) *</label>
                <input type="file" accept=".jpg,.jpeg,.png,.webp" @change="onPhoto" class="inp" />
                <img v-if="photoPreview" :src="photoPreview" alt="Pratinjau pas foto" class="w-24 h-32 object-cover rounded-xl border border-outline-variant mt-2" />
              </div>
            </div>

            <!-- Step 1: Sekolah asal -->
            <div v-if="steps[step].key === 'sekolah'" class="grid grid-cols-1 gap-3">
              <div><label class="lbl">Nama SD/MI atau SMP/MTs *</label><input v-model="form.origin_school_name" class="inp" /></div>
              <div><label class="lbl">Alamat sekolah asal</label><textarea v-model="form.origin_school_address" rows="2" class="inp"></textarea></div>
            </div>

            <!-- Step 2: Ayah -->
            <div v-if="steps[step].key === 'ayah'" class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div><label class="lbl">Nomor Kartu Keluarga *</label><input v-model="form.father_family_card" class="inp" /></div>
              <div><label class="lbl">Nama ayah kandung *</label><input v-model="form.father_name" class="inp" /></div>
              <div><label class="lbl">NIK ayah</label><input v-model="form.father_nik" class="inp" /></div>
              <div class="grid grid-cols-2 gap-2">
                <div><label class="lbl">Tempat lahir</label><input v-model="form.father_birth_place" class="inp" /></div>
                <div><label class="lbl">Tanggal lahir</label><input v-model="form.father_birth_date" type="date" class="inp" /></div>
              </div>
              <div><label class="lbl">Kewarganegaraan</label><select v-model="form.father_nationality" class="inp"><option value="">Pilih</option><option>Indonesia</option><option>Lainnya</option></select></div>
              <div v-if="form.father_nationality === 'Lainnya'"><label class="lbl">Sebutkan *</label><input v-model="form.father_nationality_other" class="inp" /></div>
              <div><label class="lbl">Pendidikan terakhir</label><input v-model="form.father_education" class="inp" placeholder="SD/SMP/SMA/D3/S1/…" /></div>
              <div><label class="lbl">Pekerjaan</label><input v-model="form.father_occupation" class="inp" /></div>
              <div class="md:col-span-2"><label class="lbl">No. telepon/HP</label><input v-model="form.father_phone" class="inp" /></div>
              <div class="md:col-span-2 flex items-center gap-2"><input v-model="copyFather" type="checkbox" id="cpF" class="w-4 h-4" /><label for="cpF" class="font-body-sm text-body-sm">Alamat sama dengan santri</label></div>
              <div class="md:col-span-2"><label class="lbl">Alamat lengkap</label><textarea v-model="form.father_address" rows="2" class="inp"></textarea></div>
              <div><label class="lbl">Desa</label><input v-model="form.father_village" class="inp" /></div>
              <div><label class="lbl">Kecamatan</label><input v-model="form.father_district" class="inp" /></div>
              <div><label class="lbl">Kabupaten/Kota</label><input v-model="form.father_city" class="inp" /></div>
              <div><label class="lbl">Provinsi</label><input v-model="form.father_province" class="inp" /></div>
              <div><label class="lbl">Kode pos</label><input v-model="form.father_postal_code" class="inp" /></div>
              <div class="md:col-span-2 flex items-center gap-2"><input v-model="form.father_is_deceased" type="checkbox" id="fdD" class="w-4 h-4" /><label for="fdD" class="font-body-sm text-body-sm">Sudah meninggal dunia</label></div>
              <template v-if="form.father_is_deceased">
                <div><label class="lbl">Tanggal meninggal *</label><input v-model="form.father_death_date" type="date" class="inp" /></div>
                <div><label class="lbl">Tempat meninggal *</label><input v-model="form.father_death_place" class="inp" /></div>
                <div class="md:col-span-2"><label class="lbl">Sebab meninggal *</label><input v-model="form.father_death_cause" class="inp" /></div>
              </template>
            </div>

            <!-- Step 3: Ibu -->
            <div v-if="steps[step].key === 'ibu'" class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div><label class="lbl">Nomor Kartu Keluarga *</label><input v-model="form.mother_family_card" class="inp" /></div>
              <div><label class="lbl">Nama ibu kandung *</label><input v-model="form.mother_name" class="inp" /></div>
              <div><label class="lbl">NIK ibu</label><input v-model="form.mother_nik" class="inp" /></div>
              <div class="grid grid-cols-2 gap-2">
                <div><label class="lbl">Tempat lahir</label><input v-model="form.mother_birth_place" class="inp" /></div>
                <div><label class="lbl">Tanggal lahir</label><input v-model="form.mother_birth_date" type="date" class="inp" /></div>
              </div>
              <div><label class="lbl">Kewarganegaraan</label><select v-model="form.mother_nationality" class="inp"><option value="">Pilih</option><option>Indonesia</option><option>Lainnya</option></select></div>
              <div v-if="form.mother_nationality === 'Lainnya'"><label class="lbl">Sebutkan *</label><input v-model="form.mother_nationality_other" class="inp" /></div>
              <div><label class="lbl">Pendidikan terakhir</label><input v-model="form.mother_education" class="inp" /></div>
              <div><label class="lbl">Pekerjaan</label><input v-model="form.mother_occupation" class="inp" /></div>
              <div class="md:col-span-2"><label class="lbl">No. telepon/HP</label><input v-model="form.mother_phone" class="inp" /></div>
              <div class="md:col-span-2 flex items-center gap-2"><input v-model="copyMother" type="checkbox" id="cpM" class="w-4 h-4" /><label for="cpM" class="font-body-sm text-body-sm">Alamat sama dengan santri</label></div>
              <div class="md:col-span-2"><label class="lbl">Alamat lengkap</label><textarea v-model="form.mother_address" rows="2" class="inp"></textarea></div>
              <div><label class="lbl">Desa</label><input v-model="form.mother_village" class="inp" /></div>
              <div><label class="lbl">Kecamatan</label><input v-model="form.mother_district" class="inp" /></div>
              <div><label class="lbl">Kabupaten/Kota</label><input v-model="form.mother_city" class="inp" /></div>
              <div><label class="lbl">Provinsi</label><input v-model="form.mother_province" class="inp" /></div>
              <div><label class="lbl">Kode pos</label><input v-model="form.mother_postal_code" class="inp" /></div>
              <div class="md:col-span-2 flex items-center gap-2"><input v-model="form.mother_is_deceased" type="checkbox" id="mdD" class="w-4 h-4" /><label for="mdD" class="font-body-sm text-body-sm">Sudah meninggal dunia</label></div>
              <template v-if="form.mother_is_deceased">
                <div><label class="lbl">Tanggal meninggal *</label><input v-model="form.mother_death_date" type="date" class="inp" /></div>
                <div><label class="lbl">Tempat meninggal *</label><input v-model="form.mother_death_place" class="inp" /></div>
                <div class="md:col-span-2"><label class="lbl">Sebab meninggal *</label><input v-model="form.mother_death_cause" class="inp" /></div>
              </template>
            </div>

            <!-- Step 4: Pembiaya + Darurat -->
            <div v-if="steps[step].key === 'biaya'" class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div class="md:col-span-2 flex flex-wrap gap-2">
                <button @click="copyFinancier('ayah')" type="button" class="px-4 py-1.5 rounded-full border border-outline-variant font-body-sm text-body-sm hover:bg-surface-container-low">Salin dari Ayah</button>
                <button @click="copyFinancier('ibu')" type="button" class="px-4 py-1.5 rounded-full border border-outline-variant font-body-sm text-body-sm hover:bg-surface-container-low">Salin dari Ibu</button>
              </div>
              <div class="md:col-span-2"><label class="lbl">Nama pembiaya *</label><input v-model="form.financier_name" class="inp" /></div>
              <div><label class="lbl">Hubungan dengan santri *</label><input v-model="form.financier_relation" class="inp" placeholder="Ayah/Ibu/Wali/…" /></div>
              <div><label class="lbl">No. telepon/HP</label><input v-model="form.financier_phone" class="inp" /></div>
              <div><label class="lbl">Tempat lahir</label><input v-model="form.financier_birth_place" class="inp" /></div>
              <div><label class="lbl">Tanggal lahir</label><input v-model="form.financier_birth_date" type="date" class="inp" /></div>
              <div><label class="lbl">Kewarganegaraan</label><select v-model="form.financier_nationality" class="inp"><option value="">Pilih</option><option>Indonesia</option><option>Lainnya</option></select></div>
              <div v-if="form.financier_nationality === 'Lainnya'"><label class="lbl">Sebutkan *</label><input v-model="form.financier_nationality_other" class="inp" /></div>
              <div><label class="lbl">Pendidikan</label><input v-model="form.financier_education" class="inp" /></div>
              <div><label class="lbl">Pekerjaan</label><input v-model="form.financier_occupation" class="inp" /></div>
              <div class="md:col-span-2"><label class="lbl">Alamat lengkap</label><textarea v-model="form.financier_address" rows="2" class="inp"></textarea></div>
              <div class="md:col-span-2 border-t border-outline-variant pt-3 mt-1"><h4 class="font-headline-sm text-headline-sm font-semibold">Keluarga Terdekat yang Mudah Dihubungi</h4></div>
              <div class="md:col-span-2"><label class="lbl">Nama *</label><input v-model="form.emergency_name" class="inp" /></div>
              <div><label class="lbl">Hubungan *</label><input v-model="form.emergency_relation" class="inp" /></div>
              <div><label class="lbl">No. telepon/HP *</label><input v-model="form.emergency_phone" class="inp" /></div>
              <div><label class="lbl">Tempat lahir</label><input v-model="form.emergency_birth_place" class="inp" /></div>
              <div><label class="lbl">Tanggal lahir</label><input v-model="form.emergency_birth_date" type="date" class="inp" /></div>
              <div><label class="lbl">Pendidikan</label><input v-model="form.emergency_education" class="inp" /></div>
              <div><label class="lbl">Pekerjaan</label><input v-model="form.emergency_occupation" class="inp" /></div>
              <div class="md:col-span-2"><label class="lbl">Alamat lengkap</label><textarea v-model="form.emergency_address" rows="2" class="inp"></textarea></div>
            </div>

            <!-- Step 5: Wali + Mahram -->
            <div v-if="steps[step].key === 'wali'" class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div class="md:col-span-2 flex items-center gap-2"><input v-model="form.guardian_not_required" type="checkbox" id="gNR" class="w-4 h-4" /><label for="gNR" class="font-body-sm text-body-sm">Tidak ada wali / tidak diperlukan</label></div>
              <template v-if="!form.guardian_not_required">
                <div class="md:col-span-2"><label class="lbl">Nama wali *</label><input v-model="form.guardian_name" class="inp" /></div>
                <div><label class="lbl">Hubungan *</label><input v-model="form.guardian_relation" class="inp" /></div>
                <div><label class="lbl">No. telepon/HP</label><input v-model="form.guardian_phone" class="inp" /></div>
                <div><label class="lbl">Tempat lahir</label><input v-model="form.guardian_birth_place" class="inp" /></div>
                <div><label class="lbl">Tanggal lahir</label><input v-model="form.guardian_birth_date" type="date" class="inp" /></div>
                <div><label class="lbl">Pendidikan</label><input v-model="form.guardian_education" class="inp" /></div>
                <div><label class="lbl">Pekerjaan</label><input v-model="form.guardian_occupation" class="inp" /></div>
                <div class="md:col-span-2"><label class="lbl">Alamat lengkap</label><textarea v-model="form.guardian_address" rows="2" class="inp"></textarea></div>
              </template>
              <template v-if="form.gender === 'P'">
                <div class="md:col-span-2 border-t border-outline-variant pt-3 mt-1"><h4 class="font-headline-sm text-headline-sm font-semibold">Mahram Santri Putri</h4></div>
                <div><label class="lbl">Nama mahram</label><input v-model="form.mahram_name" class="inp" /></div>
                <div><label class="lbl">Hubungan</label><input v-model="form.mahram_relation" class="inp" /></div>
                <div><label class="lbl">No. telepon/HP</label><input v-model="form.mahram_phone" class="inp" /></div>
                <div><label class="lbl">Tempat lahir</label><input v-model="form.mahram_birth_place" class="inp" /></div>
                <div><label class="lbl">Tanggal lahir</label><input v-model="form.mahram_birth_date" type="date" class="inp" /></div>
                <div class="md:col-span-2"><label class="lbl">Alamat lengkap</label><textarea v-model="form.mahram_address" rows="2" class="inp"></textarea></div>
              </template>
            </div>

            <!-- Step 6: Kesehatan + Pernyataan -->
            <div v-if="steps[step].key === 'sehat'" class="grid grid-cols-1 gap-3">
              <div><label class="lbl">Golongan darah *</label><select v-model="form.blood_type" class="inp"><option value="">Pilih</option><option>A</option><option>B</option><option>AB</option><option>O</option><option>Tidak Diketahui</option></select></div>
              <div>
                <label class="lbl">Riwayat penyakit (pilih yang sesuai)</label>
                <div class="flex flex-wrap gap-2 mt-1">
                  <label v-for="d in diseases" :key="d" class="inline-flex items-center gap-1 px-3 py-1.5 border rounded-full font-body-sm text-body-sm cursor-pointer" :class="form.medical_history.includes(d) ? 'border-primary bg-primary-fixed/30' : 'border-outline-variant'">
                    <input v-model="form.medical_history" :value="d" type="checkbox" class="sr-only" />{{ d }}
                  </label>
                </div>
              </div>
              <div v-if="form.medical_history.includes('Lainnya')"><label class="lbl">Sebutkan penyakit lainnya *</label><input v-model="form.medical_history_other" class="inp" /></div>
              <div><label class="lbl">Keterangan kesehatan</label><textarea v-model="form.medical_history_note" rows="2" class="inp" placeholder="Keterangan tambahan bila ada"></textarea></div>
              <div class="border-t border-outline-variant pt-3">
                <label class="flex items-start gap-2 font-body-sm text-body-sm text-on-surface">
                  <input v-model="form.consent" type="checkbox" class="w-4 h-4 mt-1" />
                  <span>Saya menyatakan data yang dimasukkan benar dan menyetujui pemrosesan data untuk kebutuhan penerimaan santri. Tanda tangan resmi tetap mengikuti dokumen cetak panitia bila diwajibkan. *</span>
                </label>
              </div>
              <div><label class="lbl">Nama orang tua/wali sebagai konfirmasi *</label><input v-model="form.declarant_name" class="inp" placeholder="Nama terang" /></div>
            </div>

            <!-- Step 7: Ringkasan -->
            <div v-if="steps[step].key === 'ringkas'" class="flex flex-col gap-3">
              <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-space-sm font-body-sm text-body-sm">
                <p><strong>Gelombang:</strong> {{ selected?.name }}</p>
                <p><strong>Nama santri:</strong> {{ form.name }} ({{ form.gender === 'L' ? 'Laki-laki' : form.gender === 'P' ? 'Perempuan' : '—' }})</p>
                <p><strong>NIK / NISN:</strong> {{ form.nik || '—' }} / {{ form.nisn || '—' }}</p>
                <p><strong>TTL:</strong> {{ form.birth_place || '—' }}, {{ fmtDate(form.birth_date) }}</p>
                <p><strong>Sekolah asal:</strong> {{ form.origin_school_name || '—' }}</p>
                <p><strong>Ayah:</strong> {{ form.father_name || '—' }} • <strong>Ibu:</strong> {{ form.mother_name || '—' }}</p>
                <p><strong>Pembiaya:</strong> {{ form.financier_name || '—' }} ({{ form.financier_relation || '—' }})</p>
                <p><strong>Darurat:</strong> {{ form.emergency_name || '—' }} ({{ form.emergency_relation || '—' }})</p>
                <p><strong>Gol. darah:</strong> {{ form.blood_type || '—' }} • <strong>Riwayat:</strong> {{ form.medical_history.length ? form.medical_history.join(', ') : '—' }}</p>
              </div>
              <p class="font-body-sm text-body-sm text-on-surface-variant">Periksa kembali data sebelum mengirim. Nomor pendaftaran dibuat otomatis oleh sistem setelah data tersimpan.</p>
            </div>

            <p v-if="formError" class="text-xs text-error bg-error-container border border-error rounded px-2 py-1 mt-3">{{ formError }}</p>
            <div v-if="backendErrors.length" class="mt-2 flex flex-col gap-1">
              <p v-for="(e, i) in backendErrors" :key="i" class="text-xs text-error">{{ e }}</p>
            </div>

            <div class="flex flex-wrap gap-2 mt-4">
              <button v-if="step > 0" @click="prev" type="button" class="px-5 py-2 bg-surface-container-low text-on-surface border border-outline-variant rounded-full text-sm font-semibold">Sebelumnya</button>
              <button v-if="step < steps.length - 1" @click="next" type="button" class="px-5 py-2 bg-primary text-on-primary rounded-full text-sm font-semibold hover:bg-primary-hover">Selanjutnya</button>
              <button v-else @click="submit" :disabled="submitting" type="button" class="px-5 py-2 bg-primary text-on-primary rounded-full text-sm font-semibold disabled:opacity-50 hover:bg-primary-hover">{{ submitting ? 'Mengirim…' : 'Kirim Pendaftaran' }}</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Sukses -->
      <div v-if="success" class="max-w-3xl mx-auto bg-surface-container-lowest border border-outline-variant rounded-[28px] shadow-md p-space-lg text-center flex flex-col gap-3 items-center">
        <span class="w-14 h-14 rounded-full bg-primary-fixed text-primary flex items-center justify-center"><span class="material-symbols-outlined text-[28px]">check_circle</span></span>
        <h2 class="font-headline-lg text-headline-lg font-bold">Pendaftaran Berhasil Dikirim</h2>
        <p class="font-body-md text-body-md text-on-surface-variant">Status: <strong>Menunggu verifikasi panitia</strong>. Keputusan diterima/ditolak diumumkan kemudian.</p>
        <div class="w-full bg-surface-container-low border border-outline-variant rounded-2xl p-space-md text-left font-body-md text-body-md grid grid-cols-1 sm:grid-cols-2 gap-2">
          <p><strong>Nomor pendaftaran:</strong><br />{{ success.registration_number }}</p>
          <p><strong>Nama santri:</strong><br />{{ success.name }}</p>
          <p><strong>Gelombang:</strong><br />{{ success.wave?.name }}</p>
          <p><strong>Tanggal:</strong><br />{{ fmtDateTime(success.registered_at) }}</p>
        </div>
        <p class="font-body-sm text-body-sm text-on-surface-variant">Simpan nomor pendaftaran. Pantau pengumuman resmi sekolah untuk jadwal, berkas, dan tahapan seleksi.</p>
        <div class="flex flex-wrap justify-center gap-2">
          <button @click="printBukti" class="px-5 py-2 bg-primary text-on-primary rounded-full text-sm font-semibold hover:bg-primary-hover">Cetak Bukti</button>
          <router-link to="/sma/ppdb" @click="resetAll" class="px-5 py-2 bg-surface-container-low border border-outline-variant rounded-full text-sm font-semibold">Daftar Lain</router-link>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import PublicLayout from '../Components/PublicLayout.vue'

const waves = ref([])
const loaded = ref(false)
const selected = ref(null)
const step = ref(0)
const submitting = ref(false)
const formError = ref('')
const backendErrors = ref([])
const success = ref(null)
const photoFile = ref(null)
const photoPreview = ref('')
const copyFather = ref(false)
const copyMother = ref(false)

const diseases = ['TBC', 'Hepatitis', 'Asthma/Sesak', 'Epilepsi/Ayan', 'Kanker', 'Tipes', 'Tidak Ada', 'Lainnya']

const steps = [
  { key: 'santri', label: 'Identitas Santri' },
  { key: 'sekolah', label: 'Sekolah Asal' },
  { key: 'ayah', label: 'Identitas Ayah' },
  { key: 'ibu', label: 'Identitas Ibu' },
  { key: 'biaya', label: 'Pembiaya & Darurat' },
  { key: 'wali', label: 'Wali & Mahram' },
  { key: 'sehat', label: 'Kesehatan & Pernyataan' },
  { key: 'ringkas', label: 'Ringkasan' },
]

const blank = () => ({
  wave_id: null, name: '', nik: '', gender: '', birth_place: '', birth_date: '',
  nationality: '', nationality_other: '', child_order: null, sibling_count: null,
  orphan_status: '', daily_language: '', daily_language_other: '',
  nisn: '', phone: '', email: '', address: '', village: '', district: '', city: '', province: '', postal_code: '',
  origin_school_name: '', origin_school_address: '',
  father_family_card: '', father_name: '', father_nik: '', father_birth_place: '', father_birth_date: '',
  father_nationality: '', father_nationality_other: '', father_education: '', father_occupation: '',
  father_phone: '', father_address: '', father_village: '', father_district: '', father_city: '',
  father_province: '', father_postal_code: '', father_is_deceased: false, father_death_date: '',
  father_death_place: '', father_death_cause: '',
  mother_family_card: '', mother_name: '', mother_nik: '', mother_birth_place: '', mother_birth_date: '',
  mother_nationality: '', mother_nationality_other: '', mother_education: '', mother_occupation: '',
  mother_phone: '', mother_address: '', mother_village: '', mother_district: '', mother_city: '',
  mother_province: '', mother_postal_code: '', mother_is_deceased: false, mother_death_date: '',
  mother_death_place: '', mother_death_cause: '',
  financier_source: '', financier_name: '', financier_birth_place: '', financier_birth_date: '',
  financier_nationality: '', financier_nationality_other: '', financier_education: '', financier_occupation: '',
  financier_phone: '', financier_address: '', financier_village: '', financier_district: '', financier_city: '',
  financier_province: '', financier_postal_code: '', financier_relation: '',
  emergency_name: '', emergency_birth_place: '', emergency_birth_date: '', emergency_nationality: '',
  emergency_nationality_other: '', emergency_education: '', emergency_occupation: '', emergency_phone: '',
  emergency_address: '', emergency_relation: '',
  guardian_not_required: false, guardian_name: '', guardian_birth_place: '', guardian_birth_date: '',
  guardian_nationality: '', guardian_nationality_other: '', guardian_education: '', guardian_occupation: '',
  guardian_phone: '', guardian_address: '', guardian_relation: '',
  mahram_name: '', mahram_birth_place: '', mahram_birth_date: '', mahram_nationality: '',
  mahram_nationality_other: '', mahram_education: '', mahram_occupation: '', mahram_phone: '',
  mahram_address: '', mahram_relation: '',
  blood_type: '', medical_history: [], medical_history_other: '', medical_history_note: '',
  declarant_name: '', consent: false,
})
const form = ref(blank())

const statusMap = { buka: 'Dibuka', tutup: 'Ditutup', archived: 'Arsip' }
function statusLabel(s) { return statusMap[s] || s || '—' }
function fmtDate(d) { return d ? String(d).slice(0, 10) : '—' }
function fmtDateTime(d) { try { return new Date(d).toLocaleString('id-ID') } catch { return d || '—' } }

onMounted(async () => {
  try {
    const r = await fetch('/api/ppdb-waves/public')
    if (r.ok) waves.value = await r.json()
  } catch { /* abaikan */ } finally { loaded.value = true }
})

watch(copyFather, (v) => {
  if (v) {
    form.value.father_address = form.value.address
    form.value.father_village = form.value.village
    form.value.father_district = form.value.district
    form.value.father_city = form.value.city
    form.value.father_province = form.value.province
    form.value.father_postal_code = form.value.postal_code
  }
})
watch(copyMother, (v) => {
  if (v) {
    form.value.mother_address = form.value.address
    form.value.mother_village = form.value.village
    form.value.mother_district = form.value.district
    form.value.mother_city = form.value.city
    form.value.mother_province = form.value.province
    form.value.mother_postal_code = form.value.postal_code
  }
})

function selectWave(w) {
  selected.value = w
  form.value.wave_id = w.id
  step.value = 0
  success.value = null
  formError.value = ''
  backendErrors.value = []
}

function onPhoto(e) {
  const f = e.target.files?.[0]
  formError.value = ''
  if (!f) return
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(f.type)) { formError.value = 'Pas foto harus JPG/PNG/WebP.'; return }
  if (f.size > 2 * 1024 * 1024) { formError.value = 'Ukuran pas foto maksimal 2MB.'; return }
  photoFile.value = f
  photoPreview.value = URL.createObjectURL(f)
}

function copyFinancier(src) {
  const s = src === 'ayah'
    ? { name: form.value.father_name, bp: form.value.father_birth_place, bd: form.value.father_birth_date, nat: form.value.father_nationality, edu: form.value.father_education, occ: form.value.father_occupation, ph: form.value.father_phone, ad: form.value.father_address, rel: 'Ayah' }
    : { name: form.value.mother_name, bp: form.value.mother_birth_place, bd: form.value.mother_birth_date, nat: form.value.mother_nationality, edu: form.value.mother_education, occ: form.value.mother_occupation, ph: form.value.mother_phone, ad: form.value.mother_address, rel: 'Ibu' }
  form.value.financier_source = src
  form.value.financier_name = s.name || ''
  form.value.financier_birth_place = s.bp || ''
  form.value.financier_birth_date = s.bd || ''
  form.value.financier_nationality = s.nat || ''
  form.value.financier_education = s.edu || ''
  form.value.financier_occupation = s.occ || ''
  form.value.financier_phone = s.ph || ''
  form.value.financier_address = s.ad || ''
  form.value.financier_relation = s.rel
}

function req(v, label) { return v === null || v === undefined || v === '' ? `${label} wajib diisi.` : '' }

function validateStep() {
  const f = form.value
  const errs = []
  const k = steps[step.value].key
  if (k === 'santri') {
    ;['name|Nama lengkap', 'nik|NIK', 'gender|Jenis kelamin', 'birth_place|Tempat lahir', 'birth_date|Tanggal lahir', 'nationality|Kewarganegaraan', 'orphan_status|Status anak', 'daily_language|Bahasa sehari-hari', 'address|Alamat', 'village|Desa', 'district|Kecamatan', 'city|Kabupaten/Kota', 'province|Provinsi'].forEach((p) => {
      const [field, label] = p.split('|')
      const e = req(f[field], label)
      if (e) errs.push(e)
    })
    if (f.nik && !/^[0-9]{16}$/.test(f.nik)) errs.push('NIK harus 16 digit angka.')
    if (f.nationality === 'Lainnya' && !f.nationality_other) errs.push('Kewarganegaraan lainnya wajib dijelaskan.')
    if (f.child_order === null || f.child_order === '') errs.push('Anak ke wajib diisi.')
    if (f.sibling_count === null || f.sibling_count === '') errs.push('Jumlah saudara wajib diisi.')
    if (f.daily_language === 'Lainnya' && !f.daily_language_other) errs.push('Bahasa lainnya wajib dijelaskan.')
    if (!photoFile.value) errs.push('Pas foto 3×4 wajib diunggah.')
  }
  if (k === 'sekolah') {
    const e = req(f.origin_school_name, 'Nama sekolah asal')
    if (e) errs.push(e)
  }
  if (k === 'ayah') {
    ;['father_family_card|Nomor KK ayah', 'father_name|Nama ayah'].forEach((p) => {
      const [field, label] = p.split('|')
      const e = req(f[field], label)
      if (e) errs.push(e)
    })
    if (f.father_is_deceased && (!f.father_death_date || !f.father_death_place || !f.father_death_cause)) errs.push('Data meninggal ayah wajib lengkap.')
  }
  if (k === 'ibu') {
    ;['mother_family_card|Nomor KK ibu', 'mother_name|Nama ibu'].forEach((p) => {
      const [field, label] = p.split('|')
      const e = req(f[field], label)
      if (e) errs.push(e)
    })
    if (f.mother_is_deceased && (!f.mother_death_date || !f.mother_death_place || !f.mother_death_cause)) errs.push('Data meninggal ibu wajib lengkap.')
  }
  if (k === 'biaya') {
    ;['financier_name|Nama pembiaya', 'financier_relation|Hubungan pembiaya', 'emergency_name|Nama darurat', 'emergency_relation|Hubungan darurat', 'emergency_phone|No. HP darurat'].forEach((p) => {
      const [field, label] = p.split('|')
      const e = req(f[field], label)
      if (e) errs.push(e)
    })
  }
  if (k === 'wali') {
    if (!f.guardian_not_required) {
      const a = req(f.guardian_name, 'Nama wali')
      if (a) errs.push(a)
      const b = req(f.guardian_relation, 'Hubungan wali')
      if (b) errs.push(b)
    }
  }
  if (k === 'sehat') {
    const e = req(f.blood_type, 'Golongan darah')
    if (e) errs.push(e)
    if (f.medical_history.includes('Lainnya') && !f.medical_history_other) errs.push('Penyakit lainnya wajib dijelaskan.')
    if (!f.consent) errs.push('Pernyataan persetujuan wajib disetujui.')
    const d = req(f.declarant_name, 'Nama orang tua/wali')
    if (d) errs.push(d)
  }
  return errs
}

function next() {
  formError.value = ''
  const errs = validateStep()
  if (errs.length) { formError.value = errs[0]; backendErrors.value = errs; return }
  backendErrors.value = []
  if (step.value < steps.length - 1) step.value++
}
function prev() { formError.value = ''; backendErrors.value = []; if (step.value > 0) step.value-- }

async function submit() {
  formError.value = ''
  backendErrors.value = []
  if (submitting.value) return
  const errs = validateStep()
  if (errs.length) { formError.value = errs[0]; backendErrors.value = errs; return }
  submitting.value = true
  try {
    const fd = new FormData()
    const f = form.value
    Object.keys(f).forEach((k) => {
      if (k === 'consent') { fd.append('consent', f.consent ? '1' : '0'); return }
      if (k === 'medical_history') { f.medical_history.forEach((m) => fd.append('medical_history[]', m)); return }
      if (k === 'father_is_deceased' || k === 'mother_is_deceased' || k === 'guardian_not_required') { fd.append(k, f[k] ? '1' : '0'); return }
      if (f[k] !== null && f[k] !== undefined && f[k] !== '') fd.append(k, f[k])
    })
    if (photoFile.value) fd.append('photo', photoFile.value)
    const r = await fetch('/api/ppdb-registrations', { method: 'POST', body: fd })
    const j = await r.json().catch(() => ({}))
    if (!r.ok) {
      const list = j.errors ? Object.values(j.errors).flat() : [j.message || 'Gagal mengirim pendaftaran']
      backendErrors.value = list
      throw new Error(list[0])
    }
    success.value = j
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (e) {
    formError.value = e.message
  } finally {
    submitting.value = false
  }
}

function resetAll() {
  form.value = blank()
  photoFile.value = null
  photoPreview.value = ''
  step.value = 0
  success.value = null
  selected.value = null
}

function printBukti() { window.print() }
</script>

<style scoped>
.lbl { display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; color: var(--color-on-surface); }
.inp { width: 100%; padding: 10px 14px; border: 1px solid var(--color-outline-variant); border-radius: 10px; background: #fff; font-size: 14px; outline: none; }
.inp:focus { border-color: var(--color-primary-container); box-shadow: 0 0 0 3px rgba(11, 35, 71, 0.12); }
</style>
