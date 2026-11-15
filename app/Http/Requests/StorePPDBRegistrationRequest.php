<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePPDBRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'father_is_deceased' => $this->boolean('father_is_deceased'),
            'mother_is_deceased' => $this->boolean('mother_is_deceased'),
            'guardian_not_required' => $this->boolean('guardian_not_required'),
        ]);
    }

    public function rules(): array
    {
        return [
            'wave_id' => 'required|exists:p_p_d_b_waves,id',
            'jenjang' => ['required', Rule::in(['lanjut', 'sma', 'smp', 'salafiyah'])],
            'name' => 'required|string|max:255',
            'nik' => 'required|string|size:16|regex:/^[0-9]+$/',
            'gender' => ['required', Rule::in($this->input('jenjang') === 'salafiyah' ? ['P'] : ['L', 'P'])],
            'birth_place' => 'required|string|max:100',
            'birth_date' => 'required|date|before:today',
            'nationality' => 'required|in:Indonesia,Lainnya',
            'nationality_other' => 'required_if:nationality,Lainnya|nullable|string|max:100',
            'child_order' => 'required|integer|min:1|max:20',
            'sibling_count' => 'required|integer|min:0|max:20',
            'orphan_status' => 'required|in:Yatim,Yatim Piatu,Piatu,Bukan Yatim',
            'daily_language' => 'required|in:Bahasa Aceh/Daerah,Bahasa Indonesia,Lainnya',
            'daily_language_other' => 'required_if:daily_language,Lainnya|nullable|string|max:100',
            'nisn' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:2000',
            'village' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'photo' => 'nullable|file|max:2048|mimes:jpg,jpeg,png,webp',
            'origin_school_name' => 'required|string|max:150',
            'origin_school_address' => 'nullable|string|max:2000',
            'father_family_card' => 'required|string|max:20',
            'father_name' => 'required|string|max:255',
            'father_nik' => 'nullable|string|max:20',
            'father_birth_place' => 'nullable|string|max:100',
            'father_birth_date' => 'nullable|date|before:today',
            'father_nationality' => 'nullable|in:Indonesia,Lainnya',
            'father_nationality_other' => 'required_if:father_nationality,Lainnya|nullable|string|max:100',
            'father_education' => 'nullable|string|max:100',
            'father_occupation' => 'nullable|string|max:100',
            'father_phone' => 'nullable|string|max:20',
            'father_address' => 'nullable|string|max:2000',
            'father_village' => 'nullable|string|max:100',
            'father_district' => 'nullable|string|max:100',
            'father_city' => 'nullable|string|max:100',
            'father_province' => 'nullable|string|max:100',
            'father_postal_code' => 'nullable|string|max:10',
            'father_is_deceased' => 'nullable|boolean',
            'father_death_date' => 'required_if:father_is_deceased,true,1|nullable|date|before_or_equal:today',
            'father_death_place' => 'required_if:father_is_deceased,true,1|nullable|string|max:100',
            'father_death_cause' => 'required_if:father_is_deceased,true,1|nullable|string|max:150',
            'mother_family_card' => 'required|string|max:20',
            'mother_name' => 'required|string|max:255',
            'mother_nik' => 'nullable|string|max:20',
            'mother_birth_place' => 'nullable|string|max:100',
            'mother_birth_date' => 'nullable|date|before:today',
            'mother_nationality' => 'nullable|in:Indonesia,Lainnya',
            'mother_nationality_other' => 'required_if:mother_nationality,Lainnya|nullable|string|max:100',
            'mother_education' => 'nullable|string|max:100',
            'mother_occupation' => 'nullable|string|max:100',
            'mother_phone' => 'nullable|string|max:20',
            'mother_address' => 'nullable|string|max:2000',
            'mother_village' => 'nullable|string|max:100',
            'mother_district' => 'nullable|string|max:100',
            'mother_city' => 'nullable|string|max:100',
            'mother_province' => 'nullable|string|max:100',
            'mother_postal_code' => 'nullable|string|max:10',
            'mother_is_deceased' => 'nullable|boolean',
            'mother_death_date' => 'required_if:mother_is_deceased,true,1|nullable|date|before_or_equal:today',
            'mother_death_place' => 'required_if:mother_is_deceased,true,1|nullable|string|max:100',
            'mother_death_cause' => 'required_if:mother_is_deceased,true,1|nullable|string|max:150',
            'financier_source' => 'nullable|in:ayah,ibu,lainnya',
            'financier_name' => 'required|string|max:255',
            'financier_birth_place' => 'nullable|string|max:100',
            'financier_birth_date' => 'nullable|date|before:today',
            'financier_nationality' => 'nullable|in:Indonesia,Lainnya',
            'financier_nationality_other' => 'required_if:financier_nationality,Lainnya|nullable|string|max:100',
            'financier_education' => 'nullable|string|max:100',
            'financier_occupation' => 'nullable|string|max:100',
            'financier_phone' => 'nullable|string|max:20',
            'financier_address' => 'nullable|string|max:2000',
            'financier_village' => 'nullable|string|max:100',
            'financier_district' => 'nullable|string|max:100',
            'financier_city' => 'nullable|string|max:100',
            'financier_province' => 'nullable|string|max:100',
            'financier_postal_code' => 'nullable|string|max:10',
            'financier_relation' => 'required|string|max:100',
            'emergency_name' => 'required|string|max:255',
            'emergency_birth_place' => 'nullable|string|max:100',
            'emergency_birth_date' => 'nullable|date|before:today',
            'emergency_nationality' => 'nullable|in:Indonesia,Lainnya',
            'emergency_nationality_other' => 'required_if:emergency_nationality,Lainnya|nullable|string|max:100',
            'emergency_education' => 'nullable|string|max:100',
            'emergency_occupation' => 'nullable|string|max:100',
            'emergency_phone' => 'required|string|max:20',
            'emergency_address' => 'nullable|string|max:2000',
            'emergency_relation' => 'required|string|max:100',
            'guardian_not_required' => 'nullable|boolean',
            'guardian_name' => [Rule::requiredIf(! $this->boolean('guardian_not_required')), 'nullable', 'string', 'max:255'],
            'guardian_birth_place' => 'nullable|string|max:100',
            'guardian_birth_date' => 'nullable|date|before:today',
            'guardian_nationality' => 'nullable|in:Indonesia,Lainnya',
            'guardian_nationality_other' => 'required_if:guardian_nationality,Lainnya|nullable|string|max:100',
            'guardian_education' => 'nullable|string|max:100',
            'guardian_occupation' => 'nullable|string|max:100',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_address' => 'nullable|string|max:2000',
            'guardian_relation' => [Rule::requiredIf(! $this->boolean('guardian_not_required')), 'nullable', 'string', 'max:100'],
            'mahram_name' => 'nullable|string|max:255',
            'mahram_birth_place' => 'nullable|string|max:100',
            'mahram_birth_date' => 'nullable|date|before:today',
            'mahram_nationality' => 'nullable|in:Indonesia,Lainnya',
            'mahram_nationality_other' => 'required_if:mahram_nationality,Lainnya|nullable|string|max:100',
            'mahram_education' => 'nullable|string|max:100',
            'mahram_occupation' => 'nullable|string|max:100',
            'mahram_phone' => 'nullable|string|max:20',
            'mahram_address' => 'nullable|string|max:2000',
            'mahram_relation' => 'nullable|string|max:100',
            'blood_type' => 'required|in:A,B,AB,O,Tidak Diketahui',
            'medical_history' => 'nullable|array',
            'medical_history.*' => 'in:TBC,Hepatitis,Asthma/Sesak,Epilepsi/Ayan,Kanker,Tipes,Tidak Ada,Lainnya',
            'medical_history_other' => 'required_if:medical_history,Lainnya|nullable|string|max:255',
            'medical_history_note' => 'nullable|string|max:2000',
            'declarant_name' => 'required|string|max:255',
            'consent' => 'required|accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'nik.size' => 'NIK harus 16 digit.',
            'consent.accepted' => 'Pernyataan persetujuan wajib disetujui.',
        ];
    }
}
