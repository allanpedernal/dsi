<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #666; font-size: 9px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f2f2f2; font-size: 10px; text-transform: uppercase; }
        tr:nth-child(even) td { background: #fafafa; }
        .right { text-align: right; }
        .summary { margin-top: 16px; width: 320px; float: right; }
        .summary td { border: 0; padding: 3px 0; }
        .summary td:last-child { text-align: right; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; background: #e0e7ff; color: #1e40af; }
    </style>
</head>
<body>
<h1>Sales Report</h1>
<div class="meta">
    Generated {{ $generatedAt->format('Y-m-d H:i') }}
    @if(!empty($filters['from'])) &middot; From {{ $filters['from'] }} @endif
    @if(!empty($filters['to'])) &middot; To {{ $filters['to'] }} @endif
    @if(!empty($filters['status'])) &middot; Status: {{ ucfirst($filters['status']) }} @endif
</div>

<table>
    <thead>
    <tr>
        <th>Reference</th>
        <th>Date</th>
        <th>Customer</th>
        <th>Status</th>
        <th class="right">Subtotal</th>
        <th class="right">Tax</th>
        <th class="right">Total</th>
        <th>Source</th>
    </tr>
    </thead>
    <tbody>
    @foreach($sales as $sale)
        <tr>
            <td>{{ $sale->reference }}</td>
            <td>{{ $sale->created_at?->format('Y-m-d H:i') }}</td>
            <td>{{ $sale->customer?->full_name }}</td>
            <td>{{ $sale->status->label() }}</td>
            <td class="right">{{ number_format($sale->subtotal, 2) }}</td>
            <td class="right">{{ number_format($sale->tax, 2) }}</td>
            <td class="right">{{ number_format($sale->total, 2) }}</td>
            <td><span class="badge">{{ ucfirst($sale->source) }}</span></td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="summary">
    <tr><td>Orders</td><td>{{ $aggregate['count'] }}</td></tr>
    <tr><td>Subtotal</td><td>{{ number_format($aggregate['subtotal'], 2) }}</td></tr>
    <tr><td>Tax</td><td>{{ number_format($aggregate['tax'], 2) }}</td></tr>
    <tr><td>Discount</td><td>{{ number_format($aggregate['discount'], 2) }}</td></tr>
    <tr><td>Grand Total</td><td>{{ number_format($aggregate['total'], 2) }}</td></tr>
</table>
</body>
</html>
