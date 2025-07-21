<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function index()
    {
        return view('pages.laporan');
    }

    public function data(Request $request)
    {
        $tanggal = $request->input('tanggal');

        if (!$tanggal) {
            return response()->json(['error' => 'Tanggal wajib diisi'], 422);
        }

        $userId = Auth::id();

        if (str_contains($tanggal, ' to ')) {
            $parts = explode(' to ', $tanggal);
            $start = \Carbon\Carbon::parse($parts[0])->startOfDay();
            $end = \Carbon\Carbon::parse($parts[1])->endOfDay();
        } else {
            $start = \Carbon\Carbon::parse($tanggal)->startOfDay();
            $end = \Carbon\Carbon::parse($tanggal)->endOfDay();
        }

        // Filter transaksi milik user
        $totalTransaksi = \App\Models\Transaction::whereBetween('created_at', [$start, $end])
            ->where('user_id', $userId)
            ->count();

        // Total penjualan
        $penjualan = \App\Models\DetailTransaction::whereHas('transaction', function ($query) use ($start, $end, $userId) {
            $query->whereBetween('created_at', [$start, $end])
                ->where('user_id', $userId);
        })->selectRaw('SUM(harga_jual * pcs) as total')->value('total');

        // Jumlah toko yang order (berdasarkan customer unik)
        $jumlahTokoOrder = \App\Models\Transaction::whereBetween('created_at', [$start, $end])
            ->where('user_id', $userId)
            ->distinct('customer_id')
            ->count('customer_id');

        // Jumlah barang terjual
        $jumlahBarangTerjual = \App\Models\DetailTransaction::whereHas('transaction', function ($query) use ($start, $end, $userId) {
            $query->whereBetween('created_at', [$start, $end])
                ->where('user_id', $userId);
        })->sum('pcs');

        return response()->json([
            'totalTransaksi' => $totalTransaksi,
            'penjualan' => $penjualan ?? 0,
            'jumlahTokoOrder' => $jumlahTokoOrder,
            'jumlahBarangTerjual' => $jumlahBarangTerjual,
        ]);
    }

   public function chartData(Request $request)
    {
        $tanggal = $request->input('tanggal');
        $type = $request->input('type', 'totalPenjualan'); // default ke total penjualan
        $userId = Auth::id();

        if (!$tanggal) {
            return response()->json(['error' => 'Tanggal wajib diisi'], 422);
        }

        // Parsing range tanggal
        if (str_contains($tanggal, ' to ')) {
            $parts = explode(' to ', $tanggal);
            $start = Carbon::parse($parts[0])->startOfDay();
            $end = Carbon::parse($parts[1])->endOfDay();
        } else {
            $start = Carbon::parse($tanggal)->startOfDay();
            $end = Carbon::parse($tanggal)->endOfDay();
        }

        switch ($type) {
            case 'jumlahBarang':
                $data = DB::table('transactions')
                    ->join('detail_transactions', 'transactions.id', '=', 'detail_transactions.transaction_id')
                    ->selectRaw('DATE(transactions.created_at) as tanggal')
                    ->selectRaw('SUM(detail_transactions.pcs) as total_barang')
                    ->whereBetween('transactions.created_at', [$start, $end])
                    ->where('transactions.user_id', $userId)
                    ->whereNull('transactions.deleted_at')
                    ->groupByRaw('DATE(transactions.created_at)')
                    ->orderBy('tanggal')
                    ->get();
                break;

            case 'jumlahTransaksi':
                $data = DB::table('transactions')
                    ->selectRaw('DATE(created_at) as tanggal')
                    ->selectRaw('COUNT(id) as total_transaksi')
                    ->whereBetween('created_at', [$start, $end])
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at')
                    ->groupByRaw('DATE(created_at)')
                    ->orderBy('tanggal')
                    ->get();
                break;

           case 'penjualanKategori':
                $data = DB::table('transactions')
                    ->join('detail_transactions', 'transactions.id', '=', 'detail_transactions.transaction_id')
                    ->join('detail_products', 'detail_transactions.detail_product_id', '=', 'detail_products.id')
                    ->join('products', 'detail_products.product_id', '=', 'products.id')
                    ->join('categories', 'products.category_id', '=', 'categories.id')
                    ->selectRaw('DATE(transactions.created_at) as tanggal')
                    ->selectRaw('categories.category as kategori')
                    ->selectRaw('SUM(detail_transactions.harga_jual * detail_transactions.pcs) as total_penjualan')
                    ->selectRaw('SUM(detail_transactions.pcs) as total_terjual')
                    ->whereBetween('transactions.created_at', [$start, $end])
                    ->where('transactions.user_id', $userId)
                    ->whereNull('transactions.deleted_at')
                    ->groupByRaw('DATE(transactions.created_at), categories.category')
                    ->orderBy('tanggal')
                    ->get();
                break;

            case 'totalPenjualan':
            default:
                $data = DB::table('transactions')
                    ->join('detail_transactions', 'transactions.id', '=', 'detail_transactions.transaction_id')
                    ->selectRaw('DATE(transactions.created_at) as tanggal')
                    ->selectRaw('SUM(detail_transactions.harga_jual * detail_transactions.pcs) as penjualan')
                    ->selectRaw('COUNT(DISTINCT transactions.id) as total_transaksi')
                    ->selectRaw('SUM(detail_transactions.pcs) as total_barang')
                    ->whereBetween('transactions.created_at', [$start, $end])
                    ->where('transactions.user_id', $userId)
                    ->whereNull('transactions.deleted_at')
                    ->groupByRaw('DATE(transactions.created_at)')
                    ->orderBy('tanggal', 'asc')
                    ->get();
                break;
        }

        return response()->json($data);
    }
}