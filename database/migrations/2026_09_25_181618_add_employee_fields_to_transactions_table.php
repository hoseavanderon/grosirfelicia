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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('created_by_employee_id')
                ->nullable()
                ->after('user_id')
                ->constrained('employees')
                ->nullOnDelete();

            $table->foreignId('delivered_by_employee_id')
                ->nullable()
                ->after('created_by_employee_id')
                ->constrained('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['created_by_employee_id']);
            $table->dropForeign(['delivered_by_employee_id']);

            $table->dropColumn([
                'created_by_employee_id',
                'delivered_by_employee_id',
            ]);
        });
    }
};
