<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Report - {{ $date }}</title>
    <style>
        @page { margin: 15mm; size: A4 portrait; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #333; line-height: 1.15; }
        .header { text-align: center; margin-bottom: 8px; }
        .header h1 { color: #17395C; font-size: 14px; font-weight: bold; margin: 0 0 2px 0; }
        .header .subtitle { color: #666; font-size: 9px; margin: 0; }
        table { width: 100%; border-collapse: collapse; font-size: 7.5px; table-layout: fixed; }
        th, td { border: 1px solid #999; padding: 2px 3px; vertical-align: middle; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        th { background: #FFFF00; color: #FF0000; font-weight: bold; font-size: 7px; text-align: center; }
        .date-col { width: 55px; text-align: center; }
        .fee-col { width: 48px; text-align: right; padding-right: 3px; }
        .total-col { width: 55px; text-align: right; padding-right: 3px; font-weight: bold; }
        .center { text-align: center; }
        .cancelled { background: #FF0000 !important; color: #FFFFFF !important; font-weight: bold; }
        .pending { background: #FFFF99 !important; color: #000000 !important; font-weight: bold; }
        .total-row { background: #FFFF00; color: #FF0000; font-weight: bold; }
        .footer { margin-top: 12px; font-size: 7.5px; color: #666; }
        .footer-row { display: flex; justify-content: space-between; margin-top: 18px; }
        .footer-col { width: 30%; text-align: center; }
        .footer-col .label { font-weight: bold; margin-bottom: 18px; display: block; }
        .footer-col .line { border-bottom: 1px solid #333; height: 36px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PORT MANAGEMENT UNIT - PASACAO, CAMARINES SUR</h1>
        <h1>Daily Report</h1>
        <p class="subtitle">DAILY REPORT FOR {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="date-col" rowspan="2">DATE</th>
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
            @foreach ($transactions as $tx)
                @php
                    $status = strtolower((string) $tx->status);
                    $isCancelled = $status === 'cancelled';
                    $isPending = $status === 'pending';
                    $includeInTotals = !($isCancelled || $isPending);
                    $rowTotal = 0;
                    $rowHasValue = false;
                    $rowClass = '';
                    if ($isCancelled) { $rowClass = 'cancelled'; }
                    elseif ($isPending) { $rowClass = 'pending'; }
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="date-col center">{{ \Carbon\Carbon::parse($tx->transaction_date)->format('m/d/Y') }}</td>
                    @foreach ($feeTypesList as $fee)
                        @php
                            $feeName = $fee->fee_name;
                            $subtotal = 0;
                            foreach ($tx->items as $item) {
                                if ($item->feeType && $item->feeType->fee_name === $feeName) {
                                    $subtotal += (float) $item->subtotal;
                                }
                            }
                            $colTotals[$feeName] = ($colTotals[$feeName] ?? 0) + ($includeInTotals ? $subtotal : 0);
                            if ($subtotal > 0) { $rowHasValue = true; }
                            $rowTotal += $includeInTotals ? $subtotal : 0;
                        @endphp
                        <td class="fee-col">{{ $subtotal > 0 ? number_format($subtotal, 2) : '' }}</td>
                    @endforeach
                    @php
                        if ($includeInTotals && $rowTotal > 0) { $grandTotal += $rowTotal; }
                    @endphp
                    <td class="total-col">{{ ($includeInTotals && $rowTotal > 0) ? number_format($rowTotal, 2) : '' }}</td>
                </tr>
            @endforeach
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