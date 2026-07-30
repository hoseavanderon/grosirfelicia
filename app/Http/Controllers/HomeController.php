<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $categories = Product::query()
            ->select(
                'products.category_id',
                'products.brand_id',
                'categories.category',
                'brands.brand'
            )
            ->join(
                'categories',
                'categories.id',
                '=',
                'products.category_id'
            )
            ->join(
                'brands',
                'brands.id',
                '=',
                'products.brand_id'
            )
            ->where(
                'products.user_id',
                $userId
            )
            ->distinct()
            ->orderBy('categories.category')
            ->orderBy('brands.brand')
            ->get()
            ->map(function ($item) {
                return [
                    'category_id' => $item->category_id,
                    'brand_id' => $item->brand_id,
                    'label' => $item->category . ' ' . $item->brand,
                ];
            })
            ->values();

       $products = Product::query()
            ->with([
                'detailProducts' => function ($query) {
                    $query->where('stok', '>', 0)
                        ->orderBy('expired');
                },
                'category',
                'brand',
            ])
            ->withSum('detailProducts as total_stock_sum', 'stok')
            ->where('user_id', $userId)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($product) {

                return [

                    'product_id' => $product->id,

                    'category_id' => $product->category_id,

                    'brand_id' => $product->brand_id,

                    'sort_order' => (int) $product->sort_order,

                    'name' => $product->nama_produk,

                    'price' => $product->harga_jual,

                    'total_batch' => $product
                        ->detailProducts
                        ->count(),

                    'total_stock' => (int) ($product->total_stock_sum ?? $product->stok),

                    'details' => $product
                        ->detailProducts
                        ->map(function ($detail) {

                            return [

                                'id' => $detail->id,

                                'stock' => $detail->stok,

                                'exp' => optional(
                                    $detail->expired
                                )->format('d/m'),

                            ];

                        })
                        ->values(),

                ];

            })
            ->filter(
                fn($product) =>
                $product['details']->count()
            )
            ->values();

        return view(
            'pages.kasir.index',
            compact(
                'categories',
                'products'
            )
        );
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'products' => ['required', 'array', 'min:1'],
            'products.*' => ['integer', 'exists:products,id'],
            'category_id' => ['required', 'integer'],
            'brand_id' => ['required', 'integer'],
        ]);

        $userId = Auth::id();

        $baseOrder = Product::query()
            ->where('user_id', $userId)
            ->where('category_id', $validated['category_id'])
            ->where('brand_id', $validated['brand_id'])
            ->min('sort_order') ?? 1;

        foreach ($validated['products'] as $index => $productId) {
            Product::query()
                ->where('user_id', $userId)
                ->where('id', $productId)
                ->where('category_id', $validated['category_id'])
                ->where('brand_id', $validated['brand_id'])
                ->update([
                    'sort_order' => $baseOrder + $index,
                ]);
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
