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
        Schema::create('weather_data', function (Blueprint $table) {

            $table->id();

            $table->date('weather_date');

            $table->decimal('rainfall_mm', 8, 2)
                ->nullable();

            $table->decimal('wind_speed', 8, 2)
                ->nullable();

            $table->decimal('temperature', 8, 2)
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_data');
    }
};
