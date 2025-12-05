<?php

namespace App\Http\Requests;

use App\Enums\GenderEnum;
use App\Enums\ReligionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateStudentProfileRequest extends FormRequest
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
            'telephone_number' => ['sometimes','string','max:15'],
            'profile_photo_url' => ['sometimes', 'string'],
            'photo' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'], // Upload foto
            'gender' => ['sometimes', new Enum(GenderEnum::class)],
            'date_of_birth' => [
                'sometimes', 
                'date', 
                'date_format:Y-m-d', 
                'before_or_equal:today'
            ],
            'religion' => ['sometimes', new Enum(ReligionEnum::class)],
            
            'province' => ['sometimes', 'string', 'min:2', 'max:255'],
            'regency' => ['sometimes', 'string', 'min:2', 'max:255'],
            'district' => ['sometimes', 'string', 'min:2', 'max:255'],
            'subdistrict' => ['sometimes', 'string', 'min:2', 'max:255'],
            'street' => ['sometimes', 'string', 'min:2', 'max:255'],
            
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            
            'school' =>  ['sometimes', 'string', 'min:2', 'max:255'],
            'class_id' =>  ['nullable', 'integer', 'exists:classes,id'],
            'curriculum_id' =>  ['nullable', 'integer', 'exists:curriculums,id'],
            'parent' => ['nullable', 'string', 'min:2', 'max:255'],
            'parent_telephone_number' => ['nullable', 'string', 'max:15',]
        ];
    }
}
