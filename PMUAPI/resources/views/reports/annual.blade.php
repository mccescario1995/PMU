<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Annual Report - {{ $year }}</title>
    <style>
        body { font-family: sans-serif; margin: 40px; }
        h1 { color: #17395C; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #17395C; color: white; }
        .total { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Annual Report - {{ $year }}</h1>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Revenue</th>
                <th>Transactions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row->revenue_date }}</td>
                    <td>{{ number_format($row->total_revenue, 2) }}</td>
                    <td>{{ $row->transaction_count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="total">
        <p>Total Revenue: {{ number_format($totalRevenue, 2) }}</p>
        <p>Total Transactions: {{ $totalTransactions }}</p>
    </div>
</body>
</html>