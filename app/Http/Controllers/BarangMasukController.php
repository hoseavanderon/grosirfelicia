<?php

namespace App\Http\Controllers;

use App\Models\DetailProduct;
use App\Models\Product;
use App\Models\BarangMasukLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangMasukController extends Controller
{
    public function index()
    {
        $products = Product::where('user_id', Auth::id())->get();

        return view('barang-masuk', compact('products'));
    }

    public function tambahKeProdukLama(Request $request)
    {
        $request->validate([
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.expired' => 'required|date',
            'items.*.pcs' => 'required|integer|min:1',
        ]);

        foreach ($request->items as $item) {
            // Cari detail produk dengan product_id dan expired yang sama
            $detail = DetailProduct::where('product_id', $item['product_id'])
                ->whereDate('expired', $item['expired'])
                ->first();

            if ($detail) {
                // Kalau ada, update stok
                $detail->stok += $item['pcs'];
                $detail->save();
            } else {
                // Kalau belum ada, buat baru
                $detail = DetailProduct::create([
                    'product_id' => $item['product_id'],
                    'expired' => $item['expired'],
                    'stok' => $item['pcs'],
                ]);
            }

            // Catat riwayat barang masuk
            BarangMasukLog::create([
                'detail_product_id' => $detail->id,
                'jumlah_masuk' => $item['pcs'],
                'tanggal_masuk' => now(),
            ]);
        }

        return response()->json(['message' => 'Stok berhasil ditambahkan.']);
    }

    public function riwayat()
    {
        $tanggalUnik = BarangMasukLog::select('tanggal_masuk')
            ->distinct()
            ->orderByDesc('tanggal_masuk')
            ->get();

        return view('barang-masuk-riwayat', compact('tanggalUnik'));
    }

    public function getRiwayatByTanggal($tanggal)
    {
        $logs = BarangMasukLog::with('detailProduct.product')
            ->whereDate('tanggal_masuk', $tanggal)
            ->get();

        return response()->json($logs);
    }

   public function destroyRiwayat($id)
    {
        $riwayat = BarangMasukLog::findOrFail($id);

        $detailProduct = DetailProduct::findOrFail($riwayat->detail_product_id);

        $hasil = $detailProduct->stok - $riwayat->jumlah_masuk;

        if ($detailProduct) {
            $detailProduct->stok = $hasil;
            $detailProduct->save();
        }

        $riwayat->delete();

        return response()->json(['success' => true]);
    }

}