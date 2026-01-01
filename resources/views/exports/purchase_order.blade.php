<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $po->reference_no }}</title>
    <style>
        /* RESET & BASICS */
        @page {
            margin: 0cm;
        }

        body {
            margin-top: 3cm;
            /* Sesuaikan dengan tinggi header */
            margin-left: 2cm;
            margin-right: 2cm;
            margin-bottom: 2cm;
            /* Sesuaikan dengan tinggi footer */
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.3;
        }

        /* Definisi Footer Tetap */
        footer {
            position: fixed;
            bottom: 0cm;
            left: 0cm;
            right: 0cm;
            height: 1.5cm;
            /* Tinggi area footer */

            /* Styling teks */
            text-align: center;
            color: #999;
            font-size: 8pt;
            border-top: 1px solid #eee;
            padding-top: 10px;
            background-color: #fff;
            /* Tutup konten yang lewat di belakangnya */

            /* Kunci agar footer berada di tengah secara vertikal di area margin bawah */
            display: block;
        }

        /* Magic Script untuk Nomor Halaman DOMPDF */
        .page-number:after {
            content: counter(page);
        }

        /* HELPER CLASSES */
        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-bold {
            font-weight: bold;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .mb-1 {
            margin-bottom: 5px;
        }

        .mb-2 {
            margin-bottom: 10px;
        }

        .mb-4 {
            margin-bottom: 20px;
        }

        .w-full {
            width: 100%;
        }

        /* COLORS (Inventra Green Theme) */
        .bg-primary {
            background-color: #f0fdf4;
            /* Green-50 */
        }

        .text-primary {
            color: #166534;
            /* Green-800 */
        }

        .border-primary {
            border-color: #166534;
        }

        /* LAYOUT (Table-based for PDF Safety) */
        table.layout-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.layout-grid td {
            vertical-align: top;
        }

        /* HEADER */
        .header-title {
            font-size: 24pt;
            font-weight: bold;
            color: #166534;
            letter-spacing: 2px;
        }

        .header-meta {
            font-size: 9pt;
            color: #555;
        }

        /* INFO BOXES */
        .box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 10px;
            border-radius: 4px;
            height: 100px;
        }

        .box-title {
            font-size: 8pt;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px;
        }

        /* ITEMS TABLE */
        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table-items th {
            background-color: #166534;
            color: white;
            padding: 8px;
            font-size: 9pt;
            text-transform: uppercase;
            text-align: left;
        }

        .table-items td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9pt;
        }

        /* Zebra Striping */
        .table-items tr:nth-child(even) {
            background-color: #f9fafb;
        }

        /* TOTALS SECTION */
        .totals-table {
            width: 40%;
            margin-left: auto;
            /* Align right */
            margin-top: 10px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 5px;
            border-bottom: 1px solid #eee;
        }

        .grand-total-row td {
            background-color: #166534;
            color: white;
            font-weight: bold;
            font-size: 11pt;
            border: none;
        }

        /* FOOTER & SIGNATURE */
        .footer-note {
            margin-top: 30px;
            font-size: 8pt;
            color: #666;
            border-left: 3px solid #166534;
            padding-left: 10px;
        }

        /* .signature-area {
            margin-top: 40px;
            width: 100%;
        }

        .sign-box {
            width: 30%;
            text-align: center;
        }

        .sign-line {
            margin-top: 50px;
            border-top: 1px solid #333;
        } */

        /* --- SUMMARY SECTION STYLES --- */
        .summary-container {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .note-box {
            background-color: #f8f9fa;
            /* Abu sangat muda */
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            font-size: 9pt;
            color: #555;
            /* height: 100%; */
            /* Agar tinggi mengikuti */
        }

        .total-box {
            width: 100%;
            border-collapse: collapse;
        }

        .total-row td {
            padding: 8px 5px;
            font-size: 10pt;
            color: #444;
        }

        .total-label {
            text-align: right;
            color: #666;
        }

        .total-value {
            text-align: right;
            font-weight: bold;
            width: 140px;
            /* Fixed width agar sejajar */
        }

        /* KOTAK GRAND TOTAL (Modern Look) */
        .grand-total-container {
            background-color: #166534;
            /* Warna Utama */
            color: #ffffff;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
        }

        .grand-total-label {
            font-size: 9pt;
            text-transform: uppercase;
            opacity: 0.9;
            margin-bottom: 2px;
            text-align: right;
        }

        .grand-total-amount {
            font-size: 16pt;
            font-weight: bold;
            text-align: right;
            letter-spacing: 0.5px;
        }

        /* TERBILANG */
        .amount-in-words {
            font-style: italic;
            font-size: 8pt;
            color: #777;
            margin-top: 8px;
            text-align: right;
            padding-right: 5px;
            border-top: 1px dashed #ddd;
            padding-top: 5px;
        }

        /* SIGNATURE MODERN */
        .signature-table {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }

        .sig-box {
            text-align: center;
            vertical-align: bottom;
        }

        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
        }

        .sig-title {
            font-size: 8pt;
            color: #666;
        }
    </style>
</head>

<body>
    <footer>
        Dicetak otomatis oleh Sistem Inventra | Halaman <span class="page-number"></span>
    </footer>
    <main>

        <table class="mb-4 layout-grid">
            <tr>
                <td width="60%">
                    <div style="font-size: 16pt; font-weight: bold; color: #333;">{{ $store['name'] }}</div>
                    <div style="font-size: 9pt; color: #555;">
                        {{ $store['address'] }}<br>
                        {{ $store['phone'] }} {{ $store['email'] ? ' | ' . $store['email'] : '' }}
                    </div>
                </td>
                <td width="40%" class="text-right">
                    <div class="header-title">PURCHASE ORDER</div>
                    <div class="header-meta">
                        REF NO: <strong>#{{ $po->reference_no }}</strong><br>
                        TANGGAL: {{ \Carbon\Carbon::parse($po->transaction_date)->format('d M Y') }}<br>
                        STATUS: <span class="uppercase">{{ $po->status }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <table class="layout-grid" style="margin-bottom: 20px; border-spacing: 0 10px;">
            <tr>
                <td width="48%" style="padding-right: 10px;">
                    <div class="box">
                        <div class="box-title">KEPADA SUPPLIER (VENDOR)</div>
                        <div class="text-bold">{{ $po->supplier->name }}</div>
                        <div style="font-size: 9pt;">
                            {{ $po->supplier->address ?? '-' }}<br>
                            Telp: {{ $po->supplier->phone ?? '-' }}
                        </div>
                    </div>
                </td>
                <td width="48%" style="padding-left: 10px;">
                    <div class="box">
                        <div class="box-title">DIKIRIM KE (SHIP TO)</div>
                        <div class="text-bold">{{ $store['name'] }} (Gudang Utama)</div>
                        <div style="font-size: 9pt;">
                            {{ $store['address'] }}<br>
                            Penerima: {{ $po->user->name ?? 'Admin Gudang' }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="table-items">
            <thead>
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="45%">Deskripsi Produk</th>
                    <th width="15%" class="text-center">Qty</th>
                    <th width="15%" class="text-right">Harga (@)</th>
                    <th width="20%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($po->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <span class="text-bold">{{ $item->product->name }}</span><br>
                            <span style="font-size: 8pt; color: #666;">
                                SKU: {{ $item->product->code }}
                                {{ $item->product->size ? '| Size: ' . $item->product->size->name : '' }}
                            </span>
                            @if ($item->note)
                                <div style="font-size: 8pt; color: #d97706; font-style: italic;">Note:
                                    {{ $item->note }}
                                </div>
                            @endif
                        </td>
                        <td class="text-center">
                            {{ $item->quantity }} {{ $item->product->unit->name ?? 'Unit' }}
                        </td>
                        <td class="text-right">{{ number_format($item->purchase_price, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach

                @if (count($po->items) < 5)
                    <tr>
                        <td colspan="5" style="height: {{ (5 - count($po->items)) * 20 }}px;"></td>
                    </tr>
                @endif
            </tbody>
        </table>

        <table class="summary-container">
            <tr>
                <td width="60%" style="vertical-align: top; padding-right: 20px;">
                    <div class="note-box">
                        <div
                            style="font-weight: bold; color: #166534; margin-bottom: 5px; font-size: 10px; text-transform: uppercase;">
                            Catatan / Instruksi:
                        </div>
                        <p style="margin: 0; line-height: 1.5;">
                            {{ $po->notes ?: 'Tidak ada catatan khusus untuk pesanan ini.' }}
                        </p>

                        <div style="margin-top: 15px; font-size: 8pt; color: #888;">
                            <strong>Ketentuan:</strong><br>
                            1. Mohon konfirmasi jika barang tidak tersedia.<br>
                            2. Faktur asli wajib disertakan saat pengiriman.
                        </div>
                    </div>
                </td>

                <td width="40%" style="vertical-align: top;">
                    <table class="total-box">
                        <tr class="total-row">
                            <td class="total-label">Subtotal</td>
                            <td class="total-value">
                                Rp {{ number_format($po->items->sum('subtotal'), 0, ',', '.') }}
                            </td>
                        </tr>

                        @if ($po->shipping_cost > 0)
                            <tr class="total-row">
                                <td class="total-label">Biaya Kirim (+)</td>
                                <td class="total-value">
                                    Rp {{ number_format($po->shipping_cost, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif

                        @if ($po->other_costs > 0)
                            <tr class="total-row">
                                <td class="total-label">Biaya Lain (+)</td>
                                <td class="total-value">
                                    Rp {{ number_format($po->other_costs, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                    </table>

                    @php
                        $gTotal =
                            $po->grand_total > 0
                                ? $po->grand_total
                                : $po->items->sum('subtotal') + $po->shipping_cost + $po->other_costs;
                    @endphp

                    <div class="grand-total-container">
                        <div class="grand-total-label">Total Tagihan (IDR)</div>
                        <div class="grand-total-amount">
                            Rp {{ number_format($gTotal, 0, ',', '.') }}
                        </div>
                    </div>

                </td>
            </tr>
        </table>

        <table class="signature-table">
            <tr>
                <td width="35%" class="sig-box">
                    <div style="margin-bottom: 60px; font-size: 9pt;">
                        Disetujui Oleh Supplier,
                    </div>
                    <div class="sig-name">{{ $po->supplier->name }}</div>
                    <div class="sig-title">Tanda Tangan & Stempel</div>
                </td>

                <td width="30%"></td>
                <td width="35%" class="sig-box">
                    <div style="margin-bottom: 60px; font-size: 9pt;">
                        {{ \Carbon\Carbon::now()->format('d F Y') }}<br>
                        Dibuat Oleh,
                    </div>
                    <div class="sig-name">{{ $po->user->name ?? 'Admin Purchasing' }}</div>
                    <div class="sig-title">{{ $store['name'] }}</div>
                </td>
            </tr>
        </table>
    </main>

</body>

</html>
