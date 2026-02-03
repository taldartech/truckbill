<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransporterMaster>
 */
class TransporterMasterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = $this->faker ?? \Faker\Factory::create();
        $panNumber = strtoupper($faker->regexify('[A-Z]{5}[0-9]{4}[A-Z]'));

        return [
            'name' => $faker->company(),
            'email' => $faker->unique()->safeEmail(),
            'pan_number' => $panNumber,
            'gst_number' => null,
            'gst_type' => null,
            'pan_active' => true,
            'address' => $faker->streetAddress(),
            'city_id' => null,
            'state_id' => null,
            'country_id' => null,
        ];
    }

    public function withLocation(int $cityId, int $stateId, int $countryId): static
    {
        return $this->state(fn (array $attributes) => [
            'city_id' => $cityId,
            'state_id' => $stateId,
            'country_id' => $countryId,
        ]);
    }
}
