<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE transactions MODIFY metode_pembayaran ENUM('cash', 'tf', 'belum_bayar', 'setor_bos') NOT NULL DEFAULT 'belum_bayar'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE transactions MODIFY metode_pembayaran ENUM('cash', 'tf', 'belum_bayar') NOT NULL DEFAULT 'belum_bayar'"
        );
    }
};
