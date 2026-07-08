<?php

namespace Database\Factories;

use App\Models\FreightBookingCustomAmount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FreightBookingCustomAmount>
 */
class FreightBookingCustomAmountFactory extends Factory
{
    protected $model = FreightBookingCustomAmount::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, -500, 500),
        ];
    }
}
