<?php

namespace Database\Factories;

use App\Models\TransporterMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transporter>
 */
class TransporterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = $this->faker ?? \Faker\Factory::create();

        return [
            'transporter_master_id' => TransporterMaster::factory(),
            'company_id' => 1,
            'is_owner' => false,
            'contact_person_name' => null,
            'mobile_number' => $faker->numerify('##########'),
            'declaration_given' => false,
            'total_trucks' => 0,
            'advance_amount' => 0,
            'short_name' => null,
            'beneficiary_id' => null,
            'tds_percentage' => 20,
        ];
    }

    public function forCompany(int $companyId): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $companyId,
        ]);
    }

    public function truckOwner(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_owner' => true,
        ]);
    }
}
