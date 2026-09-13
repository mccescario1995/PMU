<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RevenueHistory;

$min = RevenueHistory::min('revenue_date');
$max = RevenueHistory::max('revenue_date');
$count = RevenueHistory::count();
$sum = RevenueHistory::sum('total_revenue');

echo "Count: $count" . PHP_EOL;
echo "Min date: $min" . PHP_EOL;
echo "Max date: $max" . PHP_EOL;
echo "Sum: $sum" . PHP_EOL;

$sample = RevenueHistory::orderBy('revenue_date')->take(5)->get(['revenue_date', 'total_revenue']);
foreach ($sample as $row) {
    echo $row->revenue_date . ' => ' . $row->total_revenue . PHP_EOL;
}
