<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Annual Report - {{ $year }}</title>
    <style>
        @page { margin: 20mm 15mm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #333; line-height: 1.2; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { color: #17395C; font-size: 16px; font-weight: bold; margin: 0 0 4px 0; }
        .header .subtitle { color: #666; font-size: 10px; margin: 0; }
        table { width: 100%; border-collapse: collapse; font-size: 8px; }
        th, td { border: 1px solid #999; padding: 3px 4px; vertical-align: middle; }
        th { background: #FFFF00; color: #FF0000; font-weight: bold; font-size: 8px; text-align: center; }
        .date-col { width: 80px; text-align: center; }
        .fee-col { width: 55px; text-align: right; padding-right: 4px; }
        .total-col { width: 60px; text-align: right; padding-right: 4px; font-weight: bold; }
        .center { text-align: center; }
        .total-row { background: #FFFF00; color: #FF0000; font-weight: bold; }
        .footer { margin-top: 15px; font-size: 8px; color: #666; }
        .footer-row { display: flex; justify-content: space-between; margin-top: 20px; }
        .footer-col { width: 30%; text-align: center; }
        .footer-col .label { font-weight: bold; margin-bottom: 20px; display: block; }
        .footer-col .line { border-bottom: 1px solid #333; height: 40px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PORT MANAGEMENT UNIT - PASACAO, CAMARINES SUR</h1>
        <h1>Annual Report</h1>
        <p class="subtitle">ANNUAL REPORT FOR THE YEAR {{ $year }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="date-col" rowspan="2">MONTH</th>
                @foreach ($feeTypes as $fee)
                    <th class="fee-col" rowspan="2">{{ $fee->fee_name }}</th>
                @endforeach
                <th class="total-col" rowspan="2">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @php
                $feeTypesList = $feeTypes ?? \App\Models\FeeType::orderBy('fee_name')->get(['id', 'fee_name']);
                $grandTotal = 0;
                $colTotals = [];
                foreach ($feeTypesList as $fee) {
                    $colTotals[$fee->fee_name] = 0;
                }
            @endphp
            @for ($m = 1; $m <= 12; $m++)
                @php
                    $date = \Carbon\Carbon::create($year, $m, 1);
                    $periodKey = $date->format('Y-m');
                    $displayLabel = $date->format('F Y');
                    $transactions = $rows->where('revenue_date', 'like', $periodKey.'%') ?? collect();
                    $rowTotal = 0;
                @endphp
                <tr>
                    <td class="date-col center">{{ $displayLabel }}</td>
                    @foreach ($feeTypesList as $fee)
                        @php
                            $feeName = $fee->fee_name;
                            $subtotal = 0;
                            foreach ($transactions as $tx) {
                                if (strtolower((string) $tx->status) === 'cancelled') continue;
                                foreach ($tx->items as $item) {
                                    if ($item->feeType && $item->feeType->fee_name === $feeName) {
                                        $subtotal += (float) $item->subtotal;
                                    }
                                }
                            }
                            $colTotals[$feeName] = ($colTotals[$feeName] ?? 0) + $subtotal;
                            $rowTotal += $subtotal;
                        @endphp
                        <td class="fee-col">{{ $subtotal > 0 ? number_format($subtotal, 2) : '' }}</td>
                    @endforeach
                    @php
                        if ($rowTotal > 0) { $grandTotal += $rowTotal; }
                    @endphp
                    <td class="total-col">{{ $rowTotal > 0 ? number_format($rowTotal, 2) : '' }}</td>
                </tr>
            @endfor
            <tr class="total-row">
                <td class="date-col center">TOTAL</td>
                @foreach ($feeTypesList as $fee)
                    <td class="fee-col">{{ ($colTotals[$fee->fee_name] ?? 0) > 0 ? number_format($colTotals[$fee->fee_name], 2) : '' }}</td>
                @endforeach
                <td class="total-col">{{ $grandTotal > 0 ? number_format($grandTotal, 2) : '' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div class="footer-row">
            <div class="footer-col">
                <span class="label">Prepared by:</span>
                <div class="line"></div>
                <span>{{ auth()->user()?->name ?? 'System' }}</span>
            </div>
            <div class="footer-col">
                <span class="label">Checked by:</span>
                <div class="line"></div>
                <span></span>
            </div>
            <div class="footer-col">
                <span class="label">Noted by:</span>
                <div class="line"></div>
                <span></span>
            </div>
        </div>
        <p style="text-align: center; margin-top: 10px;">Generated on {{ now()->format('Y-m-d H:i') }}</p>
    </div>
</body>
</html>