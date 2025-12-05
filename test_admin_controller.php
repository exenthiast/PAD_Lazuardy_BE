<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;
use App\Models\User;

echo "=== Testing AdminDashboardController ===\n\n";

// Simulate authenticated admin user
$admin = User::where('role', 'admin')->first();

if (!$admin) {
    echo "No admin user found! Please create one first.\n";
    exit(1);
}

echo "Testing as: {$admin->name} ({$admin->email})\n\n";

// Create a test request for statistics
$request = Request::create('/api/admin/dashboard/statistics', 'GET');
$request->setUserResolver(function () use ($admin) {
    return $admin;
});

$controller = new \App\Http\Controllers\AdminDashboardController();

echo "=== GET /api/admin/dashboard/statistics ===\n";
try {
    $response = $controller->getStatistics($request);
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Response: " . $response->getContent() . "\n\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

echo "=== GET /api/admin/dashboard/pending-tutors ===\n";
try {
    $response = $controller->getPendingTutors($request);
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Response: " . $response->getContent() . "\n\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

echo "=== GET /api/admin/dashboard/pending-payments ===\n";
try {
    $response = $controller->getPendingPayments($request);
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Response: " . $response->getContent() . "\n\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}
