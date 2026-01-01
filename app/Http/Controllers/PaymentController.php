<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Services\ImageService;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    protected $imageService;
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    private function stats()
    {
        $today = now()->format('Y-m-d');
        $threeDaysLater = now()->addDays(3)->format('Y-m-d');

        $statsData = DB::table('purchase_invoices')
            ->join('purchases', 'purchase_invoices.purchase_id', '=', 'purchases.id')
            ->where('purchases.status', Purchase::STATUS_COMPLETED)
            ->selectRaw("
            -- Total Uang
            SUM(total_amount) as total_bill,
            SUM(amount_paid) as total_paid,

            -- Hitung Jumlah Nota (Counts)
            COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as count_paid,
            COUNT(CASE WHEN payment_status != 'paid' THEN 1 END) as count_unpaid,

            -- Logic Jatuh Tempo
            COUNT(CASE WHEN payment_status != 'paid' AND due_date < ? THEN 1 END) as count_overdue,
            COUNT(CASE WHEN payment_status != 'paid' AND due_date >= ? AND due_date <= ? THEN 1 END) as count_approaching
        ", [$today, $today, $threeDaysLater])
            ->first(); // Eksekusi 1 Query saja!

        // --- 2. LAST PAYMENT DATE ---
        // Mengambil tanggal bayar paling baru dari tabel payments
        $lastPaymentDate = PurchasePayment::latest('payment_date')->value('payment_date');

        // --- 3. LOGIC NOTIFIKASI (Di PHP saja biar cepat) ---
        $alerts = [];

        // Notifikasi Overdue
        if ($statsData->count_overdue > 0) {
            $alerts[] = [
                'type' => 'danger', // Merah
                'message' => "Perhatian! Ada {$statsData->count_overdue} nota yang sudah melewati jatuh tempo."
            ];
        }

        // Notifikasi Mendekati Jatuh Tempo
        if ($statsData->count_approaching > 0) {
            $alerts[] = [
                'type' => 'warning', // Kuning
                'message' => "Siapkan dana! {$statsData->count_approaching} nota akan jatuh tempo dalam 3 hari ke depan."
            ];
        }

        // Gabungkan data untuk dikirim ke Frontend
        $stats = [
            'financial' => [
                'total_bill' => $statsData->total_bill ?? 0,
                'total_paid' => $statsData->total_paid ?? 0,
                'total_debt' => ($statsData->total_bill - $statsData->total_paid) ?? 0,
            ],
            'counts' => [
                'paid' => $statsData->count_paid,
                'unpaid' => $statsData->count_unpaid,
                'overdue' => $statsData->count_overdue,
            ],
            'last_payment' => $lastPaymentDate, // Bisa null jika belum ada pembayaran sama sekali
            'alerts' => $alerts
        ];
        return $stats;
    }
    public function index()
    {
        $query = PurchaseInvoice::with(['purchase.supplier'])
            ->whereRelation('purchase', 'status', Purchase::STATUS_COMPLETED);
        $sortBy = $params['sort'] ?? 'due_date';
        $sortDirection = $params['order'] ?? 'asc';
        $perPage = $params['per_page'] ?? 10;
        $query->orderByRaw("CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END ASC");
        $invoices = $query->orderBy($sortBy, $sortDirection)->paginate($perPage)->withQueryString();
        if (request()->wantsJson() && ! request()->header('X-Inertia')) {
            return response()->json($invoices);
        }

        return Inertia::render('Keuangan/index', [
            'stats' => $this->stats(),
            'invoices' => $invoices
        ]);
    }
    public function show($id)
    {
        // Halaman Detail & Bayar
        $invoice = PurchaseInvoice::with([
            'purchase.supplier',
            'items.product',
            'payments' => function ($q) {
                $q->orderBy('payment_date', 'desc'); // Riwayat terbaru di atas
            },
        ])
            ->findOrFail($id);

        return Inertia::render('Keuangan/detail', [
            'paymentMethods' => PurchasePayment::PAYMENT_METHODS,
            'invoice' => $invoice
        ]);
    }
    public function store(Request $request, $invoiceId)
    {

        $invoice = PurchaseInvoice::findOrFail($invoiceId);

        // Cek apakah pembayaran melebihi sisa hutang
        $remainingDebt = $invoice->total_amount - $invoice->amount_paid;
        // VALIDASI
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1', "max:{$remainingDebt}"],
            'payment_date' => 'required|date',
            'payment_method' => ['required', Rule::in(PurchasePayment::PAYMENT_METHODS)],
            'proof_image' => 'nullable|image|max:20480', // Max 2MB
            'notes' => 'nullable|string|max:500',
        ], [
            'amount.max' => 'Nominal pembayaran melebihi sisa hutang saat ini.',
        ]);

        try {
            DB::transaction(function () use ($request, $invoice) {
                // 1. Upload Gambar Bukti (Jika ada)
                if ($request->hasFile('proof_image')) {
                    $newPath = $this->imageService->upload(
                        $request->file('proof_image'),
                        'payment_proofs',
                    );
                }

                // 2. Simpan History Pembayaran
                $invoice->payments()->create([
                    'amount' => $request->amount,
                    'payment_date' => $request->payment_date,
                    'payment_method' => $request->payment_method ?? 'cash',
                    'proof_image' => $newPath ?? null,
                    'notes' => $request->notes,
                    'created_by' => Auth::id(),
                ]);

                // 3. Update Status & Total Terbayar di Invoice Induk
                $newAmountPaid = $invoice->amount_paid + $request->amount;

                // Tentukan Status Baru
                // Gunakan floating point comparison (epsilon) untuk keamanan akurasi desimal
                $isPaidOff = ($newAmountPaid >= ($invoice->total_amount - 0.01));

                $invoice->update([
                    'amount_paid' => $newAmountPaid,
                    'payment_status' => $isPaidOff ? 'paid' : 'partial',
                    'paid_at' => $isPaidOff ? now() : null,
                ]);
            });
            return back()->with('success', 'Pembayaran berhasil dicatat.');
        } catch (\Exception $e) {
            return back()->withErrors('Terjadi kesalahan saat menyimpan pembayaran: ' . $e->getMessage());
        }
    }
}
