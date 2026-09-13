<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RevenueHistory;

$result = RevenueHistory::orderBy('revenue_date')->take(500)->get(['revenue_date', 'total_revenue', 'transaction_count']);
echo 'Records: ' . count($result) . PHP_EOL;
if (count($result) > 0) {
    $first = $result[0];
    echo 'First: ' . $first->revenue_date . ' => ' . $first->total_revenue . PHP_EOL;
    $last = $result[count($result)-1];
    echo 'Last: ' . $last->revenue_date . ' => ' . $last->total_revenue . PHP_EOL;
}
