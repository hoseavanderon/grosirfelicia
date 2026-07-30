<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('barang_masuk_logs')) {
            return;
        }

        Schema::create('barang_masuk_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detail_product_id')->constrained('detail_products')->cascadeOnDelete();
            $table->unsignedInteger('jumlah_masuk');
            $table->date('tanggal_masuk');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_masuk_logs');
    }
};
