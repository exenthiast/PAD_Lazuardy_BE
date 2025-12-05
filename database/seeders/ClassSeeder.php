<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            'Kelas 7',
            'Kelas 8', 
            'Kelas 9',
            'Kelas 10',
            'Kelas 11',
            'Kelas 12',
        ];

        foreach ($classes as $className) {
            ClassModel::firstOrCreate(['name' => $className]);
        }
    }
}
