<template>
  <LpiLayout>
    <div class="ppdb-page">
      <!-- SUBHERO -->
      <section class="ppdb-subhero">
        <div class="ppdb-container ppdb-subhero-inner">
          <router-link to="/ppdb" class="ppdb-back">
            <span class="material-symbols-outlined">arrow_back</span>
            Kembali ke info PPDB
          </router-link>
          <span class="ppdb-hero-eyebrow ppdb-anim" style="--d: 0ms">Formulir Pendaftaran</span>
          <h1 class="ppdb-subhero-title ppdb-anim" style="--d: 70ms">Pendaftaran Santri Baru</h1>
          <p class="ppdb-subhero-sub ppdb-anim" style="--d: 140ms">Tahun Ajaran 2027-2028 M / 1448-1449 H</p>
        </div>
      </section>

      <!-- PILIH GELOMBANG -->
      <section v-if="!success && !selected" class="ppdb-section">
        <div class="ppdb-container">
          <div class="ppdb-section-head ppdb-anim">
            <span class="ppdb-label">Langkah 1</span>
            <h2 class="ppdb-section-title">Pilih Gelombang Pendaftaran</h2>
            <p class="ppdb-section-sub">Pilih gelombang yang sedang dibuka untuk mulai mengisi formulir.</p>
          </div>

          <div v-if="!loaded" class="ppdb-card ppdb-empty">Memuat gelombang…</div>
          <div v-else-if="!waves.length" class="ppdb-card ppdb-empty">Belum ada gelombang PPDB yang dibuka. Pantau laman ini secara berkala.</div>
          <div v-else class="ppdb-info-grid">
            <button
              v-for="(w, wi) in waves"
              :key="w.id"
              type="button"
              class="ppdb-card ppdb-wave-card ppdb-anim"
              :style="{ '--d': (wi * 80) + 'ms' }"
              @click="selectWave(w)"
            >
              <span class="ppdb-label ppdb-label--sm">Gelombang {{ wi + 1 }}</span>
              <h3 class="ppdb-wave-title">{{ w.name }}</h3>
              <p class="ppdb-wave-date">{{ fmtDate(w.start_date) }} — {{ fmtDate(w.end_date) }}</p>
              <span class="ppdb-badge">{{ statusLabel(w.status) }}</span>
              <p v-if="w.description" class="ppdb-wave-desc">{{ w.description }}</p>
              <span class="ppdb-wave-action">
                Pilih gelombang
                <span class="material-symbols-outlined">arrow_forward</span>
              </span>
            </button>

            <article class="ppdb-card ppdb-schedule-card ppdb-anim" :style="{ '--d': (waves.length * 80) + 'ms' }">
              <span class="ppdb-label ppdb-label--sm">Jadwal Pendaftaran</span>
              <p class="ppdb-schedule-text"><strong>{{ scheduleDays }}</strong></p>
              <p class="ppdb-schedule-text"><strong>{{ scheduleHours }}</strong></p>
            </article>
          </div>
        </div>
      </section>

      <!-- FORMULIR -->
      <section v-if="!success && selected" class="ppdb-section">
        <div class="ppdb-container">
          <div class="ppdb-layout">
            <aside class="ppdb-sidebar">
              <div class="ppdb-card ppdb-side-card">
                <h3 class="ppdb-side-title">Gelombang</h3>
                <div class="ppdb-side-row">
                  <span class="ppdb-side-icon">
                    <span class="material-symbols-outlined">calendar_month</span>
                  </span>
                  <div>
                    <p class="ppdb-side-name">{{ selected.name }}</p>
                    <p class="ppdb-side-meta">{{ fmtDate(selected.start_date) }} — {{ fmtDate(selected.end_date) }}</p>
                  </div>
                </div>
                <button type="button" class="ppdb-btn ppdb-btn--ghost ppdb-btn--sm ppdb-side-change" @click="changeWave">Ganti gelombang</button>
              </div>

              <div class="ppdb-card ppdb-side-card">
                <h3 class="ppdb-side-title">Jalur Pendaftaran</h3>
                <div class="ppdb-side-row">
                  <span class="ppdb-side-icon">
                    <span class="material-symbols-outlined">{{ jenjangOptions.find((j) => j.key === form.jenjang)?.icon || 'route' }}</span>
                  </span>
                  <div>
                    <p class="ppdb-side-name">{{ form.jenjang ? jenjangLabel(form.jenjang) : 'Belum dipilih' }}</p>
                    <p class="ppdb-side-meta">Jenjang / jalur terpilih</p>
                  </div>
                </div>
                <p v-if="form.jenjang === 'salafiyah'" class="ppdb-info-line">Program khusus santri putri. Jenis kelamin otomatis diisi Perempuan.</p>
              </div>

              <div class="ppdb-card ppdb-side-card">
                <h3 class="ppdb-side-title">Progres Pengisian</h3>
                <ol class="ppdb-steps">
                  <li v-for="(s, i) in steps" :key="s.key" class="ppdb-step">
                    <span
                      class="ppdb-step-dot"
                      :class="i < step ? 'is-done' : i === step ? 'is-current' : ''"
                    >{{ i < step ? '✓' : (i + 1) }}</span>
                    <span class="ppdb-step-label" :class="{ 'is-current': i === step }">{{ s.label }}</span>
                  </li>
                </ol>
              </div>
            </aside>

            <div class="ppdb-main">
              <div class="ppdb-card ppdb-form-card ppdb-anim" :key="step">
                <div class="ppdb-form-head">
                  <div class="ppdb-form-heading">
                    <span v-if="steps[step].key === 'santri' || steps[step].key === 'sekolah'" class="ppdb-group-label">Data Santri</span>
                    <span v-else-if="steps[step].key === 'ayah' || steps[step].key === 'ibu' || steps[step].key === 'biaya' || steps[step].key === 'wali'" class="ppdb-group-label">Data Orang Tua</span>
                    <span v-else class="ppdb-group-label">Dokumen &amp; Pernyataan</span>
                    <h3 class="ppdb-form-title">{{ steps[step].label }}</h3>
                  </div>
                  <span class="ppdb-form-step-count">Langkah {{ step + 1 }} / {{ steps.length }}</span>
                </div>

                <div class="ppdb-progress">
                  <div class="ppdb-progress-bar" :style="{ width: ((step + 1) / steps.length * 100) + '%' }"></div>
                </div>

                <!-- Step 0: Identitas Santri -->
                <div v-if="steps[step].key === 'santri'" class="ppdb-form-grid">
                  <div class="ppdb-field ppdb-span-2">
                    <label class="ppdb-field-label">Jalur pendaftaran *</label>
                    <select v-model="form.jenjang" class="ppdb-input" @change="onJenjangChange">
                      <option v-for="j in jenjangOptions" :key="j.key" :value="j.key">{{ j.label }}</option>
                    </select>
                    <p v-if="form.jenjang === 'salafiyah'" class="ppdb-help">
                      <span class="material-symbols-outlined">info</span>Jalur Salafiyah khusus santri putri.
                    </p>
                  </div>
                  <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">Nama lengkap *</label><input v-model="form.name" class="ppdb-input" placeholder="Nama lengkap santri" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">NIK (16 digit) *</label><input v-model="form.nik" class="ppdb-input" inputmode="numeric" maxlength="16" placeholder="3271…" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">NISN</label><input v-model="form.nisn" class="ppdb-input" placeholder="NISN" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Jenis kelamin *</label><select v-model="form.gender" class="ppdb-input" :disabled="form.jenjang === 'salafiyah'"><option value="">Pilih</option><option value="L" :disabled="form.jenjang === 'salafiyah'">Laki-laki</option><option value="P">Perempuan</option></select></div>
                  <div class="ppdb-pair">
                    <div class="ppdb-field"><label class="ppdb-field-label">Tempat lahir *</label><input v-model="form.birth_place" class="ppdb-input" /></div>
                    <div class="ppdb-field"><label class="ppdb-field-label">Tanggal lahir *</label><input v-model="form.birth_date" type="date" class="ppdb-input" /></div>
                  </div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Kewarganegaraan *</label><select v-model="form.nationality" class="ppdb-input"><option value="">Pilih</option><option>Indonesia</option><option>Lainnya</option></select></div>
                  <div v-if="form.nationality === 'Lainnya'" class="ppdb-field"><label class="ppdb-field-label">Sebutkan *</label><input v-model="form.nationality_other" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Anak ke *</label><input v-model.number="form.child_order" type="number" min="1" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Jumlah saudara *</label><input v-model.number="form.sibling_count" type="number" min="0" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Status anak *</label><select v-model="form.orphan_status" class="ppdb-input"><option value="">Pilih</option><option>Yatim</option><option>Yatim Piatu</option><option>Piatu</option><option>Bukan Yatim</option></select></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Bahasa sehari-hari *</label><select v-model="form.daily_language" class="ppdb-input"><option value="">Pilih</option><option>Bahasa Aceh/Daerah</option><option>Bahasa Indonesia</option><option>Lainnya</option></select></div>
                  <div v-if="form.daily_language === 'Lainnya'" class="ppdb-field"><label class="ppdb-field-label">Sebutkan bahasa *</label><input v-model="form.daily_language_other" class="ppdb-input" /></div>
                  <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">Alamat lengkap *</label><textarea v-model="form.address" rows="2" class="ppdb-input"></textarea></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Desa *</label><input v-model="form.village" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Kecamatan *</label><input v-model="form.district" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Kabupaten/Kota *</label><input v-model="form.city" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Provinsi *</label><input v-model="form.province" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Kode pos</label><input v-model="form.postal_code" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">No. HP</label><input v-model="form.phone" class="ppdb-input" /></div>
                  <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">Email</label><input v-model="form.email" type="email" class="ppdb-input" /></div>
                  <div class="ppdb-field ppdb-span-2">
                    <label class="ppdb-field-label">Pas foto 3×4 latar biru (JPG/PNG/WebP, maks 2MB, opsional)</label>
                    <input type="file" accept=".jpg,.jpeg,.png,.webp" @change="onPhoto" class="ppdb-input" />
                    <img v-if="photoPreview" :src="photoPreview" alt="Pratinjau pas foto" class="ppdb-photo-preview" />
                  </div>
                </div>

                <!-- Step 1: Sekolah asal -->
                <div v-if="steps[step].key === 'sekolah'" class="ppdb-form-stack">
                  <div class="ppdb-field"><label class="ppdb-field-label">Nama SD/MI atau SMP/MTs *</label><input v-model="form.origin_school_name" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Alamat sekolah asal</label><textarea v-model="form.origin_school_address" rows="2" class="ppdb-input"></textarea></div>
                </div>

                <!-- Step 2: Ayah -->
                <div v-if="steps[step].key === 'ayah'" class="ppdb-form-grid">
                  <div class="ppdb-field"><label class="ppdb-field-label">Nomor Kartu Keluarga *</label><input v-model="form.father_family_card" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Nama ayah kandung *</label><input v-model="form.father_name" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">NIK ayah</label><input v-model="form.father_nik" class="ppdb-input" /></div>
                  <div class="ppdb-pair">
                    <div class="ppdb-field"><label class="ppdb-field-label">Tempat lahir</label><input v-model="form.father_birth_place" class="ppdb-input" /></div>
                    <div class="ppdb-field"><label class="ppdb-field-label">Tanggal lahir</label><input v-model="form.father_birth_date" type="date" class="ppdb-input" /></div>
                  </div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Kewarganegaraan</label><select v-model="form.father_nationality" class="ppdb-input"><option value="">Pilih</option><option>Indonesia</option><option>Lainnya</option></select></div>
                  <div v-if="form.father_nationality === 'Lainnya'" class="ppdb-field"><label class="ppdb-field-label">Sebutkan *</label><input v-model="form.father_nationality_other" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Pendidikan terakhir</label><input v-model="form.father_education" class="ppdb-input" placeholder="SD/SMP/SMA/D3/S1/…" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Pekerjaan</label><input v-model="form.father_occupation" class="ppdb-input" /></div>
                  <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">No. telepon/HP</label><input v-model="form.father_phone" class="ppdb-input" /></div>
                  <div class="ppdb-check-row ppdb-span-2"><input v-model="copyFather" type="checkbox" id="cpF" class="ppdb-check" /><label for="cpF" class="ppdb-check-label">Alamat sama dengan santri</label></div>
                  <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">Alamat lengkap</label><textarea v-model="form.father_address" rows="2" class="ppdb-input"></textarea></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Desa</label><input v-model="form.father_village" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Kecamatan</label><input v-model="form.father_district" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Kabupaten/Kota</label><input v-model="form.father_city" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Provinsi</label><input v-model="form.father_province" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Kode pos</label><input v-model="form.father_postal_code" class="ppdb-input" /></div>
                  <div class="ppdb-check-row ppdb-span-2"><input v-model="form.father_is_deceased" type="checkbox" id="fdD" class="ppdb-check" /><label for="fdD" class="ppdb-check-label">Sudah meninggal dunia</label></div>
                  <template v-if="form.father_is_deceased">
                    <div class="ppdb-field"><label class="ppdb-field-label">Tanggal meninggal *</label><input v-model="form.father_death_date" type="date" class="ppdb-input" /></div>
                    <div class="ppdb-field"><label class="ppdb-field-label">Tempat meninggal *</label><input v-model="form.father_death_place" class="ppdb-input" /></div>
                    <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">Sebab meninggal *</label><input v-model="form.father_death_cause" class="ppdb-input" /></div>
                  </template>
                </div>

                <!-- Step 3: Ibu -->
                <div v-if="steps[step].key === 'ibu'" class="ppdb-form-grid">
                  <div class="ppdb-field"><label class="ppdb-field-label">Nomor Kartu Keluarga *</label><input v-model="form.mother_family_card" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Nama ibu kandung *</label><input v-model="form.mother_name" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">NIK ibu</label><input v-model="form.mother_nik" class="ppdb-input" /></div>
                  <div class="ppdb-pair">
                    <div class="ppdb-field"><label class="ppdb-field-label">Tempat lahir</label><input v-model="form.mother_birth_place" class="ppdb-input" /></div>
                    <div class="ppdb-field"><label class="ppdb-field-label">Tanggal lahir</label><input v-model="form.mother_birth_date" type="date" class="ppdb-input" /></div>
                  </div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Kewarganegaraan</label><select v-model="form.mother_nationality" class="ppdb-input"><option value="">Pilih</option><option>Indonesia</option><option>Lainnya</option></select></div>
                  <div v-if="form.mother_nationality === 'Lainnya'" class="ppdb-field"><label class="ppdb-field-label">Sebutkan *</label><input v-model="form.mother_nationality_other" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Pendidikan terakhir</label><input v-model="form.mother_education" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Pekerjaan</label><input v-model="form.mother_occupation" class="ppdb-input" /></div>
                  <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">No. telepon/HP</label><input v-model="form.mother_phone" class="ppdb-input" /></div>
                  <div class="ppdb-check-row ppdb-span-2"><input v-model="copyMother" type="checkbox" id="cpM" class="ppdb-check" /><label for="cpM" class="ppdb-check-label">Alamat sama dengan santri</label></div>
                  <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">Alamat lengkap</label><textarea v-model="form.mother_address" rows="2" class="ppdb-input"></textarea></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Desa</label><input v-model="form.mother_village" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Kecamatan</label><input v-model="form.mother_district" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Kabupaten/Kota</label><input v-model="form.mother_city" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Provinsi</label><input v-model="form.mother_province" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Kode pos</label><input v-model="form.mother_postal_code" class="ppdb-input" /></div>
                  <div class="ppdb-check-row ppdb-span-2"><input v-model="form.mother_is_deceased" type="checkbox" id="mdD" class="ppdb-check" /><label for="mdD" class="ppdb-check-label">Sudah meninggal dunia</label></div>
                  <template v-if="form.mother_is_deceased">
                    <div class="ppdb-field"><label class="ppdb-field-label">Tanggal meninggal *</label><input v-model="form.mother_death_date" type="date" class="ppdb-input" /></div>
                    <div class="ppdb-field"><label class="ppdb-field-label">Tempat meninggal *</label><input v-model="form.mother_death_place" class="ppdb-input" /></div>
                    <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">Sebab meninggal *</label><input v-model="form.mother_death_cause" class="ppdb-input" /></div>
                  </template>
                </div>

                <!-- Step 4: Pembiaya + Darurat -->
                <div v-if="steps[step].key === 'biaya'" class="ppdb-form-grid">
                  <div class="ppdb-span-2 ppdb-inline-buttons">
                    <button @click="copyFinancier('ayah')" type="button" class="ppdb-btn ppdb-btn--soft ppdb-btn--sm">Salin dari Ayah</button>
                    <button @click="copyFinancier('ibu')" type="button" class="ppdb-btn ppdb-btn--soft ppdb-btn--sm">Salin dari Ibu</button>
                  </div>
                  <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">Nama pembiaya *</label><input v-model="form.financier_name" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Hubungan dengan santri *</label><input v-model="form.financier_relation" class="ppdb-input" placeholder="Ayah/Ibu/Wali/…" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">No. telepon/HP</label><input v-model="form.financier_phone" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Tempat lahir</label><input v-model="form.financier_birth_place" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Tanggal lahir</label><input v-model="form.financier_birth_date" type="date" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Kewarganegaraan</label><select v-model="form.financier_nationality" class="ppdb-input"><option value="">Pilih</option><option>Indonesia</option><option>Lainnya</option></select></div>
                  <div v-if="form.financier_nationality === 'Lainnya'" class="ppdb-field"><label class="ppdb-field-label">Sebutkan *</label><input v-model="form.financier_nationality_other" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Pendidikan</label><input v-model="form.financier_education" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Pekerjaan</label><input v-model="form.financier_occupation" class="ppdb-input" /></div>
                  <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">Alamat lengkap</label><textarea v-model="form.financier_address" rows="2" class="ppdb-input"></textarea></div>
                  <h4 class="ppdb-subhead ppdb-span-2">Keluarga Terdekat yang Mudah Dihubungi</h4>
                  <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">Nama *</label><input v-model="form.emergency_name" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Hubungan *</label><input v-model="form.emergency_relation" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">No. telepon/HP *</label><input v-model="form.emergency_phone" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Tempat lahir</label><input v-model="form.emergency_birth_place" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Tanggal lahir</label><input v-model="form.emergency_birth_date" type="date" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Pendidikan</label><input v-model="form.emergency_education" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Pekerjaan</label><input v-model="form.emergency_occupation" class="ppdb-input" /></div>
                  <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">Alamat lengkap</label><textarea v-model="form.emergency_address" rows="2" class="ppdb-input"></textarea></div>
                </div>

                <!-- Step 5: Wali + Mahram -->
                <div v-if="steps[step].key === 'wali'" class="ppdb-form-grid">
                  <div class="ppdb-check-row ppdb-span-2"><input v-model="form.guardian_not_required" type="checkbox" id="gNR" class="ppdb-check" /><label for="gNR" class="ppdb-check-label">Tidak ada wali / tidak diperlukan</label></div>
                  <template v-if="!form.guardian_not_required">
                    <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">Nama wali *</label><input v-model="form.guardian_name" class="ppdb-input" /></div>
                    <div class="ppdb-field"><label class="ppdb-field-label">Hubungan *</label><input v-model="form.guardian_relation" class="ppdb-input" /></div>
                    <div class="ppdb-field"><label class="ppdb-field-label">No. telepon/HP</label><input v-model="form.guardian_phone" class="ppdb-input" /></div>
                    <div class="ppdb-field"><label class="ppdb-field-label">Tempat lahir</label><input v-model="form.guardian_birth_place" class="ppdb-input" /></div>
                    <div class="ppdb-field"><label class="ppdb-field-label">Tanggal lahir</label><input v-model="form.guardian_birth_date" type="date" class="ppdb-input" /></div>
                    <div class="ppdb-field"><label class="ppdb-field-label">Pendidikan</label><input v-model="form.guardian_education" class="ppdb-input" /></div>
                    <div class="ppdb-field"><label class="ppdb-field-label">Pekerjaan</label><input v-model="form.guardian_occupation" class="ppdb-input" /></div>
                    <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">Alamat lengkap</label><textarea v-model="form.guardian_address" rows="2" class="ppdb-input"></textarea></div>
                  </template>
                  <template v-if="form.gender === 'P'">
                    <h4 class="ppdb-subhead ppdb-span-2">Mahram Santri Putri</h4>
                    <div class="ppdb-field"><label class="ppdb-field-label">Nama mahram</label><input v-model="form.mahram_name" class="ppdb-input" /></div>
                    <div class="ppdb-field"><label class="ppdb-field-label">Hubungan</label><input v-model="form.mahram_relation" class="ppdb-input" /></div>
                    <div class="ppdb-field"><label class="ppdb-field-label">No. telepon/HP</label><input v-model="form.mahram_phone" class="ppdb-input" /></div>
                    <div class="ppdb-field"><label class="ppdb-field-label">Tempat lahir</label><input v-model="form.mahram_birth_place" class="ppdb-input" /></div>
                    <div class="ppdb-field"><label class="ppdb-field-label">Tanggal lahir</label><input v-model="form.mahram_birth_date" type="date" class="ppdb-input" /></div>
                    <div class="ppdb-field ppdb-span-2"><label class="ppdb-field-label">Alamat lengkap</label><textarea v-model="form.mahram_address" rows="2" class="ppdb-input"></textarea></div>
                  </template>
                </div>

                <!-- Step 6: Kesehatan + Pernyataan -->
                <div v-if="steps[step].key === 'sehat'" class="ppdb-form-stack">
                  <div class="ppdb-field"><label class="ppdb-field-label">Golongan darah *</label><select v-model="form.blood_type" class="ppdb-input"><option value="">Pilih</option><option>A</option><option>B</option><option>AB</option><option>O</option><option>Tidak Diketahui</option></select></div>
                  <div class="ppdb-field">
                    <label class="ppdb-field-label">Riwayat penyakit (pilih yang sesuai)</label>
                    <div class="ppdb-chips">
                      <label v-for="d in diseases" :key="d" class="ppdb-chip" :class="form.medical_history.includes(d) ? 'ppdb-chip--on' : 'ppdb-chip--off'">
                        <input v-model="form.medical_history" :value="d" type="checkbox" class="sr-only" />{{ d }}
                      </label>
                    </div>
                  </div>
                  <div v-if="form.medical_history.includes('Lainnya')" class="ppdb-field"><label class="ppdb-field-label">Sebutkan penyakit lainnya *</label><input v-model="form.medical_history_other" class="ppdb-input" /></div>
                  <div class="ppdb-field"><label class="ppdb-field-label">Keterangan kesehatan</label><textarea v-model="form.medical_history_note" rows="2" class="ppdb-input" placeholder="Keterangan tambahan bila ada"></textarea></div>
                  <label class="ppdb-consent">
                    <input v-model="form.consent" type="checkbox" class="ppdb-check" />
                    <span>Saya menyatakan data yang dimasukkan benar dan menyetujui pemrosesan data untuk kebutuhan penerimaan santri. Tanda tangan resmi tetap mengikuti dokumen cetak panitia bila diwajibkan. *</span>
                  </label>
                  <div class="ppdb-field"><label class="ppdb-field-label">Nama orang tua/wali sebagai konfirmasi *</label><input v-model="form.declarant_name" class="ppdb-input" placeholder="Nama terang" /></div>
                </div>

                <!-- Step 7: Ringkasan -->
                <div v-if="steps[step].key === 'ringkas'" class="ppdb-form-stack">
                  <div class="ppdb-summary">
                    <p><strong>Jalur:</strong> {{ jenjangLabel(form.jenjang) }}</p>
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
                  <p class="ppdb-summary-note">Periksa kembali data sebelum mengirim. Nomor pendaftaran dibuat otomatis oleh sistem setelah data tersimpan.</p>
                </div>

                <p v-if="formError" class="ppdb-error">{{ formError }}</p>
                <div v-if="backendErrors.length" class="ppdb-error-list">
                  <p v-for="(e, i) in backendErrors" :key="i">{{ e }}</p>
                </div>

                <div class="ppdb-form-actions">
                  <button v-if="step > 0" @click="prev" type="button" class="ppdb-btn ppdb-btn--ghost">Sebelumnya</button>
                  <button v-if="step < steps.length - 1" @click="next" type="button" class="ppdb-btn ppdb-btn--primary">Selanjutnya</button>
                  <button v-else @click="submit" :disabled="submitting" type="button" class="ppdb-btn ppdb-btn--primary">
                    <span v-if="submitting" class="material-symbols-outlined ppdb-spin">progress_activity</span>
                    {{ submitting ? 'Mengirim…' : 'Kirim Pendaftaran' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- SUKSES -->
      <section v-if="success" class="ppdb-section">
        <div class="ppdb-container">
          <div class="ppdb-card ppdb-success ppdb-anim">
            <span class="ppdb-success-icon"><span class="material-symbols-outlined">check_circle</span></span>
            <h2 class="ppdb-success-title">Pendaftaran Berhasil Dikirim</h2>
            <p class="ppdb-success-text">Status: <strong>Menunggu verifikasi panitia</strong>. Keputusan diterima/ditolak diumumkan kemudian.</p>
            <div class="ppdb-success-grid">
              <p><strong>Nomor pendaftaran:</strong><br />{{ success.registration_number }}</p>
              <p><strong>Nama santri:</strong><br />{{ success.name }}</p>
              <p><strong>Jalur:</strong><br />{{ jenjangLabel(success.jenjang) }}</p>
              <p><strong>Gelombang:</strong><br />{{ success.wave?.name }}</p>
              <p><strong>Tanggal:</strong><br />{{ fmtDateTime(success.registered_at) }}</p>
            </div>
            <p class="ppdb-success-text">Simpan nomor pendaftaran. Pantau pengumuman resmi sekolah untuk jadwal, berkas, dan tahapan seleksi.</p>
            <div class="ppdb-success-actions">
              <button @click="printBukti" class="ppdb-btn ppdb-btn--primary">Cetak Bukti</button>
              <router-link :to="{ path: '/ppdb/upload-foto', query: { registration_number: success.registration_number } }" class="ppdb-btn ppdb-btn--soft">Upload Pas Foto</router-link>
              <router-link :to="{ path: '/ppdb/cek-status', query: { nomor: success.registration_number } }" class="ppdb-btn ppdb-btn--ghost">Cek Status</router-link>
              <router-link to="/ppdb/pengumuman" class="ppdb-btn ppdb-btn--ghost">Pengumuman</router-link>
              <router-link to="/ppdb/daftar" @click="resetAll" class="ppdb-btn ppdb-btn--ghost">Daftar Lain</router-link>
            </div>
          </div>
        </div>
      </section>
    </div>
  </LpiLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import LpiLayout from '../Components/LpiLayout.vue'

const route = useRoute()
const waves = ref([])
const loaded = ref(false)
const selected = ref(null)
const publicStats = ref(null)
const DEFAULT_SCHEDULE_DAYS = 'Setiap hari Sabtu s/d Kamis'
const DEFAULT_SCHEDULE_HOURS = 'Pukul 09.00-12.00 WIB dan 14.00-17.00 WIB'
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

const jenjangOptions = [
  { key: 'lanjut', label: 'Jenjang Lanjut', icon: 'trending_up', badge: '', description: 'Pendaftaran santri yang melanjutkan ke jenjang berikutnya di lingkungan LPI Dayah Madani Al-Aziziyah.', note: '' },
  { key: 'sma', label: 'Pendaftaran SMA', icon: 'account_balance', badge: '', description: 'Pendaftaran santri baru untuk jenjang SMA Dayah Madani Al-Aziziyah.', note: '' },
  { key: 'smp', label: 'Pendaftaran SMP', icon: 'school', badge: '', description: 'Pendaftaran santri baru untuk jenjang SMP Dayah Madani Al-Aziziyah.', note: '' },
  { key: 'salafiyah', label: 'Salafiyah Santri Putri', icon: 'female', badge: 'Khusus Putri', description: 'Pendaftaran khusus santri putri pada program Salafiyah.', note: 'Jenis kelamin otomatis Perempuan' },
]
const jenjangMap = Object.fromEntries(jenjangOptions.map((j) => [j.key, j.label]))
function jenjangLabel(key) { return jenjangMap[key] || '—' }

function onJenjangChange() {
  if (form.value.jenjang === 'salafiyah') {
    form.value.gender = 'P'
  }
  formError.value = ''
}

function selectJenjang(key) {
  form.value.jenjang = key
  onJenjangChange()
}

const scheduleDays = ref(DEFAULT_SCHEDULE_DAYS)
const scheduleHours = ref(DEFAULT_SCHEDULE_HOURS)

function syncSchedule() {
  const src = selected.value || waves.value[0] || null
  scheduleDays.value = src?.schedule_days || DEFAULT_SCHEDULE_DAYS
  scheduleHours.value = src?.schedule_hours || DEFAULT_SCHEDULE_HOURS
}

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
  wave_id: null, jenjang: '', name: '', nik: '', gender: '', birth_place: '', birth_date: '',
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
  const q = route.query
  if (typeof q.jenjang === 'string' && jenjangMap[q.jenjang]) {
    form.value.jenjang = q.jenjang
    if (q.jenjang === 'salafiyah') form.value.gender = 'P'
  }
  try {
    const wr = await fetch('/api/ppdb-waves/public')
    if (wr.ok) {
      waves.value = await wr.json()
      const waveParam = typeof q.wave === 'string' ? q.wave : Array.isArray(q.wave) ? q.wave[0] : ''
      if (waveParam) {
        const found = waves.value.find((w) => String(w.id) === String(waveParam))
        if (found) selectWave(found)
      }
    }
    syncSchedule()
  } catch { /* abaikan */ } finally { loaded.value = true }
})

watch(() => form.value.jenjang, (v) => {
  if (v === 'salafiyah') {
    form.value.gender = 'P'
  }
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
  syncSchedule()
  step.value = 0
  success.value = null
  formError.value = ''
  backendErrors.value = []
}

function changeWave() {
  selected.value = null
  form.value.wave_id = null
  formError.value = ''
  backendErrors.value = []
  syncSchedule()
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
    if (!f.jenjang) errs.push('Jalur pendaftaran wajib dipilih.')
    if (f.jenjang === 'salafiyah' && f.gender !== 'P') errs.push('Jalur Salafiyah hanya untuk santri putri.')
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
  syncSchedule()
}

function printBukti() { window.print() }
</script>

<style scoped src="../../css/ppdb.css"></style>
