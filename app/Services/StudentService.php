<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;

class StudentService
{
    /**
     * Create a new class instance.
     */
    public function showStudentProfile(?Student $query)
    {
        // Jika student belum dibuat, return default values
        if (!$query) {
            return [
                'school' => null,
                'class' => null,
                'curriculum' => null,
                'parent' => null,
                'parent_telephone_number' => null
            ];
        }

        $data = [
            'school' => $query->school,
            'class' => $query->class?->name ?? null,
            'curriculum' => $query->curriculum?->name ?? null,
            'parent' => $query->parent,
            'parent_telephone_number' => $query->parent_telephone_number
        ];

        return $data;
    }
}
