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
        Schema::table('stakeholders', function (Blueprint $table) {
            $table->string('official_receipt', 7)->unique()->nullable()->after('name');
            $table->dropColumn(['type', 'contact_no', 'email', 'address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stakeholders', function (Blueprint $table) {
            $table->dropColumn('official_receipt');
            $table->enum('type', ['buyer', 'broker', 'renter'])->after('name');
            $table->string('contact_no', 30)->nullable()->after('type');
            $table->string('email')->nullable()->after('contact_no');
            $table->text('address')->nullable()->after('email');
        });
    }
};
