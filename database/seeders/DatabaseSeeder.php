<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeeCategory;
use App\Models\House;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        FeeCategory::create([
            'name' => 'Satpam',
            'amount' => 100000
        ]);

        FeeCategory::create([
            'name' => 'Kebersihan',
            'amount' => 15000
        ]);

        for ($i = 1; $i <= 20; $i++) {
            House::create([
                'house_code' => 'Blok A-' . $i,
                'status' => 'tidak dihuni' 
            ]);
        }
    }
}