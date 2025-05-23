<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;  
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;

class PelangganController extends Controller
{
    public function index(){
        $customers = Customer::withCount('transactions')
            ->where('user_id', Auth::id()) 
            ->get(); 
            
        return view('pages.pelanggan', compact('customers'));
    }

    public function show(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $tanggalRange = $request->get('tanggal_transaksi');
        $transactionsQuery = $customer->transactions()->with([
            'detailTransactions.detailProduct.product',
            'customer'
        ]);

        if ($tanggalRange) {
            $dates = explode(' to ', str_replace(' sampai ', ' to ', $tanggalRange));
            if (count($dates) === 2) {
                $start = Carbon::parse($dates[0])->startOfDay();
                $end = Carbon::parse($dates[1])->endOfDay();

                $transactionsQuery->whereBetween('created_at', [$start, $end]);
            }
        }

        $transactions = $transactionsQuery->get();

        foreach ($transactions as $transaction) {
            $transaction->subtotal = $transaction->detailTransactions->sum(function ($detail) {
                return $detail->harga_jual * $detail->pcs;
            });
            $transaction->jumlah_item = $transaction->detailTransactions->sum('pcs');
        }

        $customer->setRelation('transactions', $transactions);

        return view('pages.detailpelanggan', compact('customer'));
    }

    public function ajaxDetail(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $tanggalRange = $request->get('tanggal_transaksi');
        $transactionsQuery = $customer->transactions()->with('detailTransactions');

        if ($tanggalRange) {
            $dates = explode(' to ', str_replace(' sampai ', ' to ', $tanggalRange));
            if (count($dates) === 2) {
                $start = Carbon::parse($dates[0])->startOfDay();
                $end = Carbon::parse($dates[1])->endOfDay();
                $transactionsQuery->whereBetween('created_at', [$start, $end]);
            }
        }

        $transactions = $transactionsQuery->get();

        foreach ($transactions as $transaction) {
            $transaction->subtotal = $transaction->detailTransactions->sum(fn($detail) => $detail->harga_jual * $detail->pcs);
            $transaction->jumlah_item = $transaction->detailTransactions->sum('pcs');
        }

        $customer->setRelation('transactions', $transactions);

        return response()->json([
            'html' => view('partials.detail-transaksi', compact('customer'))->render()
        ]);
    }

    public function kirimWhatsapp($id)
    {
        $record = \App\Models\Transaction::with(['customer', 'detailTransactions.detailProduct.product'])->findOrFail($id);

        $message = "Detail Nota :\n";
        $message .= "Nomor Nota: " . ($record->nomor_nota ?? 'N/A') . "\n";
        $message .= "Pelanggan: " . strtoupper($record->customer->nama_pelanggan ?? '-') . "\n";

        $totalPcs = $record->detailTransactions->sum('pcs');
        $message .= "Total Item: $totalPcs pcs\n";

        $message .= "========================\n";

        foreach ($record->detailTransactions as $item) {
            $productName = $item->detailProduct->product->nama_produk ?? 'Produk Tidak Diketahui';
            $expired = \Carbon\Carbon::parse($item->detailProduct->expired)->format('d/m');
            $pcs = $item->pcs;
            $price = number_format($item->harga_jual, 0, ',', '.');

            $message .= "$productName (Exp: $expired) - $pcs x Rp $price\n";
        }

        $message .= "========================\n";
        $message .= "*Total: Rp " . number_format(
            $record->detailTransactions->sum(fn ($i) => $i->pcs * $i->harga_jual),
            0,
            ',',
            '.'
        ) . "*\n";
        $message .= "\nTerima kasih atas pembelian Anda!";

        $phone = $record->customer->no_telp ?? '';
        $url = 'https://api.whatsapp.com/send?phone=' . $phone . '&text=' . urlencode($message);

        return Redirect::to($url);
    }

    public function hapusTransaksi($id)
    {
        $transaction = \App\Models\Transaction::with(['detailTransactions.detailProduct.product'])->findOrFail($id);

        foreach ($transaction->detailTransactions as $detail) {
            $detailProduct = \App\Models\DetailProduct::find($detail->detail_product_id);
            if ($detailProduct) {
                $detailProduct->increment('stok', $detail->pcs); 
            }
        }

        $transaction->detailTransactions()->delete();

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dihapus dan stok diperbarui.'
        ]);
    }
}
