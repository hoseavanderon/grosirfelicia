<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('detail_barang_masuk');
        Schema::dropIfExists('barang_masuk');
    }

    public function down(): void
    {
        // Legacy tables were replaced by barang_masuk_logs.
    }
};
