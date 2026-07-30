<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\JejakProdukService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JejakProdukController extends Controller
{
    public function __construct(
        private readonly JejakProdukService $jejakProdukService,
    ) {}

    public function index()
    {
        return view('pages.jejak-produk.index');
    }

    public function products(): JsonResponse
    {
        $products = Product::query()
            ->where('user_id', Auth::id())
            ->orderBy('sort_order')
            ->orderBy('nama_produk')
            ->get(['id', 'nama_produk'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->nama_produk,
            ])
            ->values();

        return response()->json([
            'products' => $products,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $userId = Auth::id();

        Product::query()
            ->where('user_id', $userId)
            ->findOrFail($validated['product_id']);

        $trail = $this->jejakProdukService->getTrail(
            $userId,
            (int) $validated['product_id'],
            $validated['from_date'],
            $validated['to_date'],
        );

        return response()->json($trail);
    }
}
