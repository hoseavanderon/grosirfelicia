<?php

namespace App\Http\Controllers;

use App\Models\DetailProduct;
use App\Models\DetailTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RiwayatTransaksiController extends Controller
{
    public function index(Request $request)
    {
        \Carbon\Carbon::setLocale('id');
        $tanggalFilter = $request->input('tanggal_riwayat');

        if ($tanggalFilter) {
            $dates = explode(' to ', $tanggalFilter);

            if(count($dates) === 2){
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();
            } else {
                $startDate = Carbon::parse($tanggalFilter)->startOfDay();
                $endDate = Carbon::parse($tanggalFilter)->endOfDay();
            }
        } else {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        }

        $userId = Auth::id();

        $transactions = Transaction::with([
            'detailTransactions.detailProduct.product',
            'customer'
        ])
        ->where('user_id', $userId)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->orderBy('created_at', 'asc')
        ->get();

        $totalUangDiterima = $transactions->sum(function($transaction) {
            return $transaction->detailTransactions->sum(function($detail) {
                return $detail->harga_jual * $detail->pcs;
            });
        });

        return view('pages.riwayat', compact('transactions', 'tanggalFilter', 'startDate', 'endDate', 'totalUangDiterima'));
    }

    public function ajaxIndex(Request $request)
    {
        \Carbon\Carbon::setLocale('id');
        $tanggalRange = $request->tanggal_riwayat;

        // Proses parsing tanggal
        [$startDate, $endDate] = explode(' to ', str_replace(' to ', ' to ', $tanggalRange));
        $startDate = \Carbon\Carbon::parse($startDate)->startOfDay();
        $endDate = \Carbon\Carbon::parse($endDate)->endOfDay();

        $transactions = Transaction::with([
                'detailTransactions.detailProduct.product',
                'customer'
            ])
            ->where('user_id', Auth::id())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'asc')
            ->get();

        $totalUangDiterima = $transactions->sum(fn ($trx) =>
            $trx->detailTransactions->sum(fn ($d) => $d->harga_jual * $d->pcs)
        );

        return view('partials.ajax-filter-riwayat', compact('transactions', 'startDate', 'endDate', 'totalUangDiterima'));
    }

    public function edit(Transaction $transaction)
    {
        $detailProducts = DetailProduct::with('product')->where('stok', '>', 0)->get();

        return view('transactions.edit', [
            'transaction' => $transaction->load('detailTransactions.detailProduct.product', 'customer'),
            'detailProducts' => $detailProducts,
        ]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        DB::transaction(function () use ($request, $transaction) {
            if ($request->filled('deleted_ids')) {
                foreach ($request->deleted_ids as $deletedId) {
                    $detail = DetailTransaction::find($deletedId);
                    if ($detail) {
                        // Kembalikan stok
                        $detail->detailProduct->increment('stok', $detail->pcs);

                        // Hapus dari DB
                        $detail->delete();
                    }
                }
            }

            foreach ($request->items as $itemData) {
                if (isset($itemData['id'])) {
                    // 🟢 Update item lama
                    $detail = DetailTransaction::find($itemData['id']);

                    $oldPcs = $detail->pcs;
                    $newPcs = $itemData['pcs'];
                    $selisih = $newPcs - $oldPcs;

                    if ($selisih > 0) {
                        $detail->detailProduct->decrement('stok', $selisih);
                    } elseif ($selisih < 0) {
                        $detail->detailProduct->increment('stok', abs($selisih));
                    }

                    $detail->update([
                        'pcs' => $newPcs,
                        'harga_jual' => $itemData['harga_jual'],
                    ]);
                } else {
                    // 🆕 Tambah item baru
                    $detailProduct = DetailProduct::find($itemData['detail_product_id']);

                    // Kurangi stok
                    $detailProduct->decrement('stok', $itemData['pcs']);

                    // Simpan detail transaksi baru
                    $transaction->detailTransactions()->create([
                        'detail_product_id' => $itemData['detail_product_id'],
                        'pcs' => $itemData['pcs'],
                        'harga_jual' => $itemData['harga_jual'],
                    ]);
                }
            }
        });

        return redirect()->route('riwayat')->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            // Ambil transaksi
            $transaction = Transaction::with('detailTransactions')->findOrFail($id);

            // Kembalikan stok dari setiap detail transaksi
            foreach ($transaction->detailTransactions as $detail) {
                $detail->detailProduct->increment('stok', $detail->pcs);
            }

            // Hapus detail transaksi
            $transaction->detailTransactions()->delete();

            // Hapus transaksi utama
            $transaction->delete();

            DB::commit();

            return redirect()->route('riwayat')
                ->with('success', 'Transaksi berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('riwayat')
                ->with('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }
}
