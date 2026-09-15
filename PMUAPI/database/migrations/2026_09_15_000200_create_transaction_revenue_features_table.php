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
        Schema::create('transaction_revenue_features', function (Blueprint $table) {
            $table->id();

            $table->date('report_date')->unique();

            $table->decimal('revenue_lag_1d', 16, 2)->nullable();
            $table->decimal('revenue_lag_7d', 16, 2)->nullable();
            $table->decimal('revenue_lag_365d', 16, 2)->nullable();

            $table->decimal('revenue_rolling_7d_mean', 16, 2)->nullable();
            $table->decimal('revenue_rolling_30d_mean', 16, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_revenue_features');
    }
};