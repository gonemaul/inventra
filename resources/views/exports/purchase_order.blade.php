<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Purchase Order #{{ $po->reference_no }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
        }

        /* Layout */
        .header {
            w-full;
            margin-bottom: 30px;
            border-bottom: 2px solid #84cc16;
            padding-bottom: 10px;
        }

        /* Lime Border */
        .flex-between {
            width: 100%;
            display: table;
        }

        .col-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .col-right {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }

        /* Typography */
        h1 {
            font-size: 24px;
            font-weight: bold;
            color: #3f6212;
            margin: 0;
        }

        /* Dark Lime */
        h2 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
            color: #555;
        }

        p {
            margin: 2px 0;
            line-height: 1.4;
        }

        .status-badge {
            padding: 4px 8px;
            background: #eee;
            border-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            display: inline-block;
            margin-top: 5px;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #f3f4f6;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            text-transform: uppercase;
            font-size: 10px;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row td {
            font-weight: bold;
            background-color: #f9fafb;
            border-top: 2px solid #ddd;
        }

        .grand-total {
            font-size: 14px;
            color: #3f6212;
        }

        /* Footer */
        .footer {
            margin-top: 50px;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .signature-box {
            margin-top: 40px;
            display: table;
            width: 100%;
        }

        .sig-col {
            display: table-cell;
            width: 33%;
            text-align: center;
        }

        .sig-line {
            margin-top: 60px;
            border-top: 1px solid #ccc;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="flex-between">
            <div class="col-left">
                <h1>PURCHASE ORDER</h1>
                <p><strong>{{ $store['name'] }}</strong></p>
                <p>{{ $store['address'] }}</p>
                <p>Telp: {{ $store['phone'] }} | Email: {{ $store['email'] }}</p>
            </div>
            <div class="col-right">
                <p>No. Ref: <strong>{{ $po->reference_no }}</strong></p>
                <p>Tanggal: {{ \Carbon\Carbon::parse($po->transaction_date)->format('d F Y') }}</p>
                <div class="status-badge">{{ $po->status }}</div>
            </div>
        </div>
    </div>

    <div class="flex-between">
        <div class="col-left">
            <h2>Kepada Supplier:</h2>
            <p><strong>{{ $po->supplier->name }}</strong></p>
            <p>{{ $po->supplier->address ?? 'Alamat tidak tersedia' }}</p>
            <p>Telp: {{ $po->supplier->phone ?? '-' }}</p>
        </div>
        <div class="col-right">
            <h2>Catatan:</h2>
            <p style="font-style: italic; color: #666;">
                {{ $po->notes ?: '-' }}
            </p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="45%">Nama Produk</th>
                <th width="15%" class="text-center">Qty</th>
                <th width="15%" class="text-right">Harga Satuan</th>
                <th width="20%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($po->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->product->name }}</strong><br>
                        <span style="font-size: 10px; color: #777;">
                            Kode: {{ $item->product->code }}
                            @if ($item->product->size)
                                | Size: {{ $item->product->size->name }}
                            @endif
                        </span>
                    </td>
                    <td class="text-center">
                        {{ $item->quantity }} {{ $item->product->unit->name ?? 'Pcs' }}
                    </td>
                    <td class="text-right">Rp {{ number_format($item->purchase_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="3"></td>
                <td class="text-right">Subtotal</td>
                <td class="text-right">Rp {{ number_format($po->items->sum('subtotal'), 0, ',', '.') }}</td>
            </tr>
            @if ($po->shipping_cost > 0)
                <tr>
                    <td colspan="3" style="border: none;"></td>
                    <td class="text-right">Ongkir</td>
                    <td class="text-right">Rp {{ number_format($po->shipping_cost, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td colspan="3" style="border: none;"></td>
                <td class="text-right" style="font-size: 14px;">GRAND TOTAL</td>
                <td class="text-right grand-total">Rp
                    {{ number_format($po->grand_total > 0 ? $po->grand_total : $po->items->sum('subtotal'), 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="signature-box">
        <div class="sig-col">
            <p>Dibuat Oleh,</p>
            <div class="sig-line"></div>
            <p>{{ $po->user->name ?? 'Admin' }}</p>
        </div>
        <div class="sig-col">
        </div>
        <div class="sig-col">
            <p>Disetujui Supplier,</p>
            <div class="sig-line"></div>
            <p>( ........................... )</p>
        </div>
    </div>

    <div class="footer">
        <p>Dokumen ini dibuat secara otomatis oleh Sistem Inventra pada {{ now()->format('d/m/Y H:i') }}</p>
    </div>

</body>

</html>
