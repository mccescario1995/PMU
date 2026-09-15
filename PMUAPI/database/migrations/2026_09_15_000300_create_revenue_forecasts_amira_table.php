<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_forecasts_amira', function (Blueprint $table) {
            $table->id();
            $table->date('forecast_date')->unique();
            $table->decimal('predicted_revenue', 12, 2);
            $table->string('season')->nullable();
            $table->string('model_version')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_forecasts_amira');
    }
};