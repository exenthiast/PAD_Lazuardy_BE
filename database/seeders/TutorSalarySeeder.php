<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TutorSalary;
use App\Models\User;
use Carbon\Carbon;

class TutorSalarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first tutor user for testing
        $tutor = User::where('role', 'tutor')->first();
        
        if (!$tutor) {
            $this->command->info('No tutor found. Please create a tutor first.');
            return;
        }

        // Get admin user (or use system admin)
        $admin = User::where('role', 'admin')->first();
        
        // Create 3 salary records for testing
        $salaries = [
            [
                'tutor_user_id' => $tutor->id,
                'admin_user_id' => $admin?->id,
                'amount' => 1500000,
                'invoice_file_url' => 'https://drive.google.com/drive/folders/sample-invoice-dec-2025',
                'paid_at' => Carbon::now()->subMonths(0),
                'notes' => 'Pembayaran gaji bulan Desember 2025',
            ],
            [
                'tutor_user_id' => $tutor->id,
                'admin_user_id' => $admin?->id,
                'amount' => 1200000,
                'invoice_file_url' => 'https://drive.google.com/drive/folders/sample-invoice-nov-2025',
                'paid_at' => Carbon::now()->subMonths(1),
                'notes' => 'Pembayaran gaji bulan November 2025',
            ],
            [
                'tutor_user_id' => $tutor->id,
                'admin_user_id' => $admin?->id,
                'amount' => 1000000,
                'invoice_file_url' => 'https://drive.google.com/drive/folders/sample-invoice-oct-2025',
                'paid_at' => Carbon::now()->subMonths(2),
                'notes' => 'Pembayaran gaji bulan Oktober 2025',
            ],
        ];

        foreach ($salaries as $salary) {
            TutorSalary::create($salary);
        }

        $this->command->info('Tutor salary records created successfully for tutor: ' . $tutor->name);
    }
}
