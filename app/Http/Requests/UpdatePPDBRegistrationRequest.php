<?php

namespace App\Http\Requests;

use App\Models\PPDBRegistration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePPDBRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $bools = ['father_is_deceased', 'mother_is_deceased', 'guardian_not_required'];
        $merge = [];
        foreach ($bools as $key) {
            if ($this->has($key)) {
                $val = filter_var($this->input($key), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $merge[$key] = $val ?? $this->boolean($key);
            }
        }
        if ($merge) {
            $this->merge($merge);
        }
        if ($this->input('jenjang') === 'salafiyah') {
            $this->merge(['gender' => 'P']);
        }
    }

    public function rules(): array
    {
        $keys = array_keys($this->all());
        $isStatusOnly = count(array_diff($keys, ['status', 'notes'])) === 0;

        if ($isStatusOnly) {
            return [
                'status' => ['required', Rule::in(['pending', 'diterima', 'ditolak', 'cadangan'])],
                'notes' => ['nullable', 'string', 'max:2000'],
            ];
        }

        $jenjang = $this->input('jenjang');

        return [
            'wave_id' => ['required', 'integer', 'exists:p_p_d_b_waves,id'],
            'jenjang' => ['required', Rule::in(array_keys(PPDBRegistration::JENJANG_LABELS))],
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'size:16', 'regex:/^[0-9]{16}$/'],
            'gender' => ['required', Rule::in($jenjang === 'salafiyah' ? ['P'] : ['L', 'P'])],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date'],
            'nationality' => ['sometimes', 'required', Rule::in(['Indonesia', 'Lainnya'])],
            'nationality_other' => ['nullable', 'required_if:nationality,Lainnya', 'string', 'max:100'],
            'child_order' => ['sometimes', 'required', 'integer', 'min:1', 'max:20'],
            'sibling_count' => ['sometimes', 'required', 'integer', 'min:0', 'max:20'],
            'orphan_status' => ['sometimes', 'required', Rule::in(['Yatim', 'Yatim Piatu', 'Piatu', 'Bukan Yatim'])],
            'daily_language' => ['sometimes', 'required', Rule::in(['Bahasa Aceh/Daerah', 'Bahasa Indonesia', 'Lainnya'])],
            'daily_language_other' => ['nullable', 'required_if:daily_language,Lainnya', 'string', 'max:100'],
            'nisn' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:2000'],
            'village' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'origin_school_name' => ['required', 'string', 'max:150'],
            'origin_school_address' => ['nullable', 'string', 'max:2000'],
            'father_family_card' => ['required', 'string', 'max:20'],
            'father_name' => ['required', 'string', 'max:255'],
            'father_nik' => ['nullable', 'string', 'max:20'],
            'father_birth_place' => ['nullable', 'string', 'max:100'],
            'father_birth_date' => ['nullable', 'date'],
            'father_nationality' => ['nullable', Rule::in(['Indonesia', 'Lainnya'])],
            'father_nationality_other' => ['nullable', 'string', 'max:100'],
            'father_education' => ['nullable', 'string', 'max:100'],
            'father_occupation' => ['nullable', 'string', 'max:100'],
            'father_phone' => ['nullable', 'string', 'max:20'],
            'father_address' => ['nullable', 'string', 'max:2000'],
            'father_village' => ['nullable', 'string', 'max:100'],
            'father_district' => ['nullable', 'string', 'max:100'],
            'father_city' => ['nullable', 'string', 'max:100'],
            'father_province' => ['nullable', 'string', 'max:100'],
            'father_postal_code' => ['nullable', 'string', 'max:10'],
            'father_is_deceased' => ['nullable', 'boolean'],
            'father_death_date' => ['nullable', 'date'],
            'father_death_place' => ['nullable', 'string', 'max:100'],
            'father_death_cause' => ['nullable', 'string', 'max:150'],
            'mother_family_card' => ['required', 'string', 'max:20'],
            'mother_name' => ['required', 'string', 'max:255'],
            'mother_nik' => ['nullable', 'string', 'max:20'],
            'mother_birth_place' => ['nullable', 'string', 'max:100'],
            'mother_birth_date' => ['nullable', 'date'],
            'mother_nationality' => ['nullable', Rule::in(['Indonesia', 'Lainnya'])],
            'mother_nationality_other' => ['nullable', 'string', 'max:100'],
            'mother_education' => ['nullable', 'string', 'max:100'],
            'mother_occupation' => ['nullable', 'string', 'max:100'],
            'mother_phone' => ['nullable', 'string', 'max:20'],
            'mother_address' => ['nullable', 'string', 'max:2000'],
            'mother_village' => ['nullable', 'string', 'max:100'],
            'mother_district' => ['nullable', 'string', 'max:100'],
            'mother_city' => ['nullable', 'string', 'max:100'],
            'mother_province' => ['nullable', 'string', 'max:100'],
            'mother_postal_code' => ['nullable', 'string', 'max:10'],
            'mother_is_deceased' => ['nullable', 'boolean'],
            'mother_death_date' => ['nullable', 'date'],
            'mother_death_place' => ['nullable', 'string', 'max:100'],
            'mother_death_cause' => ['nullable', 'string', 'max:150'],
            'financier_source' => ['nullable', Rule::in(['ayah', 'ibu', 'lainnya'])],
            'financier_name' => ['required', 'string', 'max:255'],
            'financier_birth_place' => ['nullable', 'string', 'max:100'],
            'financier_birth_date' => ['nullable', 'date'],
            'financier_nationality' => ['nullable', Rule::in(['Indonesia', 'Lainnya'])],
            'financier_nationality_other' => ['nullable', 'string', 'max:100'],
            'financier_education' => ['nullable', 'string', 'max:100'],
            'financier_occupation' => ['nullable', 'string', 'max:100'],
            'financier_phone' => ['nullable', 'string', 'max:20'],
            'financier_address' => ['nullable', 'string', 'max:2000'],
            'financier_village' => ['nullable', 'string', 'max:100'],
            'financier_district' => ['nullable', 'string', 'max:100'],
            'financier_city' => ['nullable', 'string', 'max:100'],
            'financier_province' => ['nullable', 'string', 'max:100'],
            'financier_postal_code' => ['nullable', 'string', 'max:10'],
            'financier_relation' => ['required', 'string', 'max:100'],
            'emergency_name' => ['required', 'string', 'max:255'],
            'emergency_birth_place' => ['nullable', 'string', 'max:100'],
            'emergency_birth_date' => ['nullable', 'date'],
            'emergency_nationality' => ['nullable', Rule::in(['Indonesia', 'Lainnya'])],
            'emergency_nationality_other' => ['nullable', 'string', 'max:100'],
            'emergency_education' => ['nullable', 'string', 'max:100'],
            'emergency_occupation' => ['nullable', 'string', 'max:100'],
            'emergency_phone' => ['required', 'string', 'max:20'],
            'emergency_address' => ['nullable', 'string', 'max:2000'],
            'emergency_relation' => ['required', 'string', 'max:100'],
            'guardian_not_required' => ['nullable', 'boolean'],
            'guardian_name' => ['nullable', 'required_if:guardian_not_required,false,0', 'string', 'max:255'],
            'guardian_birth_place' => ['nullable', 'string', 'max:100'],
            'guardian_birth_date' => ['nullable', 'date'],
            'guardian_nationality' => ['nullable', Rule::in(['Indonesia', 'Lainnya'])],
            'guardian_nationality_other' => ['nullable', 'string', 'max:100'],
            'guardian_education' => ['nullable', 'string', 'max:100'],
            'guardian_occupation' => ['nullable', 'string', 'max:100'],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
            'guardian_address' => ['nullable', 'string', 'max:2000'],
            'guardian_relation' => ['nullable', 'required_if:guardian_not_required,false,0', 'string', 'max:100'],
            'mahram_name' => ['nullable', 'string', 'max:255'],
            'mahram_birth_place' => ['nullable', 'string', 'max:100'],
            'mahram_birth_date' => ['nullable', 'date'],
            'mahram_nationality' => ['nullable', Rule::in(['Indonesia', 'Lainnya'])],
            'mahram_nationality_other' => ['nullable', 'string', 'max:100'],
            'mahram_education' => ['nullable', 'string', 'max:100'],
            'mahram_occupation' => ['nullable', 'string', 'max:100'],
            'mahram_phone' => ['nullable', 'string', 'max:20'],
            'mahram_address' => ['nullable', 'string', 'max:2000'],
            'mahram_relation' => ['nullable', 'string', 'max:100'],
            'blood_type' => ['required', Rule::in(['A', 'B', 'AB', 'O', 'Tidak Diketahui'])],
            'medical_history' => ['nullable', 'array'],
            'medical_history.*' => [Rule::in(['TBC', 'Hepatitis', 'Asthma/Sesak', 'Epilepsi/Ayan', 'Kanker', 'Tipes', 'Tidak Ada', 'Lainnya'])],
            'medical_history_other' => ['nullable', 'string', 'max:255'],
            'medical_history_note' => ['nullable', 'string', 'max:2000'],
            'declarant_name' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'diterima', 'ditolak', 'cadangan'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nik.size' => 'NIK harus 16 digit.',
            'nik.regex' => 'NIK harus terdiri dari 16 digit angka.',
            'nisn.regex' => 'NISN harus terdiri dari 10 digit angka.',
            'birth_date.date' => 'Tanggal lahir tidak valid.',
            'required' => ':attribute wajib diisi.',
            'required_if' => ':attribute wajib diisi.',
            'in' => ':attribute tidak valid.',
            'exists' => ':attribute tidak ditemukan.',
            'email' => 'Format email tidak valid.',
            'date' => ':attribute tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'wave_id' => 'Gelombang', 'jenjang' => 'Jalur', 'name' => 'Nama lengkap', 'nik' => 'NIK',
            'gender' => 'Jenis kelamin', 'birth_place' => 'Tempat lahir', 'birth_date' => 'Tanggal lahir',
            'nisn' => 'NISN', 'address' => 'Alamat', 'village' => 'Desa/Kelurahan', 'district' => 'Kecamatan',
            'city' => 'Kabupaten/Kota', 'province' => 'Provinsi', 'origin_school_name' => 'Sekolah asal',
            'father_family_card' => 'Nomor KK ayah', 'father_name' => 'Nama ayah',
            'mother_family_card' => 'Nomor KK ibu', 'mother_name' => 'Nama ibu',
            'financier_name' => 'Nama pembiaya', 'financier_relation' => 'Hubungan pembiaya',
            'emergency_name' => 'Nama kontak darurat', 'emergency_phone' => 'No. HP darurat',
            'emergency_relation' => 'Hubungan kontak darurat', 'guardian_name' => 'Nama wali',
            'guardian_relation' => 'Hubungan wali', 'blood_type' => 'Golongan darah',
            'declarant_name' => 'Nama pernyataan', 'status' => 'Status',
        ];
    }
}
