<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #{{ $sale->reference_no ?? $sale->id }}</title>
    <style>
        /* CSS Khusus Thermal Printer 58mm */
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .container {
            width: 58mm;
            padding: 2mm;
            margin: 0 auto;
        }

        /* Atur 58mm atau 80mm */
        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .border-bottom {
            border-bottom: 1px dashed #000;
            margin: 5px 0;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .items td {
            padding: 2px 0;
            vertical-align: top;
        }

        /* Hilangkan header/footer browser saat print */
        @media print {
            @page {
                margin: 0;
                size: auto;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="container">
        <div class="text-center">
            <div class="bold" style="font-size: 14px;">{{ config('app.name', 'INVENTRA POS') }}</div>
            <div>Jl. Contoh No. 123, Kota Kediri</div>
            <div>Telp: 0812-3456-7890</div>
        </div>

        <div class="border-bottom"></div>

        <div>
            No: {{ $sale->reference_no ?? 'INV-' . $sale->id }}<br>
            Tgl: {{ date('d/m/Y H:i', strtotime($sale->created_at)) }}<br>
            Kasir: {{ $sale->user->name ?? 'Admin' }}<br>
            @if ($sale->customer)
                Member: {{ $sale->customer->name }}
            @endif
        </div>

        <div class="border-bottom"></div>

        <table class="items">
            @foreach ($sale->items as $item)
                <tr>
                    <td colspan="2">{{ $item->product->name ?? $item->product_name }}</td>
                </tr>
                <tr>
                    <td>{{ $item->quantity }} x {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </table>

        <div class="border-bottom"></div>

        <table style="width: 100%">
            <tr>
                <td>Total</td>
                <td class="text-right bold">{{ number_format($sale->total_revenue, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Bayar ({{ ucfirst($sale->payment_method) }})</td>
                <td class="text-right">{{ number_format($sale->payment_amount ?? $sale->total_revenue, 0, ',', '.') }}
                </td>
            </tr>
            @if (isset($sale->change_amount))
                <tr>
                    <td>Kembali</td>
                    <td class="text-right">{{ number_format($sale->change_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
        </table>

        <div class="border-bottom"></div>
        <div class="text-center" style="margin-top: 10px;">
            Terima Kasih<br>
            Barang yang dibeli tidak dapat ditukar
        </div>
    </div>

    <script>
        window.onafterprint = function() {
            window.close();
        }
    </script>
</body>

</html>
