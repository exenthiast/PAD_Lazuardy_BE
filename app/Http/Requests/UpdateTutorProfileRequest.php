<?php

namespace App\Http\Requests;

use App\Enums\CourseModeEnum;
use App\Enums\DayEnum;
use App\Enums\GenderEnum;
use App\Enums\ReligionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTutorProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'], // max 2MB
            'name' => ['sometimes', 'string', 'min:2', 'max:255'],
            'gender' => ['sometimes', new Enum(GenderEnum::class)],
            'date_of_birth' => [
                'sometimes', 
                'date', 
                'date_format:Y-m-d', 
                'before_or_equal:today'
            ],
            'religion' => ['sometimes', new Enum(ReligionEnum::class)],
            'telephone_number' => ['sometimes','string','max:15'],
            
            'province' => ['sometimes', 'string', 'min:2', 'max:255'],
            'regency' => ['sometimes', 'string', 'min:2', 'max:255'],
            'district' => ['sometimes', 'string', 'min:2', 'max:255'],
            'subdistrict' => ['sometimes', 'string', 'min:2', 'max:255'],
            'street' => ['sometimes', 'string', 'min:2', 'max:255'],
            
            // Address sebagai string lengkap
            'address' => ['sometimes', 'string'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],

            'bank' => ['nullable', 'string'],
            'rekening' => ['nullable', 'string'],
            
            // Bank & price fields baru
            'price' => ['sometimes', 'integer', 'min:0'],
            'bank_name' => ['sometimes', 'string', 'max:100'],
            'bank_account_number' => ['sometimes', 'string', 'max:50'],
            'bank_account_name' => ['sometimes', 'string', 'max:255'],
            
            // Fields dari social auth profile update
            'description' => ['sometimes', 'string'],
            'keahlian' => ['sometimes', 'string'],
            'marketSiswa' => ['sometimes', 'string', 'max:100'],
            'pengalaman' => ['sometimes', 'string', 'max:255'],
            'skillBahasa' => ['sometimes', 'string', 'max:255'],
            'organisasi' => ['sometimes', 'string', 'max:255'],
            'pendidikan' => ['sometimes', 'json'],
            'skills' => ['sometimes', 'array'],
            'schedule' => ['sometimes', 'array'],
            'education' => ['sometimes', 'array'],
        ];
    }
}
