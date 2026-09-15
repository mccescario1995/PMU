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
        Schema::create('transaction_revenue', function (Blueprint $table) {
            $table->id();

            $table->date('report_date')->unique();

            $table->decimal('revenue_target', 16, 2);
            $table->decimal('log_revenue', 16, 6)->nullable();

            $table->decimal('temp_celsius', 8, 2)->nullable();
            $table->decimal('precipitation_mm', 8, 2)->nullable();
            $table->decimal('wind_speed', 8, 2)->nullable();

            $table->integer('year_num');
            $table->integer('month_num');
            $table->integer('day_num');
            $table->integer('day_of_week');
            $table->integer('quarter_num');

            $table->boolean('is_weekend')->default(false);
            $table->boolean('is_month_start')->default(false);
            $table->boolean('is_month_end')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_revenue');
    }
};