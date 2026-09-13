<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_features', function (Blueprint $table) {
            $table->id();

            $table->date('report_date');

            $table->integer('year_num');
            $table->integer('month_num');
            $table->integer('day_num');
            $table->integer('day_of_week');
            $table->integer('quarter_num');

            $table->boolean('is_weekend')->default(false);
            $table->boolean('is_month_start')->default(false);
            $table->boolean('is_month_end')->default(false);

            $table->decimal('revenue_target', 16, 2);
            $table->decimal('log_revenue', 16, 6)->nullable();
            $table->decimal('revenue_lag_1d', 16, 2)->nullable();
            $table->decimal('revenue_lag_7d', 16, 2)->nullable();
            $table->decimal('revenue_lag_365d', 16, 2)->nullable();
            $table->decimal('revenue_rolling_7d_mean', 16, 2)->nullable();
            $table->decimal('revenue_rolling_30d_mean', 16, 2)->nullable();
            $table->decimal('summary_metric_col17', 16, 2)->nullable();

            $table->boolean('is_missing_date')->default(false);

            $table->timestamps();

            $table->unique('report_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_features');
    }
};
