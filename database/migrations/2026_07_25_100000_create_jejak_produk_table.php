<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jejak_produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('movement_type', 20);
            $table->integer('qty');
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nomor_nota');
            $table->integer('stock_after');
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['user_id', 'product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jejak_produk');
    }
};
