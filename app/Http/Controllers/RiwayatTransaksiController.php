<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FormatsTransactions;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiwayatTransaksiController extends Controller
{
    use FormatsTransactions;

    public function index()
    {
        return view('pages.transaksi.index');
    }

    public function list(Request $request)
    {
        $query = Transaction::query()
            ->with([
                'customer',
                'detailTransactions.detailProduct.product',
            ])
            ->where('user_id', Auth::id());

        if ($request->filled('from') && $request->filled('to')) {
            $from = $request->date('from');
            $to = $request->date('to');

            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }

            $query
                ->whereDate('created_at', '>=', $from)
                ->whereDate('created_at', '<=', $to);
        } else {
            $query->whereDate('created_at', now()->toDateString());
        }

        $transactions = $query
            ->orderBy('created_at')
            ->get()
            ->map(fn (Transaction $transaction) => $this->formatTransaction($transaction))
            ->values();

        return response()->json([
            'transactions' => $transactions,
            'total_amount' => $transactions->sum('amount'),
            'transaction_count' => $transactions->count(),
        ]);
    }

    public function updatePayment(Request $request, int $id)
    {
        $validated = $request->validate([
            'metode_pembayaran' => ['required', 'in:cash,tf'],
        ]);

        $transaction = Transaction::query()
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        if ($transaction->metode_pembayaran === 'setor_bos') {
            return response()->json([
                'message' => 'Transaksi yang sudah disetor tidak dapat diubah.',
            ], 422);
        }

        $transaction->update([
            'metode_pembayaran' => $validated['metode_pembayaran'],
        ]);

        return response()->json([
            'success' => true,
            'transaction' => $this->formatPaymentPayload($transaction),
        ]);
    }

    public function depositByDate(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $date = $request->date('date');

        $transactionIds = Transaction::query()
            ->where('user_id', Auth::id())
            ->whereDate('created_at', $date)
            ->whereIn('metode_pembayaran', ['cash', 'tf'])
            ->pluck('id');

        if ($transactionIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'updated_count' => 0,
                'transactions' => [],
            ]);
        }

        Transaction::query()
            ->whereIn('id', $transactionIds)
            ->update(['metode_pembayaran' => 'setor_bos']);

        $updated = Transaction::query()
            ->whereIn('id', $transactionIds)
            ->get();

        return response()->json([
            'success' => true,
            'updated_count' => $updated->count(),
            'transactions' => $updated
                ->map(fn (Transaction $transaction) => [
                    'id' => $transaction->id,
                    ...$this->formatPaymentPayload($transaction),
                ])
                ->values(),
        ]);
    }
}
