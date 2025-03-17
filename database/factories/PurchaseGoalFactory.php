<?php

namespace Database\Factories;

use App\Models\PurchaseGoal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseGoal>
 */
class PurchaseGoalFactory extends Factory
{
    protected $model = PurchaseGoal::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'target_amount' => $this->faker->randomFloat(2, 100, 10000),
            'amount_per_person' => $this->faker->randomFloat(2, 10, 500),
            'group_link' => $this->faker->url,
            'start_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'end_date' => $this->faker->dateTimeBetween('+1 month', '+6 months'),
            'creator_id' => User::factory(),
        ];
    }
}
