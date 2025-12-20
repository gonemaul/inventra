<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $sale->reference_no ?? $sale->id }}</title>
    <style>
        /* --- RESET & BASIC SETUP --- */
        body {
            font-family: 'Courier New', Courier, monospace;
            /* Font struk */
            font-size: 12px;
            margin: 0;
            padding: 20px 0;
            /* Jarak atas bawah di mode preview */
            background-color: #525252;
            /* Latar belakang gelap untuk mode preview */
            color: #000;
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }

        /* --- KERTAS STRUK (PREVIEW MODE) --- */
        .sheet {
            background-color: #fff;
            width: 58mm;
            /* Sesuaikan ukuran kertas (58mm atau 80mm) */
            padding: 5mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            /* Efek bayangan kertas */
            margin-bottom: 60px;
            /* Ruang untuk tombol di bawah */
        }

        /* --- UTILITY CLASSES --- */
        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .border-dashed {
            border-bottom: 1px dashed #000;
            margin: 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            vertical-align: top;
            padding: 2px 0;
        }

        /* --- TOMBOL AKSI (FLOATING) --- */
        .action-bar {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            padding: 10px 20px;
            border-radius: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            gap: 10px;
            z-index: 100;
        }

        .btn {
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
            font-family: sans-serif;
            font-size: 14px;
        }

        .btn-print {
            background: #2563eb;
            color: white;
        }

        .btn-print:hover {
            background: #1d4ed8;
        }

        .btn-close {
            background: #ef4444;
            color: white;
        }

        .btn-close:hover {
            background: #dc2626;
        }

        /* --- MODE CETAK (SAAT PRINTER JALAN) --- */
        @media print {
            @page {
                margin: 0;
                size: auto;
            }

            body {
                background: none;
                display: block;
                padding: 0;
                margin: 0;
            }

            .sheet {
                width: 100%;
                /* Penuhi lebar kertas thermal */
                box-shadow: none;
                padding: 0;
                margin: 0;
            }

            /* .action-bar,
            .no-print {
                display: none !important;
            } */
        }
    </style>
</head>

<body>

    {{-- <div class="action-bar no-print">
        <button onclick="window.print()" class="btn btn-print">🖨 Cetak</button>
        <button onclick="window.close()" class="btn btn-close">Tutup</button>
    </div> --}}

    <div class="sheet">

        <div class="text-center">
            <div class="uppercase bold" style="font-size: 14px; margin-bottom: 3px;">
                {{ $settings['shop_name'] ?? 'NAMA TOKO' }}
            </div>
            <div style="font-size: 11px;">
                {{ $settings['shop_address'] ?? 'Alamat Toko Belum Diisi' }}
            </div>
            <div style="font-size: 11px;">
                {{ $settings['shop_phone'] ?? '' }}
            </div>
        </div>

        <div class="border-dashed"></div>

        <div>
            No: {{ $sale->reference_no }}<br>
            Tgl: {{ date('d/M/y H:i', strtotime($sale->created_at)) }} WIB<br>
            Kasir: {{ $sale->user->name ?? 'Admin' }}<br>
            @if (isset($sale->customer) && $sale->customer->name != 'Umum')
                Member: {{ $sale->customer->name }}
            @endif
        </div>

        <div class="border-dashed"></div>

        <table>
            @foreach ($sale->items as $item)
                @php
                    $qty =
                        (float) $item->quantity == (int) $item->quantity
                            ? (int) $item->quantity
                            : (float) $item->quantity;
                @endphp
                <tr>
                    <td colspan="2" style="padding-bottom: 2px;">
                        {{ $item->product->name ?? $item->product_name }}
                    </td>
                </tr>
                <tr>
                    <td>
                        {{ $qty }} x {{ number_format($item->selling_price, 0, ',', '.') }}
                    </td>
                    <td class="text-right">
                        {{ number_format($item->subtotal, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </table>

        <div class="border-dashed"></div>

        <table>
            <tr>
                <td>Total</td>
                <td class="text-right bold">
                    Rp {{ number_format($sale->total_revenue, 0, ',', '.') }}
                </td>
            </tr>
            @if ($sale->discount_amount > 0)
                <tr>
                    <td>Diskon</td>
                    <td class="text-right">
                        -{{ number_format($sale->discount_amount, 0, ',', '.') }}
                    </td>
                </tr>
            @endif
            <tr>
                <td>Bayar ({{ ucfirst($sale->payment_method) }})</td>
                <td class="text-right">
                    {{ number_format($sale->financial_summary['payment_amount'], 0, ',', '.') }}
                </td>
            </tr>
            @if (isset($sale->financial_summary['change_amount']))
                <tr>
                    <td>Kembali</td>
                    <td class="text-right">
                        {{ number_format($sale->financial_summary['change_amount'], 0, ',', '.') }}
                    </td>
                </tr>
            @endif
        </table>

        <div class="border-dashed"></div>

        <div class="text-center" style="margin-top: 10px;">
            <p>{{ $settings['receipt_footer'] ?? 'Terima Kasih' }}</p>
            {{-- <p style="font-size: 10px;">Barang yang dibeli tidak dapat ditukar</p> --}}
        </div>

    </div>

</body>

</html>
