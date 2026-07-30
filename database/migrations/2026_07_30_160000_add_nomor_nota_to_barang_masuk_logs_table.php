<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barang_masuk_logs', function (Blueprint $table) {
            $table->string('nomor_nota')->nullable()->after('jumlah_masuk');
        });
    }

    public function down(): void
    {
        Schema::table('barang_masuk_logs', function (Blueprint $table) {
            $table->dropColumn('nomor_nota');
        });
    }
};
