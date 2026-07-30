<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\StockAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CekStokController extends Controller
{
    public function __construct(
        private readonly StockAuditService $stockAuditService,
    ) {}

    public function index()
    {
        return view('pages.stok.index');
    }

    public function data()
    {
        $payload = $this->stockAuditService->loadAuditData(Auth::id());

        return response()->json($payload);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.check_state' => ['required', 'in:system,manual,unchecked'],
            'items.*.pcs' => ['required', 'integer', 'min:0'],
        ]);

        $userId = Auth::id();

        foreach ($validated['items'] as $item) {
            $ownsProduct = Product::query()
                ->where('user_id', $userId)
                ->where('id', $item['product_id'])
                ->exists();

            if (! $ownsProduct) {
                return response()->json([
                    'message' => 'Produk tidak valid.',
                ], 422);
            }
        }

        $this->stockAuditService->saveProgress($userId, $validated['items']);

        return response()->json([
            'success' => true,
        ]);
    }
}
