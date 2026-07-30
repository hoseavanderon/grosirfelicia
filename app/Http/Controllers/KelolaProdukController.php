<?php

namespace App\Http\Controllers;

use App\Services\ProdukAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class KelolaProdukController extends Controller
{
    public function __construct(
        private readonly ProdukAnalyticsService $analytics,
    ) {}

    public function index()
    {
        return view('pages.produk.index');
    }

    public function bestSellers(): JsonResponse
    {
        return response()->json([
            'items' => $this->analytics->bestSellers(Auth::id()),
        ]);
    }

    public function expiringSoon(): JsonResponse
    {
        return response()->json([
            'items' => $this->analytics->expiringSoon(Auth::id()),
        ]);
    }

    public function criticalStock(): JsonResponse
    {
        return response()->json([
            'items' => $this->analytics->criticalStock(Auth::id()),
        ]);
    }
}
