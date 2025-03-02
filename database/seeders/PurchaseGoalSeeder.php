<?php

namespace Database\Seeders;

use App\Models\PurchaseGoal;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseGoalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PurchaseGoal::factory()->count(10)->create();
    }
}
