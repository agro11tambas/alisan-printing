<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\FreightPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

/**
 * Pembayaran ongkos angkut.
 *
 * Tagihannya tidak disimpan sebagai baris tersendiri — dia dihitung dari baris
 * Stock In yang freight-nya terisi, lalu dikelompokkan per NOMOR SURAT JALAN.
 * Satu surat jalan bisa memuat beberapa stock in dari beberapa purchase, jadi
 * di level itulah ongkos angkut ditagih dan dibayar.
 *
 * Yang disimpan cuma pembayarannya (tabel freight_payments) plus jurnal kas/bank
 * di account_transactions, persis seperti Mark as Paid (Freight) yang lama.
 */
class FreightPaymentController extends Controller
{
    public function index()
    {
        $transactionTypes = Account::where('name', 'Purchase')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();
        $defaultAccount = Account::where('is_default_purchase', true)->first();

        return view('erp.pages.purchases.freight-payments.freight-payments', compact(
            'transactionTypes',
            'cashAccounts',
            'bankAccounts',
            'defaultAccount'
        ));
    }

    /**
     * Tagihan freight per surat jalan.
     *
     * stock_in tersimpan dalam satuan dasar (pcs) sedangkan freight per satuan
     * BELI (mis. per Dus) — sama seperti kolom freight lama di Purchase List —
     * jadi qty-nya dibagi dulu dengan konversi unitnya sebelum dikali freight.
     */
    public function data(Request $request)
    {
        $rows = $this->billRows();

        // Filter tanggal memakai preset yang sama persis dengan Purchase List,
        // cuma acuannya tanggal stock in (change_date), bukan tanggal purchase.
        $this->applyDateFilter($rows, $request);

        if ($request->filled('search_keyword')) {
            $keyword = '%'.$request->input('search_keyword').'%';

            match ($request->input('search_type')) {
                'supplier' => $rows->where('s.name', 'like', $keyword),
                'invoice' => $rows->where('si.invoice_number', 'like', $keyword),
                default => $rows->where('si.waybill_number', 'like', $keyword),
            };
        }

        $bills = DB::query()
            ->fromSub($rows, 'r')
            ->groupBy('r.waybill_key')
            ->select('r.waybill_key')
            ->selectRaw('MAX(r.waybill_number) as waybill_number')
            ->selectRaw('MAX(r.supplier_id) as supplier_id')
            ->selectRaw('MAX(r.supplier_name) as supplier_name')
            ->selectRaw('MIN(r.change_date) as change_date')
            ->selectRaw('GROUP_CONCAT(DISTINCT r.invoice_number ORDER BY r.invoice_number SEPARATOR ", ") as invoice_numbers')
            ->selectRaw('SUM(r.freight_amount) as total_freight');

        $paid = DB::table('freight_payments')
            ->whereNull('deleted_at')
            ->groupBy('waybill_key')
            ->select('waybill_key')
            ->selectRaw('SUM(paid_amount) as paid_amount');

        $query = DB::query()
            ->fromSub($bills, 'b')
            ->leftJoinSub($paid, 'p', 'p.waybill_key', '=', 'b.waybill_key')
            ->select('b.*')
            ->selectRaw('COALESCE(p.paid_amount, 0) as paid_amount')
            ->selectRaw('b.total_freight - COALESCE(p.paid_amount, 0) as remaining_amount')
            ->orderByDesc('b.change_date');

        if ($request->input('search_type') === 'payment_status' && $request->filled('payment_status')) {
            match ($request->input('payment_status')) {
                'Paid' => $query->havingRaw('remaining_amount <= 0.5'),
                'Partially Paid' => $query->havingRaw('remaining_amount > 0.5 and paid_amount > 0'),
                default => $query->havingRaw('paid_amount <= 0'),
            };
        }

        return DataTables::query($query)
            ->addIndexColumn()
            ->editColumn('waybill_number', fn ($row) => $row->waybill_number ?: '<span class="text-muted">(tanpa no. surat jalan)</span>')
            ->editColumn('change_date', fn ($row) => $row->change_date ? date('d/m/Y', strtotime($row->change_date)) : '-')
            ->editColumn('total_freight', fn ($row) => 'Rp. '.number_format((float) $row->total_freight, 0, ',', '.'))
            ->editColumn('paid_amount', fn ($row) => 'Rp. '.number_format((float) $row->paid_amount, 0, ',', '.'))
            ->editColumn('remaining_amount', fn ($row) => 'Rp. '.number_format((float) $row->remaining_amount, 0, ',', '.'))
            ->addColumn('status', fn ($row) => $this->statusBadge($row))
            ->addColumn('action', fn ($row) => view('erp.pages.purchases.freight-payments.partials.action-button', [
                'bill' => $row,
            ])->render())
            ->rawColumns(['waybill_number', 'status', 'action'])
            ->toJson();
    }

    /**
     * Preset tanggal yang sama dengan Purchase List, supaya kebiasaan pemakainya
     * tidak berubah waktu pindah halaman.
     */
    private function applyDateFilter($query, Request $request): void
    {
        $column = 'si.change_date';

        switch ($request->input('filter')) {
            case 'today':
                $query->whereDate($column, Carbon::today());
                break;
            case 'last_7_days':
                $query->whereBetween($column, [Carbon::now()->subDays(7), Carbon::now()]);
                break;
            case 'this_month':
                $query->whereMonth($column, Carbon::now()->month)
                    ->whereYear($column, Carbon::now()->year);
                break;
            case 'last_30_days':
                $query->whereBetween($column, [Carbon::now()->subDays(30), Carbon::now()]);
                break;
            case 'year_to_date':
                $query->whereBetween($column, [Carbon::now()->startOfYear(), Carbon::now()]);
                break;
            case 'yearly':
                $query->whereYear($column, Carbon::now()->year);
                break;
            case 'custom':
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $query->whereBetween($column, [$request->start_date, $request->end_date]);
                }
                break;
        }
    }

    /**
     * Rincian tagihan + riwayat pembayaran satu surat jalan, dipakai modal.
     */
    public function detail(Request $request)
    {
        $waybillKey = (string) $request->input('waybill_key');

        abort_if($waybillKey === '', 404);

        $bill = $this->billFor($waybillKey);

        abort_if($bill === null, 404);

        $payments = FreightPayment::with(['cashBankAccount', 'user'])
            ->where('waybill_key', $waybillKey)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'transaction_date' => optional($payment->transaction_date)->format('d/m/Y'),
                'paid_amount' => 'Rp. '.number_format((float) $payment->paid_amount, 0, ',', '.'),
                'account' => $payment->cashBankAccount?->type ?? '-',
                'note' => $payment->note,
                'user' => $payment->user?->name ?? '-',
                'proof' => $payment->proof ? json_decode($payment->proof, true) : [],
            ]);

        return response()->json([
            'bill' => [
                'waybill_key' => $bill->waybill_key,
                'waybill_number' => $bill->waybill_number ?: '(tanpa no. surat jalan)',
                'supplier_name' => $bill->supplier_name,
                'invoice_numbers' => $bill->invoice_numbers,
                'total_freight' => (float) $bill->total_freight,
                'paid_amount' => (float) $bill->paid_amount,
                'remaining_amount' => (float) $bill->remaining_amount,
            ],
            'items' => $this->itemsFor($waybillKey),
            'payments' => $payments,
        ]);
    }

    /**
     * Halaman riwayat pembayaran, bentuknya sama dengan Payment History milik
     * Purchase List: satu kartu per pembayaran, isinya jurnal kas/bank-nya.
     */
    public function paymentHistory(Request $request)
    {
        $waybillKey = (string) $request->input('waybill_key');

        abort_if($waybillKey === '', 404);

        $bill = $this->billFor($waybillKey);

        abort_if($bill === null, 404);

        $payments = FreightPayment::with(['cashBankAccount', 'user'])
            ->where('waybill_key', $waybillKey)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        $transactions = AccountTransaction::with('account')
            ->whereIn('transaction_group_id', $payments->pluck('transaction_group_id')->filter())
            ->get()
            ->groupBy('transaction_group_id');

        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        return view('erp.pages.purchases.freight-payments.payment-history', [
            'bill' => $bill,
            'payments' => $payments,
            'transactions' => $transactions,
            'cashAccounts' => $cashAccounts,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    /**
     * Ubah satu pembayaran freight. Kontraknya sengaja disamakan dengan
     * updatePayment milik Purchase List — termasuk nominal 0 yang berarti
     * "hapus pembayaran ini" — supaya halaman riwayatnya bisa dipakai sama.
     */
    public function updatePayment(Request $request, $groupId)
    {
        $request->merge([
            'paid_amount' => str_replace('.', '', (string) $request->paid_amount),
        ]);

        $request->validate([
            'transaction_date' => 'required|date',
            'paid_amount' => 'required|numeric|min:0',
            'cash_bank_account_id' => 'required|exists:accounts,id',
            'note' => 'nullable|string',
            'payment_proof' => 'nullable|array',
            'payment_proof.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'note_per_image' => 'nullable|array',
        ]);

        $payment = FreightPayment::where('transaction_group_id', $groupId)->first();

        if ($payment === null) {
            return response()->json(['message' => 'Pembayaran freight tidak ditemukan.'], 404);
        }

        $newAmount = (float) $request->paid_amount;

        if ($newAmount > 0) {
            $bill = $this->billFor($payment->waybill_key);
            // Sisa tagihan dihitung tanpa pembayaran ini, karena nilainya mau diganti.
            $room = ($bill->remaining_amount ?? 0) + (float) $payment->paid_amount;

            if ($newAmount > $room + 0.5) {
                return response()->json([
                    'message' => 'Nominal melebihi sisa tagihan freight (Rp. '.number_format($room, 0, ',', '.').').',
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            $transactions = AccountTransaction::where('transaction_group_id', $groupId)->get();

            // Saldo akun lama dikembalikan dulu, baru dipotong lagi memakai nilai
            // dan akun yang baru — supaya pindah akun pun saldonya tetap benar.
            foreach ($transactions as $trx) {
                if ($trx->credit > 0 && $trx->account) {
                    $trx->account->increment('closing_balance', $trx->credit);
                }
            }

            if ($newAmount == 0) {
                foreach ($transactions as $trx) {
                    $trx->delete();
                }

                $payment->delete();

                DB::commit();

                return response()->json([
                    'status' => 'deleted',
                    'message' => 'Pembayaran freight berhasil dihapus.',
                    'group_id' => $groupId,
                ]);
            }

            $proofJson = $this->resolveProofs($request, $transactions->first()?->proof ?? $payment->proof);
            $account = Account::findOrFail($request->cash_bank_account_id);

            foreach ($transactions as $trx) {
                $trx->update([
                    'transaction_date' => $request->transaction_date,
                    'account_id' => $account->id,
                    'credit' => $newAmount,
                    'note' => $request->note ?: 'Freight Payment',
                    'proof' => $proofJson,
                    'verified' => false,
                ]);
            }

            $account->decrement('closing_balance', $newAmount);

            $payment->update([
                'paid_amount' => $newAmount,
                'transaction_date' => $request->transaction_date,
                'cash_bank_account_id' => $account->id,
                'note' => $request->note,
                'proof' => $proofJson,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pembayaran freight berhasil diperbarui.',
                'data' => [
                    'transaction_group_id' => $groupId,
                    'transaction_date' => Carbon::parse($request->transaction_date)->format('d-m-Y'),
                    'account_name' => $account->name,
                    'account_type' => $account->type,
                    'paid_amount' => number_format($newAmount, 0, ',', '.'),
                    'note' => $request->note,
                    'proofs' => $proofJson ? json_decode($proofJson, true) : [],
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal memperbarui pembayaran: '.$e->getMessage()], 500);
        }
    }

    /**
     * Bukti baru menimpa yang lama; kalau tidak ada unggahan baru, bukti lama
     * dipertahankan dan hanya catatannya yang diperbarui.
     */
    private function resolveProofs(Request $request, ?string $existingJson): ?string
    {
        $uploaded = $this->storeProofs($request);

        if ($uploaded !== null) {
            return $uploaded;
        }

        $old = $existingJson ? json_decode($existingJson, true) : [];

        if (! is_array($old) || $old === []) {
            return null;
        }

        $notes = $request->note_per_image ?? [];

        foreach ($old as $index => &$proof) {
            $proof['note'] = $notes[$index] ?? ($proof['note'] ?? '');
        }

        return json_encode($old);
    }

    /**
     * Rincian per produk di balik satu tagihan surat jalan: dari sini kelihatan
     * angka total freight-nya datang dari mana.
     */
    private function itemsFor(string $waybillKey): array
    {
        $expression = $this->waybillKeyExpression();

        return DB::table('inventory_stock_in_histories_2 as h')
            ->join('inventory_stock_ins_2 as si', 'si.id', '=', 'h.inventory_stock_in_id')
            ->join('inventory_items_2 as ii', 'ii.id', '=', 'h.inventory_item_id')
            ->join('inventories_2 as inv', 'inv.id', '=', 'si.inventory_id')
            ->leftJoin('purchases as pur', 'pur.id', '=', 'inv.purchase_id')
            ->leftJoin('products as pr', 'pr.id', '=', 'ii.product_id')
            ->whereNull('h.deleted_at')
            ->whereNull('si.deleted_at')
            ->whereNull('ii.deleted_at')
            ->where('h.stock_in', '>', 0)
            ->where('h.freight', '>', 0)
            ->whereRaw($expression.' = ?', [$waybillKey])
            ->orderBy('h.id')
            ->select([
                'h.stock_in',
                'h.freight',
                'si.invoice_number',
                'si.change_date',
                'ii.unit_name',
                'ii.unit_conversion_value',
                'pr.name as product_name',
                'pr.sku',
            ])
            ->get()
            ->map(function ($row) {
                $conversion = max(1, (float) ($row->unit_conversion_value ?: 1));
                $qtyUnit = (float) $row->stock_in / $conversion;

                return [
                    'product' => $row->product_name ?? '-',
                    'sku' => $row->sku ?? '-',
                    'invoice_number' => $row->invoice_number ?? '-',
                    'change_date' => $row->change_date ? date('d/m/Y', strtotime($row->change_date)) : '-',
                    'qty' => number_format($qtyUnit, 0, ',', '.').' '.($row->unit_name ?: 'Pcs'),
                    'qty_base' => number_format((float) $row->stock_in, 0, ',', '.').' Pcs',
                    'freight' => 'Rp. '.number_format((float) $row->freight, 0, ',', '.'),
                    'subtotal' => 'Rp. '.number_format($qtyUnit * (float) $row->freight, 0, ',', '.'),
                ];
            })
            ->all();
    }

    public function markAsPaid(Request $request)
    {
        $request->validate([
            'waybill_key' => 'required|string',
            'paid_amount' => 'required|numeric|min:1',
            'cash_bank_account_id' => 'required|exists:accounts,id',
            'transaction_type' => 'required|exists:accounts,id',
            'transaction_date' => 'required|date',
            'note' => 'nullable|string',
            'particular' => 'nullable|string',
            'payment_proof' => 'nullable|array',
            'payment_proof.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'note_per_image' => 'nullable|array',
        ]);

        $bill = $this->billFor($request->input('waybill_key'));

        if ($bill === null) {
            return response()->json(['message' => 'Tagihan freight untuk surat jalan ini tidak ditemukan.'], 404);
        }

        // Kelebihan bayar ditolak di sini supaya sisa tagihannya tidak pernah minus.
        if ((float) $request->paid_amount > (float) $bill->remaining_amount + 0.5) {
            return response()->json([
                'message' => 'Nominal melebihi sisa tagihan freight (Rp. '.number_format((float) $bill->remaining_amount, 0, ',', '.').').',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $groupId = (string) Str::uuid();
            $purchaseAccount = Account::findOrFail($request->transaction_type);
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

            $proofJson = $this->storeProofs($request);

            AccountTransaction::create([
                'transaction_date' => $request->transaction_date,
                'account_id' => $cashBankAccount->id,
                'debit' => 0,
                'credit' => $request->paid_amount,
                'note' => 'Freight Payment',
                'particular' => 'Freight Payment - '.($bill->supplier_name ?? '-').' / '
                    .($bill->waybill_number ?: 'Tanpa No. Surat Jalan'),
                'transaction_group_id' => $groupId,
                'proof' => $proofJson,
            ]);

            $cashBankAccount->decrement('closing_balance', $request->paid_amount);

            FreightPayment::create([
                'waybill_key' => $bill->waybill_key,
                'waybill_number' => $bill->waybill_number,
                'supplier_id' => $bill->supplier_id,
                'paid_amount' => $request->paid_amount,
                'transaction_date' => $request->transaction_date,
                'cash_bank_account_id' => $cashBankAccount->id,
                'purchase_account_id' => $purchaseAccount->id,
                'transaction_group_id' => $groupId,
                'proof' => $proofJson,
                'note' => $request->note,
                'particular' => $request->particular,
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            $updated = $this->billFor($bill->waybill_key);

            return response()->json([
                'message' => 'Pembayaran freight berhasil disimpan.',
                'bill' => [
                    'total_freight' => (float) $updated->total_freight,
                    'paid_amount' => (float) $updated->paid_amount,
                    'remaining_amount' => (float) $updated->remaining_amount,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['message' => 'Pembayaran gagal: '.$e->getMessage()], 500);
        }
    }

    /**
     * Hapus satu pembayaran: saldo kas/bank dikembalikan dan jurnalnya ikut
     * dihapus, supaya laporan keuangan tidak meninggalkan sisa transaksi.
     */
    public function destroyPayment($id)
    {
        $payment = FreightPayment::findOrFail($id);

        DB::beginTransaction();

        try {
            if ($payment->transaction_group_id) {
                AccountTransaction::where('transaction_group_id', $payment->transaction_group_id)->delete();
            }

            $account = $payment->cashBankAccount;

            if ($account) {
                $account->increment('closing_balance', (float) $payment->paid_amount);
            }

            $payment->delete();

            DB::commit();

            return response()->json(['message' => 'Pembayaran freight berhasil dihapus.']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal menghapus pembayaran: '.$e->getMessage()], 500);
        }
    }

    /**
     * Kunci pengelompokan tagihan: NOMOR SURAT JALAN + SUPPLIER.
     *
     * Satu surat jalan bisa memuat beberapa stock in dari beberapa invoice, dan
     * semuanya adalah satu tagihan ongkos angkut — jadi invoice sengaja tidak
     * ikut jadi pembeda. Supplier tetap ikut supaya nomor surat jalan yang
     * kebetulan sama di dua supplier tidak tergabung.
     *
     * Stock in yang nomor surat jalannya kosong dikelompokkan per supplier per
     * TANGGAL stock in ("D-<tanggal>"), bukan per stock in seperti sebelumnya:
     * satu kedatangan barang di hari yang sama tetap jadi satu tagihan walau
     * invoice-nya beda-beda.
     */
    private function waybillKeyExpression(): string
    {
        return "CONCAT('S', COALESCE(pur.supplier_id, 0), '|', ".
            "COALESCE(NULLIF(TRIM(si.waybill_number), ''), ".
            "CONCAT('D-', COALESCE(DATE(si.change_date), DATE(si.created_at), 'NA'))))";
    }

    /**
     * Baris stock in yang punya freight, satu baris per item, sudah membawa
     * kolom waybill_key.
     *
     * Kuncinya sengaja dihitung di sini lalu di-GROUP BY sebagai kolom oleh
     * pemanggil, bukan langsung GROUP BY ekspresi CONCAT-nya. MySQL di server
     * produksi (ONLY_FULL_GROUP_BY) menolak GROUP BY ekspresi mentah dengan
     * error 1055 "pur.supplier_id isn't in GROUP BY", padahal versi terbaru
     * di lokal menerimanya — jadi bug ini tidak pernah kelihatan waktu
     * dikembangkan.
     */
    private function billRows()
    {
        return DB::table('inventory_stock_in_histories_2 as h')
            ->join('inventory_stock_ins_2 as si', 'si.id', '=', 'h.inventory_stock_in_id')
            ->join('inventory_items_2 as ii', 'ii.id', '=', 'h.inventory_item_id')
            ->join('inventories_2 as inv', 'inv.id', '=', 'si.inventory_id')
            ->leftJoin('purchases as pur', 'pur.id', '=', 'inv.purchase_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'pur.supplier_id')
            ->whereNull('h.deleted_at')
            ->whereNull('si.deleted_at')
            ->whereNull('ii.deleted_at')
            ->where('h.stock_in', '>', 0)
            ->where('h.freight', '>', 0)
            ->selectRaw($this->waybillKeyExpression().' as waybill_key')
            ->addSelect([
                'si.waybill_number',
                'si.invoice_number',
                'si.change_date',
                'pur.supplier_id',
            ])
            ->selectRaw("COALESCE(s.name, '-') as supplier_name")
            ->selectRaw('h.stock_in / GREATEST(COALESCE(ii.unit_conversion_value, 1), 1) * h.freight as freight_amount');
    }

    private function billFor(string $waybillKey): ?object
    {
        $bill = DB::query()
            ->fromSub($this->billRows(), 'r')
            ->where('r.waybill_key', $waybillKey)
            ->groupBy('r.waybill_key')
            ->select('r.waybill_key')
            ->selectRaw('MAX(r.waybill_number) as waybill_number')
            ->selectRaw('MAX(r.supplier_id) as supplier_id')
            ->selectRaw('MAX(r.supplier_name) as supplier_name')
            ->selectRaw('GROUP_CONCAT(DISTINCT r.invoice_number ORDER BY r.invoice_number SEPARATOR ", ") as invoice_numbers')
            ->selectRaw('SUM(r.freight_amount) as total_freight')
            ->first();

        if ($bill === null) {
            return null;
        }

        $bill->paid_amount = (float) FreightPayment::where('waybill_key', $waybillKey)->sum('paid_amount');
        $bill->total_freight = (float) $bill->total_freight;
        $bill->remaining_amount = $bill->total_freight - $bill->paid_amount;

        return $bill;
    }

    private function statusBadge(object $row): string
    {
        $remaining = (float) $row->remaining_amount;
        $paid = (float) $row->paid_amount;

        if ($remaining <= 0.5) {
            return '<span class="badge bg-soft-success text-success">Paid</span>';
        }

        if ($paid > 0) {
            return '<span class="badge bg-soft-warning text-warning">Partially Paid</span>';
        }

        return '<span class="badge bg-soft-danger text-danger">Unpaid</span>';
    }

    private function storeProofs(Request $request): ?string
    {
        if (! $request->hasFile('payment_proof')) {
            return null;
        }

        $uploadPath = public_path('uploads/payment_proofs');

        if (! file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $notes = $request->note_per_image ?? [];
        $uploaded = [];

        foreach ($request->file('payment_proof') as $index => $file) {
            $fileName = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move($uploadPath, $fileName);

            $uploaded[] = [
                'file' => 'uploads/payment_proofs/'.$fileName,
                'note' => $notes[$index] ?? '',
            ];
        }

        return $uploaded === [] ? null : json_encode($uploaded);
    }
}
