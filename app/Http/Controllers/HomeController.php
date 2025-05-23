<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Str;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use App\Models\DetailProduct;
use App\Models\DetailTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $detailProducts = DetailProduct::with('product')
            ->whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId)->whereNull('deleted_at');
            })
            ->get();

        $customers = Customer::all();

        $categoriesWithBrands = Product::select('products.category_id', 'products.brand_id', 'categories.category as category_name', 'brands.brand as brand_name')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->where('products.user_id', $userId)
            ->whereNull('products.deleted_at')
            ->whereNull('categories.deleted_at') // tambahkan ini
            ->whereNull('brands.deleted_at')     // dan ini
            ->distinct()
            ->get();

        return view('pages.home', compact('detailProducts', 'categoriesWithBrands', 'customers'));
    }

    public function filter(Request $request)
    {
        $userId = Auth::id();

        $query = DetailProduct::with('product')
            ->whereHas('product', function ($q) use ($userId, $request) {
                $q->where('user_id', $userId)->whereNull('deleted_at');

                if ($request->filled('catbrand') && $request->catbrand !== 'all') {
                    [$catId, $brandId] = explode('-', $request->catbrand);
                    $q->where('category_id', $catId)->where('brand_id', $brandId);
                }

                if ($request->filled('search')) {
                    $q->where('nama_produk', 'like', '%' . $request->search . '%');
                }
            });

        $detailProducts = $query->get();
        $customers = Customer::all();

        return view('partials.filter-product', compact('detailProducts', 'customers'));
    }

    public function checkStock(Request $request)
    {
        $productName = $request->name;
        $quantity = $request->quantity;

        $product = Product::where('nama_produk', $productName)
                  ->where('user_id', Auth::id())
                  ->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
        }

        if ($quantity > $product->stok) {
            return response()->json([
                'success' => false,
                'message' => "Stok tidak mencukupi. Stok tersedia: {$product->stok}"
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function search(Request $request)
    {
        $search = $request->get('q');

        $results = Customer::where('nama_pelanggan', 'like', "%{$search}%")
            ->select('user_id', 'nama_pelanggan')
            ->limit(10)
            ->get();

        return response()->json($results);
    }

   public function checkout(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'cart_items' => 'required|array|min:1',
        ]);

        $user = Auth::user();
        $customer = Customer::findOrFail($request->customer_id);

        foreach ($request->cart_items as $item) {
            $detailProduct = DetailProduct::with('product')->find($item['detail_product_id']);

            if (!$detailProduct || $detailProduct->stok < $item['qty']) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Stok tidak cukup untuk <strong>{$detailProduct?->product?->nama_produk}</strong> (Expired: <strong>" . \Carbon\Carbon::parse($detailProduct?->expired)->translatedFormat('d F Y') . "</strong>). Sisa stok: {$detailProduct?->stok}",
                ], 400);
            }
        }

        $tanggal = now()->format('Ymd');
        $countToday = Transaction::whereDate('created_at', today())->count();

        do {
            $number = str_pad($countToday + 1, 4, '0', STR_PAD_LEFT);
            $newNota = "TRX-{$tanggal}-{$number}";
            $countToday++;
        } while (Transaction::withTrashed()->where('nomor_nota', $newNota)->exists());

        DB::beginTransaction();

        try {
            $transaction = Transaction::create([
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'nomor_nota' => $newNota,
                'metode_pembayaran' => 'cash',
            ]);

            foreach ($request->cart_items as $item) {
                $detailProduct = DetailProduct::find($item['detail_product_id']);
                $detailProduct->stok -= $item['qty'];
                $detailProduct->save();

                DetailTransaction::create([
                    'transaction_id' => $transaction->id,
                    'detail_product_id' => $detailProduct->id,
                    'harga_jual' => $item['price'],
                    'pcs' => $item['qty'],
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Checkout berhasil!',
                'nomor_nota' => $transaction->nomor_nota,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat proses checkout.',
            ], 500);
        }
    }

}
