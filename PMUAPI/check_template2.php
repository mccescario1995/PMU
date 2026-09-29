<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$spreadsheet = IOFactory::load('database/seeders/PMU REPORT TEMPLATE.xlsx');
$sheet = $spreadsheet->getActiveSheet();

echo "Sheets: " . count($spreadsheet->getAllSheets()) . "\n";
foreach ($spreadsheet->getAllSheets() as $i => $s) {
    echo "Sheet $i: " . $s->getTitle() . " (rows: " . $s->getHighestRow() . ", cols: " . $s->getHighestColumn() . ")\n";
}

echo "\n=== ROW 13 ===\n";
for ($col = 1; $col <= 15; $col++) {
    $colLetter = Coordinate::stringFromColumnIndex($col);
    $cell = $sheet->getCell($colLetter . '13');
    $style = $cell->getStyle();
    $fill = $style->getFill();
    echo "Cell {$colLetter}13: value=" . $cell->getValue() . " | font=" . json_encode([
        'name' => $style->getFont()->getName(),
        'size' => $style->getFont()->getSize(),
        'bold' => $style->getFont()->getBold(),
        'color' => $style->getFont()->getColor()->getRGB() ?? 'none'
    ]) . " | fill=" . json_encode([
        'type' => $fill->getFillType(),
        'fg' => $fill->getStartColor()->getRGB() ?? 'none',
        'bg' => $fill->getEndColor()->getRGB() ?? 'none'
    ]) . "\n";
}

echo "\n=== TITLE D11 ===\n";
$cell = $sheet->getCell('D11');
$style = $cell->getStyle();
$fill = $style->getFill();
echo "Cell D11: value=" . $cell->getValue() . " | font=" . json_encode([
    'name' => $style->getFont()->getName(),
    'size' => $style->getFont()->getSize(),
    'bold' => $style->getFont()->getBold(),
    'color' => $style->getFont()->getColor()->getRGB() ?? 'none'
]) . " | fill=" . json_encode([
    'type' => $fill->getFillType(),
    'fg' => $fill->getStartColor()->getRGB() ?? 'none',
    'bg' => $fill->getEndColor()->getRGB() ?? 'none'
]) . "\n";

echo "\n=== FOOTER ROWS 49-53 ===\n";
for ($r = 49; $r <= 53; $r++) {
    $cell = $sheet->getCell('A' . $r);
    $style = $cell->getStyle();
    echo "Cell A$r: value=" . $cell->getValue() . " | font=" . json_encode([
        'name' => $style->getFont()->getName(),
        'size' => $style->getFont()->getSize(),
        'bold' => $style->getFont()->getBold(),
    ]) . "\n";
}