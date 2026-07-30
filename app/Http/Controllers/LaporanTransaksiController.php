<?php

namespace App\Http\Controllers;

use App\Services\TransactionReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LaporanTransaksiController extends Controller
{
    public function __construct(
        private readonly TransactionReportService $transactionReportService,
    ) {}

    public function index()
    {
        return view('pages.laporan-transaksi.index');
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date'],
        ]);

        $report = $this->transactionReportService->getReport(
            Auth::id(),
            $validated['from_date'],
            $validated['to_date'],
        );

        return response()->json($report);
    }
}
