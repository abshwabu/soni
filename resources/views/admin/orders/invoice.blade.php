<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - #{{ $order->order_number }} - Soni Perfume</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1a1a1a;
            background-color: #f9fafb;
            padding: 30px;
            font-size: 14px;
            line-height: 1.5;
        }
        .invoice-card {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .no-print {
            max-width: 800px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }
        .btn-primary {
            background-color: #B08D57;
            color: white;
        }
        .btn-primary:hover {
            background-color: #9a7846;
        }
        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 25px;
            margin-bottom: 25px;
        }
        .brand-title {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #B08D57;
            text-transform: uppercase;
        }
        .brand-tagline {
            color: #6b7280;
            font-size: 13px;
        }
        .store-contact {
            margin-top: 8px;
            font-size: 12px;
            color: #4b5563;
            line-height: 1.4;
        }
        .invoice-details {
            text-align: right;
        }
        .invoice-number {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }
        .invoice-meta {
            margin-top: 6px;
            font-size: 12px;
            color: #6b7280;
        }
        .section-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 30px;
        }
        .info-box {
            background: #f9fafb;
            padding: 16px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        .info-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .info-content {
            font-size: 13px;
            color: #1f2937;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            text-align: left;
            padding: 10px 12px;
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4b5563;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
        }
        .text-right {
            text-align: right;
        }
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        .totals-table {
            width: 300px;
        }
        .totals-table tr td {
            padding: 6px 12px;
            font-size: 13px;
        }
        .totals-table tr.grand-total td {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            border-top: 2px solid #e5e7eb;
            padding-top: 10px;
        }
        .notes-section {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 25px;
            font-size: 13px;
            color: #92400e;
        }
        .footer {
            border-top: 1px solid #f3f4f6;
            padding-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 9999px;
            text-transform: uppercase;
        }
        .badge-status {
            background-color: #fef3c7;
            color: #92400e;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .invoice-card {
                box-shadow: none;
                border: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <a href="javascript:window.history.back()" class="btn btn-secondary">← Back to Admin</a>
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print Invoice</button>
    </div>

    <div class="invoice-card">
        <div class="header">
            <div>
                <div class="brand-title">Soni Perfume</div>
                <div class="brand-tagline">Curated Luxury & Everyday Scents</div>
                <div class="store-contact">
                    {{ \App\Models\Setting::get('store_address', 'Bole, Addis Ababa, Ethiopia') }}<br>
                    Tel: {{ config('services.store.phone', env('STORE_PHONE', '+251 91 123 4567')) }}<br>
                    Telegram: {{ \App\Models\Setting::get('telegram_url', 't.me/soniperfume_admin') }}
                </div>
            </div>
            <div class="invoice-details">
                <div class="invoice-number">#{{ $order->order_number }}</div>
                <div class="invoice-meta">
                    <strong>Date:</strong> {{ $order->created_at->format('M d, Y - h:i A') }}<br>
                    <strong>Status:</strong> <span class="badge badge-status">{{ $order->status?->label() }}</span><br>
                    <strong>Method:</strong> {{ $order->delivery_method?->label() }}
                </div>
            </div>
        </div>

        <div class="section-grid">
            <div class="info-box">
                <div class="info-title">Customer Information</div>
                <div class="info-content">
                    <strong>{{ $order->customer_name }}</strong><br>
                    Phone: {{ $order->phone }}<br>
                    @if($order->alt_phone)
                        Alt Phone: {{ $order->alt_phone }}<br>
                    @endif
                </div>
            </div>

            <div class="info-box">
                <div class="info-title">Delivery Details</div>
                <div class="info-content">
                    <strong>{{ $order->city }}</strong><br>
                    {{ $order->address }}<br>
                    Method: {{ $order->delivery_method?->label() }}
                </div>
            </div>
        </div>

        @if($order->notes)
            <div class="notes-section">
                <strong>Customer Notes:</strong> {{ $order->notes }}
            </div>
        @endif

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th>Product</th>
                    <th>Size / Variant</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $item->product_name }}</strong></td>
                        <td>{{ $item->variant_label }}</td>
                        <td class="text-right">{{ $item->formatted_unit_price }}</td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right"><strong>{{ $item->formatted_line_total }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right">{{ $order->formatted_subtotal }}</td>
                </tr>
                <tr>
                    <td>Delivery Fee:</td>
                    <td class="text-right">{{ $order->formatted_delivery_fee }}</td>
                </tr>
                <tr class="grand-total">
                    <td>Grand Total:</td>
                    <td class="text-right">{{ $order->formatted_total }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            Thank you for ordering with Soni Perfume! For inquiries, call {{ env('STORE_PHONE', '+251 91 123 4567') }}.
        </div>
    </div>

</body>
</html>
