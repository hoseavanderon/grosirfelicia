<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FormatsTransactions;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LanggananController extends Controller
{
    use FormatsTransactions;

    public function index()
    {
        $customers = Customer::query()
            ->where('user_id', Auth::id())
            ->orderBy('nama_pelanggan')
            ->get(['id', 'nama_pelanggan', 'no_telp']);

        return view('pages.langganan.index', compact('customers'));
    }

    public function show(int $id)
    {
        $customer = Customer::query()
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return view('pages.langganan.show', compact('customer'));
    }

    public function data(Request $request, int $id)
    {
        Customer::query()
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        [$start, $end] = $this->resolveDateRange($request);

        return response()->json([
            'stats' => $this->customerStats($id, $start, $end),
            'transactions' => $this->customerTransactions($id, $start, $end),
        ]);
    }

    private function resolveDateRange(Request $request): array
    {
        $from = $request->date('from') ?? now()->startOfYear();
        $to = $request->date('to') ?? today();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
    }

    private function customerStats(int $customerId, $start, $end): array
    {
        $userId = Auth::id();

        $detailBase = DB::table('detail_transactions')
            ->join('transactions', 'transactions.id', '=', 'detail_transactions.transaction_id')
            ->where('transactions.user_id', $userId)
            ->where('transactions.customer_id', $customerId)
            ->whereNull('transactions.deleted_at')
            ->whereNull('detail_transactions.deleted_at')
            ->whereBetween('transactions.created_at', [$start, $end]);

        $totalSpending = (clone $detailBase)
            ->selectRaw('COALESCE(SUM(detail_transactions.harga_jual * detail_transactions.pcs), 0) as total')
            ->value('total');

        $totalItems = (clone $detailBase)
            ->selectRaw('COALESCE(SUM(detail_transactions.pcs), 0) as total')
            ->value('total');

        $totalOrders = Transaction::query()
            ->where('user_id', $userId)
            ->where('customer_id', $customerId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $largestTransaction = DB::table('detail_transactions')
            ->join('transactions', 'transactions.id', '=', 'detail_transactions.transaction_id')
            ->where('transactions.user_id', $userId)
            ->where('transactions.customer_id', $customerId)
            ->whereNull('transactions.deleted_at')
            ->whereNull('detail_transactions.deleted_at')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->groupBy('detail_transactions.transaction_id')
            ->selectRaw('SUM(detail_transactions.harga_jual * detail_transactions.pcs) as subtotal')
            ->orderByDesc('subtotal')
            ->value('subtotal');

        return [
            'total_spending' => (int) $totalSpending,
            'total_orders' => $totalOrders,
            'total_items' => (int) $totalItems,
            'largest_transaction' => (int) ($largestTransaction ?? 0),
        ];
    }

    private function customerTransactions(int $customerId, $start, $end): array
    {
        return Transaction::query()
            ->with([
                'customer',
                'detailTransactions.detailProduct.product',
            ])
            ->where('user_id', Auth::id())
            ->where('customer_id', $customerId)
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Transaction $transaction) => $this->formatTransaction($transaction))
            ->values()
            ->all();
    }
}
