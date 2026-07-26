<?php

namespace Database\Seeders;

use App\Models\Stakeholder;
use App\Models\StakeholderType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StakeholderTypeSeeder extends Seeder
{
    public function run(): void
    {
        $buyer = StakeholderType::create(['name' => 'Buyer', 'description' => 'Purchases fish and seafood']);
        $broker = StakeholderType::create(['name' => 'Broker', 'description' => 'Facilitates transactions']);
        $renter = StakeholderType::create(['name' => 'Renter', 'description' => 'Rents port facilities']);

        Stakeholder::where('type', 'buyer')->update(['stakeholder_type_id' => $buyer->id]);
        Stakeholder::where('type', 'broker')->update(['stakeholder_type_id' => $broker->id]);
        Stakeholder::where('type', 'renter')->update(['stakeholder_type_id' => $renter->id]);
    }
}
