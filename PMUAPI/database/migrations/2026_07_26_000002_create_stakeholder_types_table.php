<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stakeholder_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::table('stakeholders', function (Blueprint $table) {
            $table->foreignId('stakeholder_type_id')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('stakeholders', function (Blueprint $table) {
            $table->dropForeign(['stakeholder_type_id']);
            $table->dropColumn('stakeholder_type_id');
        });

        Schema::dropIfExists('stakeholder_types');
    }
};
