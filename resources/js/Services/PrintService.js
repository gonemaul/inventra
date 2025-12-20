import { formatQty, formatRupiah } from "@/utils/format"; // Import helper tadi

export const generateReceiptHTML = (transaction) => {
    // 1. Header Toko
    let html = `
        <div style="font-family: 'Courier New', monospace; font-size: 12px; width: 58mm; text-align: center;">
            <h3>NAMA TOKO ANDA</h3>
            <p>Jl. Raya Kediri No. 123<br>Telp: 08123456789</p>
            <hr style="border-top: 1px dashed black;">
            <div style="text-align: left;">
                No: ${transaction.invoice_number}<br>
                Tgl: ${transaction.created_at_formatted}<br>
                Kasir: ${transaction.cashier_name}
            </div>
            <hr style="border-top: 1px dashed black;">
            <table style="width: 100%; font-size: 12px; text-align: left;">
    `;

    // 2. Loop Item (Support Eceran)
    transaction.details.forEach((item) => {
        // Logic Eceran: Qty dikali Harga
        let subtotal = Number(item.qty) * Number(item.price);

        html += `
            <tr>
                <td colspan="2">${item.product_name}</td>
            </tr>
            <tr>
                <td>${formatQty(item.qty)} x ${formatRupiah(item.price)}</td>
                <td style="text-align: right;">${formatRupiah(subtotal)}</td>
            </tr>
        `;
    });

    // 3. Footer & Total
    html += `
            </table>
            <hr style="border-top: 1px dashed black;">
            <div style="text-align: right;">
                Total: <b>${formatRupiah(transaction.total)}</b><br>
                Bayar: ${formatRupiah(transaction.paid_amount)}<br>
                Kembali: ${formatRupiah(transaction.change_amount)}
            </div>
            <hr style="border-top: 1px dashed black;">
            <p style="text-align: center;">Terima Kasih<br>Selamat Belanja Kembali</p>
        </div>
    `;

    return html;
};

export const printTransaction = (transaction) => {
    const content = generateReceiptHTML(transaction);

    // Teknik Print: Buka Popup Window Baru, Tulis HTML, Print, Lalu Tutup
    const printWindow = window.open("", "", "height=600,width=400");

    printWindow.document.write("<html><head><title>Print Receipt</title>");
    // Tambahkan CSS reset untuk print jika perlu
    printWindow.document.write(
        "<style>body { margin: 0; padding: 10px; }</style>"
    );
    printWindow.document.write("</head><body>");
    printWindow.document.write(content);
    printWindow.document.write("</body></html>");

    printWindow.document.close();
    printWindow.focus();

    // Tunggu gambar/resource load (kalau ada), lalu print
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
};
