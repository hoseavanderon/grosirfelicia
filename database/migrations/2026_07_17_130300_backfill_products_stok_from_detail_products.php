<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $totals = DB::table('detail_products')
            ->select('product_id', DB::raw('COALESCE(SUM(stok), 0) as total'))
            ->whereNull('deleted_at')
            ->groupBy('product_id')
            ->get();

        foreach ($totals as $row) {
            DB::table('products')
                ->where('id', $row->product_id)
                ->update(['stok' => (int) $row->total]);
        }
    }

    public function down(): void
    {
        DB::table('products')->update(['stok' => 0]);
    }
};
