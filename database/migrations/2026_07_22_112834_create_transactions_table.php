<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('amount');
            $table->timestamp('transaction_date');

            $table->index('user_id');
            $table->index('transaction_date');

            $table->unique(['user_id', 'transaction_date', 'amount'], 'idx_unique_transaction');
            DB::statement("CREATE INDEX idx_transactions_month ON transactions (TO_CHAR(transaction_date, 'YYYY-MM'))");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
