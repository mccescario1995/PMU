<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Report - {{ $date }}</title>
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
    <h1>Daily Report - {{ $date }}</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Stakeholder</th>
                <th>Fee Types</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $tx)
                <tr>
                    <td>#{{ $tx->id }}</td>
                    <td>{{ $tx->stakeholder?->name ?? '-' }}</td>
                    <td>{{ $tx->items->map(fn($i) => $i->feeType?->fee_name)->filter()->join(', ') ?: '-' }}</td>
                    <td>{{ number_format($tx->total_amount, 2) }}</td>
                    <td>{{ $tx->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="total">
        <p>Total Transactions: {{ $count }}</p>
        <p>Total Collection: {{ number_format($total, 2) }}</p>
    </div>
</body>
</html>