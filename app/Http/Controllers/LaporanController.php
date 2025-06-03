<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;

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

        $start = $range[0];
        $end = $range[1];

        $totalTransaksi = \App\Models\Transaction::whereBetween('created_at', [$start, $end])->count();

        $penjualan = \App\Models\DetailTransaction::whereHas('transaction', function ($query) use ($start, $end) {
            $query->whereBetween('created_at', [$start, $end]);
        })->selectRaw('SUM(harga_jual * pcs) as total')->value('total');

        $jumlahTokoOrder = \App\Models\Transaction::whereBetween('created_at', [$start, $end])
            ->distinct('customer_id')
            ->count('customer_id');

        $jumlahBarangTerjual = \App\Models\DetailTransaction::whereHas('transaction', function ($query) use ($start, $end) {
            $query->whereBetween('created_at', [$start, $end]);
        })->sum('pcs');

        return response()->json([
            'totalTransaksi' => $totalTransaksi,
            'penjualan' => $penjualan ?? 0,
            'jumlahTokoOrder' => $jumlahTokoOrder,
            'jumlahBarangTerjual' => $jumlahBarangTerjual,
        ]);
    }
}