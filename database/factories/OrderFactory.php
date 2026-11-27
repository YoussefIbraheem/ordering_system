<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'subtotal' => $this->faker->randomFloat(2, 1, 100),
            'total' => $this->faker->randomFloat(2, 1, 100),
            'status' => OrderStatus::PENDING,
            'user_id' => User::factory(),
        ];
    }
}
