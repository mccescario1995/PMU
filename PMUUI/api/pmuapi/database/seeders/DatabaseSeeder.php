<?php

namespace Database\Seeders;

use App\Models\FeeType;
use App\Models\InventoryItem;
use App\Models\Setting;
use App\Models\Stakeholder;
use App\Models\Status;
use App\Models\User;
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
        $permissions = [
            'view roles', 'create roles', 'edit roles', 'delete roles',
            'view users', 'create users', 'edit users', 'delete users',
            'view stakeholders', 'create stakeholders', 'edit stakeholders', 'delete stakeholders',
            'view stakeholder types', 'create stakeholder types', 'edit stakeholder types', 'delete stakeholder types',
            'view fee types', 'create fee types', 'edit fee types', 'delete fee types',
            'view settings', 'create settings', 'edit settings', 'delete settings',
            'view statuses', 'create statuses', 'edit statuses', 'delete statuses',
            'view transactions', 'create transactions', 'edit transactions', 'delete transactions',
            'view inventory', 'create inventory', 'edit inventory', 'delete inventory',
            'view inventory planning',
            'view weather', 'create weather', 'edit weather', 'delete weather',
            'create imports',
            'view forecasts', 'create forecasts',
            'view dashboard',
            'view reports',
            'view audit logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'Port Manager' => $permissions,
            'Statistician' => [
                'view stakeholders', 'create stakeholders', 'edit stakeholders', 'delete stakeholders',
                'view transactions', 'create transactions', 'edit transactions', 'delete transactions',
                'view dashboard', 'view forecasts', 'create forecasts', 'view reports',
                'view inventory planning', 'view weather',
            ],
            
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

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

        $settings = [
            ['key' => 'low_stock_threshold', 'value' => '10', 'type' => 'number', 'description' => 'Minimum quantity before item is marked low stock'],
            ['key' => 'port_name', 'value' => 'Pasacao Port', 'type' => 'string', 'description' => 'Official port name'],
            ['key' => 'currency', 'value' => 'PHP', 'type' => 'string', 'description' => 'Default currency for transactions'],
        ];
        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        $statuses = [
            ['name' => 'available', 'type' => 'inventory', 'color' => 'green'],
            ['name' => 'low_stock', 'type' => 'inventory', 'color' => 'yellow'],
            ['name' => 'damaged', 'type' => 'inventory', 'color' => 'red'],
            ['name' => 'pending', 'type' => 'transaction', 'color' => 'yellow'],
            ['name' => 'completed', 'type' => 'transaction', 'color' => 'green'],
            ['name' => 'cancelled', 'type' => 'transaction', 'color' => 'red'],
            ['name' => 'active', 'type' => 'stakeholder', 'color' => 'green'],
            ['name' => 'inactive', 'type' => 'stakeholder', 'color' => 'red'],
        ];
        foreach ($statuses as $status) {
            Status::firstOrCreate(['name' => $status['name'], 'type' => $status['type']], $status);
        }

        $this->call(RevenueHistorySeeder::class);
    }
}
