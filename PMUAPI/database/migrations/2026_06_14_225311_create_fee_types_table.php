<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fee_types', function (Blueprint $table) {

            $table->id();

            $table->string('fee_name');

            $table->decimal('base_rate', 12, 2);

            $table->string('unit')->nullable();

            $table->timestamps();
        });

        // ideal fee_name:
            // Fish Landing 
            // Fish Unloading
            // Wharfage
            // Parking
            // Storage
            // Rental
            // Accreditation
            // Auxiliary Invoice
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_types');
    }
};
