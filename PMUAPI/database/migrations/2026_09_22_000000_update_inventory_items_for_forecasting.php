<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert any existing 'low_stock' status to 'available'.
        // Low stock is now calculated from quantity <= minimum_stock.
        DB::table('inventory_items')
            ->where('status', 'low_stock')
            ->update(['status' => 'available']);

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->integer('minimum_stock')
                ->default(0)
                ->after('unit');

            $table->integer('reorder_quantity')
                ->default(0)
                ->after('minimum_stock');

            $table->decimal('average_daily_usage', 12, 2)
                ->default(0)
                ->after('reorder_quantity');

            $table->enum('status', [
                'available',
                'inactive',
                'damaged',
            ])->default('available')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['minimum_stock', 'reorder_quantity', 'average_daily_usage']);
        });

        // Restore the original enum. Any 'inactive' values are mapped back to
        // 'available' since the original schema did not include that value.
        DB::table('inventory_items')
            ->where('status', 'inactive')
            ->update(['status' => 'available']);

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->enum('status', [
                'available',
                'low_stock',
                'damaged',
            ])->default('available')->change();
        });
    }
};