<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Report - {{ $date }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 30px; color: #333; }
        h1 { color: #17395C; font-size: 20px; margin-bottom: 4px; }
        .subtitle { color: #666; font-size: 12px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #17395C; color: white; font-weight: bold; }
        .amount { text-align: right; }
        .total { margin-top: 16px; padding: 10px; background: #f5f5f5; font-weight: bold; font-size: 13px; }
        .footer { margin-top: 20px; font-size: 10px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <h1>Daily Report</h1>
    <p class="subtitle">{{ \Carbon\Carbon::parse($date)->format('l, F d, Y') }}</p>
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">ID</th>
                <th style="width: 25%;">Stakeholder</th>
                <th style="width: 35%;">Fee Types</th>
                <th style="width: 17%;" class="amount">Amount</th>
                <th style="width: 15%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $tx)
                <tr>
                    <td>#{{ $tx->id }}</td>
                    <td>{{ $tx->stakeholder?->name ?? '-' }}</td>
                    <td>{{ $tx->items->map(fn($i) => $i->feeType?->fee_name)->filter()->join(', ') ?: '-' }}</td>
                    <td class="amount">₱ {{ number_format($tx->total_amount, 2) }}</td>
                    <td>{{ ucfirst($tx->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="total">
        <p>Total Transactions: {{ $count }}</p>
        <p>Total Collection: ₱ {{ number_format($total, 2) }}</p>
    </div>
    <div class="footer">Generated on {{ now()->format('Y-m-d H:i') }}</div>
</body>
</html>
