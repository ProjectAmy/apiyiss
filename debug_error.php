<?php

use App\Models\Student;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

try {
    echo "Testing Student query...\n";
    $students = Student::with('walimuridProfile')->get();
    echo "Query successful. Count: " . $students->count() . "\n";
    echo "First student: " . json_encode($students->first()) . "\n";
} catch (\Throwable $e) {
    echo "ERROR caught:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
