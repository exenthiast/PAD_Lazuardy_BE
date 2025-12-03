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

            'bank' => ['nullable', 'string'],
            'rekening' => ['nullable', 'string'],
            
            // Fields dari social auth profile update
            'description' => ['sometimes', 'string'],
            'keahlian' => ['sometimes', 'string'],
            'skills' => ['sometimes', 'array'],
            'schedule' => ['sometimes', 'array'],
            'education' => ['sometimes', 'array'],
        ];
    }
}
