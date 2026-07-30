<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomingGoodsRequest;
use App\Models\BarangMasukLog;
use App\Models\Product;
use App\Services\IncomingGoodsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class BarangMasukController extends Controller
{
    private const DRAFT_KEY = 'incoming_goods_draft';

    public function __construct(
        private readonly IncomingGoodsService $incomingGoodsService,
    ) {}

    public function index()
    {
        return view('pages.barang-masuk.index', [
            'draft' => Session::get(self::draftKey(), []),
        ]);
    }

    public function history()
    {
        return view('pages.barang-masuk.history');
    }

    public function products(): JsonResponse
    {
        $products = Product::query()
            ->where('user_id', Auth::id())
            ->orderBy('sort_order')
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

    public function saveDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rows' => ['present', 'array'],
            'rows.*.uid' => ['required', 'string'],
            'rows.*.product_id' => ['nullable', 'integer'],
            'rows.*.product_name' => ['nullable', 'string'],
            'rows.*.expired' => ['nullable', 'date'],
            'rows.*.quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        Session::put(self::draftKey(), $validated['rows']);

        return response()->json([
            'success' => true,
        ]);
    }

    public function discardDraft(): JsonResponse
    {
        Session::forget(self::draftKey());

        return response()->json([
            'success' => true,
        ]);
    }

    public function store(StoreIncomingGoodsRequest $request): JsonResponse
    {
        $rows = collect($request->validated('rows'))
            ->map(fn (array $row) => [
                'product_id' => (int) $row['product_id'],
                'expired' => $row['expired'],
                'quantity' => (int) $row['quantity'],
            ])
            ->all();

        $this->incomingGoodsService->process(Auth::id(), $rows);
        Session::forget(self::draftKey());

        return response()->json([
            'success' => true,
            'redirect' => route('barang.masuk.history'),
        ]);
    }

    public function historyList(): JsonResponse
    {
        $records = BarangMasukLog::query()
            ->where('user_id', Auth::id())
            ->select([
                DB::raw('DATE(tanggal_masuk) as date_key'),
                DB::raw('COUNT(*) as item_count'),
                DB::raw('MAX(created_at) as last_created_at'),
            ])
            ->groupBy(DB::raw('DATE(tanggal_masuk)'))
            ->orderByDesc('date_key')
            ->get()
            ->map(function ($record) {
                $date = \Illuminate\Support\Carbon::parse($record->date_key);

                return [
                    'date_key' => $record->date_key,
                    'date_label' => $date->translatedFormat('d F Y'),
                    'item_count' => (int) $record->item_count,
                    'datetime_label' => $date->format('d/m/Y'),
                ];
            })
            ->values();

        return response()->json([
            'records' => $records,
        ]);
    }

    public function showByDate(string $date): JsonResponse
    {
        $parsedDate = \Illuminate\Support\Carbon::parse($date)->toDateString();

        $logs = BarangMasukLog::query()
            ->where('user_id', Auth::id())
            ->whereDate('tanggal_masuk', $parsedDate)
            ->with([
                'detailProduct.product',
            ])
            ->orderByDesc('created_at')
            ->get();

        abort_if($logs->isEmpty(), 404);

        return response()->json([
            'record' => $this->formatHistoryRecord($parsedDate, $logs),
        ]);
    }

    public function destroyLog(int $id): JsonResponse
    {
        $result = $this->incomingGoodsService->deleteLog(Auth::id(), $id);

        if ($result['remaining_count'] === 0) {
            return response()->json([
                'success' => true,
                'deleted_date' => true,
                'date_key' => $result['date_key'],
            ]);
        }

        $logs = BarangMasukLog::query()
            ->where('user_id', Auth::id())
            ->whereDate('tanggal_masuk', $result['date_key'])
            ->with([
                'detailProduct.product',
            ])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'deleted_date' => false,
            'record' => $this->formatHistoryRecord($result['date_key'], $logs),
        ]);
    }

    private function formatHistoryRecord(string $dateKey, $logs): array
    {
        $date = \Illuminate\Support\Carbon::parse($dateKey);

        return [
            'date_key' => $dateKey,
            'datetime_label' => $date->format('d/m/Y'),
            'items' => $logs->map(fn (BarangMasukLog $log) => [
                'id' => $log->id,
                'product_name' => $log->detailProduct?->product?->nama_produk ?? 'Produk',
                'expired_label' => $this->incomingGoodsService->formatExpired(
                    $log->detailProduct?->expired,
                ),
                'quantity' => (int) $log->jumlah_masuk,
            ])->values(),
        ];
    }

    private static function draftKey(): string
    {
        return self::DRAFT_KEY . '_' . Auth::id();
    }
}
