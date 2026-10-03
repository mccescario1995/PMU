<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Annual Report - {{ $year }}</title>
<style>
        @page { margin: 15mm; size: A4 portrait; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #333; line-height: 1.15; }
        .header { text-align: center; margin-bottom: 8px; }
        .header h1 { color: #17395C; font-size: 12px; font-weight: bold; margin: 0 0 2px 0; word-wrap: break-word; overflow-wrap: break-word; }
        .header .subtitle { color: #666; font-size: 9px; margin: 0; word-wrap: break-word; overflow-wrap: break-word; }
        table { width: 100%; border-collapse: collapse; font-size: 7.5px; table-layout: fixed; }
        th, td { border: 1px solid #999; padding: 2px 3px; vertical-align: middle; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        th { background: #FFFF00; color: #FF0000; font-weight: bold; font-size: 7px; text-align: center; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
        .date-col { width: 75px; text-align: center; }
        .fee-col { width: 48px; text-align: right; padding-right: 3px; }
        .total-col { width: 55px; text-align: right; padding-right: 3px; font-weight: bold; }
        .center { text-align: center; }
        .total-row { background: #FFFF00; color: #FF0000; font-weight: bold; }
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
                $grandTotal = 0;
                $colTotals = [];
                foreach ($feeTypes as $fee) { $colTotals[$fee->fee_name] = 0; }
            @endphp
            @foreach ($periodLabels as $periodKey => $displayLabel)
                @php
                    $transactions = $monthlyData[$periodKey] ?? collect();
                    $rowTotal = 0;
                @endphp
                <tr>
                    <td class="date-col center">{{ $displayLabel }}</td>
                    @foreach ($feeTypes as $fee)
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
                    @php $grandTotal += $rowTotal; @endphp
                    <td class="total-col">{{ $rowTotal > 0 ? number_format($rowTotal, 2) : '' }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="date-col center">TOTAL</td>
                @foreach ($feeTypes as $fee)
                    <td class="fee-col">{{ ($colTotals[$fee->fee_name] ?? 0) > 0 ? number_format($colTotals[$fee->fee_name], 2) : '' }}</td>
                @endforeach
                <td class="total-col">{{ $grandTotal > 0 ? number_format($grandTotal, 2) : '' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <table style="width: 100%; border: none; margin-top: 20px; font-size: 7.5px;">
            <tr style="border: none;">
                <td style="width: 45%; border: none; text-align: left; vertical-align: top;">
                    <strong>Prepared by:</strong><br>
                    <span style="font-weight: bold;">{{ auth()->user()?->name ?? 'MARTE C. DAQUIZ' }}</span><br>
                    <span>Port Statistician</span>
                </td>
                <td style="width: 10%; border: none;"></td>
                <td style="width: 45%; border: none; text-align: left; vertical-align: top;">
                    <strong>Noted by:</strong><br>
                    <span style="font-weight: bold;">WILSON B. RABAJE</span><br>
                    <span>OIC-Port Manager</span>
                </td>
            </tr>
        </table>
        <p style="text-align: center; margin-top: 10px;">Generated on {{ now()->format('Y-m-d H:i') }}</p>
    </div>
</body>
</html>