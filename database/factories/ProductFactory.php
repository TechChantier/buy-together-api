<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'unit_price' => $this->faker->randomFloat(2, 5, 500),
            'bulk_price' => $this->faker->randomFloat(2, 50, 5000),
            'quantity' => $this->faker->numberBetween(1, 100),
            'image' => 'https://placehold.co/300x300/',
            'purchase_goal_id' => $this->faker->numberBetween(1, PurchaseGoal::count()),
        ];
    }
}