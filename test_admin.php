<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "=== Testing Admin User ===\n\n";

$admin = User::where('role', 'admin')->first();

if ($admin) {
    echo "Admin found!\n";
    echo "ID: {$admin->id}\n";
    echo "Name: {$admin->name}\n";
    echo "Email: {$admin->email}\n";
    echo "Role: {$admin->role->value}\n\n";
} else {
    echo "No admin user found!\n\n";
}

echo "=== Testing Statistics ===\n\n";

$totalStudents = User::where('role', 'student')->count();
$totalTutors = User::where('role', 'tutor')->count();

echo "Total Students: {$totalStudents}\n";
echo "Total Tutors: {$totalTutors}\n";

echo "\n=== All Users ===\n";
User::all(['id', 'name', 'email', 'role'])->each(function($u) {
    echo "ID: {$u->id}, Name: {$u->name}, Email: {$u->email}, Role: " . ($u->role ? $u->role->value : 'null') . "\n";
});
