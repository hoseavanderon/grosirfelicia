<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE transactions
            MODIFY metode_pembayaran ENUM(
                'belum_bayar',
                'cash',
                'tf',
                'setor_bos'
            ) NOT NULL DEFAULT 'belum_bayar'
        ");
    }

    public function down(): void
    {
        DB::table('transactions')
            ->where('metode_pembayaran', 'setor_bos')
            ->update([
                'metode_pembayaran' => 'cash',
            ]);

        DB::statement("
            ALTER TABLE transactions
            MODIFY metode_pembayaran ENUM(
                'belum_bayar',
                'cash',
                'tf'
            ) NOT NULL DEFAULT 'belum_bayar'
        ");
    }
};
