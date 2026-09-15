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
            // Roles & Permissions
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            // Users
            'view users',
            'create users',
            'edit users',
            'delete users',
            // Stakeholders
            'view stakeholders',
            'create stakeholders',
            'edit stakeholders',
            'delete stakeholders',
            // Stakeholder Types
            'view stakeholder types',
            'create stakeholder types',
            'edit stakeholder types',
            'delete stakeholder types',
            // Fee Types
            'view fee types',
            'create fee types',
            'edit fee types',
            'delete fee types',
            // Settings
            'view settings',
            'create settings',
            'edit settings',
            'delete settings',
            // Statuses
            'view statuses',
            'create statuses',
            'edit statuses',
            'delete statuses',
            // Transactions
            'view transactions',
            'create transactions',
            'edit transactions',
            'delete transactions',
            // Inventory
            'view inventory',
            'create inventory',
            'edit inventory',
            'delete inventory',
            'view inventory planning',
            // Weather
            'view weather',
            'create weather',
            'edit weather',
            'delete weather',
            // Imports
            'create imports',
            // Forecasts
            'view forecasts',
            'create forecasts',
            // Dashboard
            'view dashboard',
            // Reports
            'view reports',
            // Audit Logs
            'view audit logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'Port Manager' => [
                // Roles & Permissions
                'view roles',
                'create roles',
                'edit roles',
                'delete roles',
                // Users
                'view users',
                'create users',
                'edit users',
                'delete users',
                // Stakeholders
                'view stakeholders',
                'create stakeholders',
                'edit stakeholders',
                'delete stakeholders',
                // Stakeholder Types
                'view stakeholder types',
                'create stakeholder types',
                'edit stakeholder types',
                'delete stakeholder types',
                // Fee Types
                'view fee types',
                'create fee types',
                'edit fee types',
                'delete fee types',
                // Settings
                'view settings',
                'create settings',
                'edit settings',
                'delete settings',
                // Statuses
                'view statuses',
                'create statuses',
                'edit statuses',
                'delete statuses',
                // Transactions
                // 'view transactions',
                // 'create transactions',
                // 'edit transactions',
                // 'delete transactions',
                // Inventory
                'view inventory',
                'create inventory',
                'edit inventory',
                'delete inventory',
                'view inventory planning',
                // Weather
                'view weather',
                'create weather',
                'edit weather',
                'delete weather',
                // Imports
                'create imports',
                // Forecasts
                'view forecasts',
                'create forecasts',
                // Dashboard
                'view dashboard',
                // Reports
                'view reports',
                // Audit Logs
                'view audit logs',
            ],
            'Statistician' => [
                'view stakeholders',
                'create stakeholders',
                'edit stakeholders',
                'delete stakeholders',
                'view transactions',
                'create transactions',
                'edit transactions',
                'delete transactions',
                'view dashboard',
                'view forecasts',
                'create forecasts',
                'view reports',
                'view inventory planning',
                'view weather',
            ],

        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        $user = User::create([
            'name' => 'Port Manager',
            'email' => 'admin@pmu.gov.ph',
            'password' => Hash::make('PMUmanager1'),
            'status' => 'active',
        ]);
        $user->assignRole('Port Manager');

        $userStat = User::create([
            'name' => 'Statistician',
            'email' => 'stat@pmu.gov.ph',
            'password' => Hash::make('PMUstatistician1'),
            'status' => 'active',
        ]);
        $userStat->assignRole('Statistician');

        // $feeTypes = [
        //     ['fee_name' => 'Fish Landing', 'base_rate' => 30.00, 'unit' => 'kg'],
        //     ['fee_name' => 'Fish Unloading', 'base_rate' => 50.00, 'unit' => 'kg'],
        //     ['fee_name' => 'Wharfage', 'base_rate' => 20.00, 'unit' => 'trip'],
        //     ['fee_name' => 'Parking', 'base_rate' => 15.00, 'unit' => 'day'],
        //     ['fee_name' => 'Storage', 'base_rate' => 25.00, 'unit' => 'day'],
        //     ['fee_name' => 'Rental', 'base_rate' => 100.00, 'unit' => 'month'],
        //     ['fee_name' => 'Accreditation', 'base_rate' => 250.00, 'unit' => 'head'],
        //     ['fee_name' => 'Auxiliary Invoice', 'base_rate' => 40.00, 'unit' => 'item'],
        //     ['fee_name' => 'Entrance', 'base_rate' => 10.00, 'unit' => 'head'],
        //     ['fee_name' => 'Usage', 'base_rate' => 15.00, 'unit' => 'hour'],
        //     ['fee_name' => 'Inspection', 'base_rate' => 150.00, 'unit' => 'unit'],
        //     ['fee_name' => 'Regulatory', 'base_rate' => 200.00, 'unit' => 'transaction'],
        // ];
        // foreach ($feeTypes as $fee) {
        //     FeeType::create($fee);
        // }

        // Stakeholder::create(['name' => 'Juan Dela Cruz', 'type' => 'buyer', 'contact_no' => '09123456789', 'email' => 'juan@example.com', 'address' => 'Pasacao, Camarines Sur', 'status' => 'active']);
        // Stakeholder::create(['name' => 'Pedro Santos', 'type' => 'broker', 'contact_no' => '09987654321', 'email' => 'pedro@example.com', 'address' => 'Pasacao, Camarines Sur', 'status' => 'active']);
        // Stakeholder::create(['name' => 'Maria Reyes', 'type' => 'buyer', 'contact_no' => '09176543210', 'email' => 'maria@example.com', 'address' => 'Pasacao, Camarines Sur', 'status' => 'active']);
        // Stakeholder::create(['name' => 'Ana Cruz', 'type' => 'broker', 'contact_no' => '09811223344', 'email' => 'ana@example.com', 'address' => 'Pasacao, Camarines Sur', 'status' => 'inactive']);

        $this->call(StakeholderTypeSeeder::class);

        // InventoryItem seed data
        $inventoryItems = [
            // Office Supplies
            [
                'item_name' => 'Log books',
                'category' => 'Office Supplies',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            [
                'item_name' => 'Long bond Paper',
                'category' => 'Office Supplies',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            [
                'item_name' => 'Envelopes (long)',
                'category' => 'Office Supplies',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            [
                'item_name' => 'Staplers',
                'category' => 'Office Supplies',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            [
                'item_name' => 'Scissors',
                'category' => 'Office Supplies',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            [
                'item_name' => 'Folders',
                'category' => 'Office Supplies',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            [
                'item_name' => 'Printer Ink',
                'category' => 'Office Supplies',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            [
                'item_name' => 'Pens',
                'category' => 'Office Supplies',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            // Calculator - fixed empty unit
            [
                'item_name' => 'Calculator',
                'category' => 'Office Supplies',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            
            // Materials
            [
                'item_name' => 'Cleaning Hoses',
                'category' => 'Materials',
                'quantity' => 0,
                'unit' => 'meters',
                'status' => 'available'
            ],
            [
                'item_name' => 'Utility Buckets',
                'category' => 'Materials',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            [
                'item_name' => 'Mop',
                'category' => 'Materials',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            // Brooms - fixed empty unit
            [
                'item_name' => 'Brooms',
                'category' => 'Materials',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            [
                'item_name' => 'Cleaning Agent',
                'category' => 'Materials',
                'quantity' => 0,
                'unit' => 'liters',
                'status' => 'available'
            ],
            [
                'item_name' => 'Dustpan',
                'category' => 'Materials',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],

            // Equipment
            [
                'item_name' => 'Power Generator',
                'category' => 'Equipment',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            [
                'item_name' => 'Weightened Scale',
                'category' => 'Equipment',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            // Trolley - fixed empty unit
            [
                'item_name' => 'Trolley',
                'category' => 'Equipment',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            [
                'item_name' => 'Submersible Water Pump',
                'category' => 'Equipment',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            // Ladder - fixed empty unit
            [
                'item_name' => 'Ladder',
                'category' => 'Equipment',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            [
                'item_name' => 'Flashlights',
                'category' => 'Equipment',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ],
            [
                'item_name' => 'Rubber Boots',
                'category' => 'Equipment',
                'quantity' => 0,
                'unit' => 'pcs',
                'status' => 'available'
            ]
        ];

        foreach ($inventoryItems as $item) {
            InventoryItem::create($item);
        }

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
        $this->call(MlFeatureSeeder::class);
    }
}

// What if i do tthis:
// have a initial model training that will compile and ingest data to a new table named transaction revenue from ml feature, weather data, revenue history so that it the data is combined and later when training again it will just train using the table transaction revenue. note that the model training is use for forecasting of revenue thru seasonal revenue or peack revenue.