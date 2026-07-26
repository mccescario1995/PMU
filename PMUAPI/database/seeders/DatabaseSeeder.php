<?php

namespace Database\Seeders;

use App\Models\FeeType;
use App\Models\InventoryItem;
use App\Models\Stakeholder;
use App\Models\User;
use Database\Seeders\StakeholderTypeSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $portManagerRole = Role::create(['name' => 'Port Manager', 'guard_name' => 'web']);
        $statisticianRole = Role::create(['name' => 'Statistician', 'guard_name' => 'web']);

        Permission::create(['name' => 'manage users', 'guard_name' => 'web'])->syncRoles([$portManagerRole]);
        Permission::create(['name' => 'manage roles', 'guard_name' => 'web'])->syncRoles([$portManagerRole]);
        Permission::create(['name' => 'manage stakeholders', 'guard_name' => 'web'])->syncRoles([$statisticianRole]);
        Permission::create(['name' => 'manage transactions', 'guard_name' => 'web'])->syncRoles([$statisticianRole]);
        Permission::create(['name' => 'manage inventory', 'guard_name' => 'web'])->syncRoles([$portManagerRole, $statisticianRole]);
        Permission::create(['name' => 'view reports', 'guard_name' => 'web'])->syncRoles([$portManagerRole, $statisticianRole]);
        Permission::create(['name' => 'manage settings', 'guard_name' => 'web'])->syncRoles([$portManagerRole]);

        $user = User::create([
            'name' => 'Port Manager',
            'email' => 'admin@pmu.gov.ph',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $user->assignRole('Port Manager');

        $feeTypes = [
            ['fee_name' => 'Fish Landing', 'base_rate' => 30.00, 'unit' => 'kg'],
            ['fee_name' => 'Fish Unloading', 'base_rate' => 50.00, 'unit' => 'kg'],
            ['fee_name' => 'Wharfage', 'base_rate' => 20.00, 'unit' => 'trip'],
            ['fee_name' => 'Parking', 'base_rate' => 15.00, 'unit' => 'day'],
            ['fee_name' => 'Storage', 'base_rate' => 25.00, 'unit' => 'day'],
            ['fee_name' => 'Rental', 'base_rate' => 100.00, 'unit' => 'month'],
            ['fee_name' => 'Accreditation', 'base_rate' => 250.00, 'unit' => 'head'],
            ['fee_name' => 'Auxiliary Invoice', 'base_rate' => 40.00, 'unit' => 'item'],
            ['fee_name' => 'Entrance', 'base_rate' => 10.00, 'unit' => 'head'],
            ['fee_name' => 'Usage', 'base_rate' => 15.00, 'unit' => 'hour'],
            ['fee_name' => 'Inspection', 'base_rate' => 150.00, 'unit' => 'unit'],
            ['fee_name' => 'Regulatory', 'base_rate' => 200.00, 'unit' => 'transaction'],
        ];
        foreach ($feeTypes as $fee) {
            FeeType::create($fee);
        }

        Stakeholder::create(['name' => 'Juan Dela Cruz', 'type' => 'buyer', 'contact_no' => '09123456789', 'email' => 'juan@example.com', 'address' => 'Pasacao, Camarines Sur', 'status' => 'active']);
        Stakeholder::create(['name' => 'Pedro Santos', 'type' => 'broker', 'contact_no' => '09987654321', 'email' => 'pedro@example.com', 'address' => 'Pasacao, Camarines Sur', 'status' => 'active']);
        Stakeholder::create(['name' => 'Maria Reyes', 'type' => 'buyer', 'contact_no' => '09176543210', 'email' => 'maria@example.com', 'address' => 'Pasacao, Camarines Sur', 'status' => 'active']);
        Stakeholder::create(['name' => 'Ana Cruz', 'type' => 'broker', 'contact_no' => '09811223344', 'email' => 'ana@example.com', 'address' => 'Pasacao, Camarines Sur', 'status' => 'inactive']);

        $this->call(StakeholderTypeSeeder::class);

        InventoryItem::create(['item_name' => 'Plastic Crates', 'category' => 'Containers', 'quantity' => 100, 'unit' => 'pcs', 'status' => 'available']);
        InventoryItem::create(['item_name' => 'Fishing Net', 'category' => 'Gear', 'quantity' => 8, 'unit' => 'pcs', 'status' => 'low_stock']);
        InventoryItem::create(['item_name' => 'Life Vest', 'category' => 'Safety', 'quantity' => 0, 'unit' => 'pcs', 'status' => 'damaged']);
        InventoryItem::create(['item_name' => 'Cold Storage Pump', 'category' => 'Machinery', 'quantity' => 5, 'unit' => 'pcs', 'status' => 'available']);

        $this->call(RevenueHistorySeeder::class);
    }
}
