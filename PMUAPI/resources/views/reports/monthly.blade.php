<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Report - {{ $month }}</title>
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
    <h1>Monthly Report</h1>
    <p class="subtitle">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</p>
    <table>
        <thead>
            <tr>
                <th style="width: 30%;">Date</th>
                <th style="width: 35%;" class="amount">Revenue</th>
                <th style="width: 35%;">Transactions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row->revenue_date)->format('M d, Y') }}</td>
                    <td class="amount">₱ {{ number_format($row->total_revenue, 2) }}</td>
                    <td>{{ $row->transaction_count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="total">
        <p>Total Revenue: ₱ {{ number_format($totalRevenue, 2) }}</p>
        <p>Total Transactions: {{ $totalTransactions }}</p>
    </div>
    <div class="footer">Generated on {{ now()->format('Y-m-d H:i') }}</div>
</body>
</html>
