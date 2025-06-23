<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

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

        $range = explode(' to ', $tanggal);

        $start = $range[0] . ' 00:00:00';
        $end = $range[1] . ' 23:59:59';

        $userId = Auth::id();

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

}