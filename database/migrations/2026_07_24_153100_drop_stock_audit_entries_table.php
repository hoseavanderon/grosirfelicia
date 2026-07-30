<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('stock_audit_entries');
    }

    public function down(): void
    {
        Schema::create('stock_audit_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('audit_date');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('check_state', 20);
            $table->unsignedInteger('pcs_value');
            $table->timestamps();
            $table->unique(['user_id', 'audit_date', 'product_id']);
        });
    }
};
