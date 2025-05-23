<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('barang_masuk_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detail_product_id')->constrained()->onDelete('cascade');
            $table->integer('jumlah_masuk');
            $table->date('tanggal_masuk')->default(now());
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_masuk_logs');
    }
};
