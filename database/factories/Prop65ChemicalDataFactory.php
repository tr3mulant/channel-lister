<?php

namespace IGE\ChannelLister\Database\Factories;

use IGE\ChannelLister\Models\Prop65ChemicalData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prop65ChemicalData>
 */
class Prop65ChemicalDataFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Prop65ChemicalData>
     */
    protected $model = Prop65ChemicalData::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chemical' => $this->faker->words(2, true),
            'type_of_toxicity' => $this->faker->optional()->randomElement([
                'cancer',
                'reproductive toxicity',
                'developmental toxicity',
                'both cancer and reproductive toxicity',
            ]),
            'listing_mechanism' => $this->faker->optional()->randomElement([
                'Labor Code',
                'Authoritative Bodies',
                'Formally Required to be Labeled',
            ]),
            'cas_no' => $this->faker->optional()->regexify('\d{2,7}-\d{2}-\d'),
            'nsrl_or_madl' => $this->faker->optional()->randomElement([
                '0.5 micrograms/day',
                '1000 micrograms/day',
                'No Significant Risk Level',
            ]),
            'date_listed' => $this->faker->dateTimeBetween('-10 years', 'now'),
            'last_update' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Create a chemical with a CAS number.
     */
    public function withCasNumber(): static
    {
        return $this->state(fn (array $attributes) => [
            'cas_no' => $this->faker->regexify('\d{2,7}-\d{2}-\d'),
        ]);
    }

    /**
     * Create a chemical without a CAS number.
     */
    public function withoutCasNumber(): static
    {
        return $this->state(fn (array $attributes) => [
            'cas_no' => null,
        ]);
    }

    /**
     * Create a chemical with NSRL or MADL value.
     */
    public function withNsrlOrMadl(): static
    {
        return $this->state(fn (array $attributes) => [
            'nsrl_or_madl' => $this->faker->randomElement([
                '0.5 micrograms/day',
                '1000 micrograms/day',
                'No Significant Risk Level',
            ]),
        ]);
    }

    /**
     * Create a chemical without NSRL or MADL value.
     */
    public function withoutNsrlOrMadl(): static
    {
        return $this->state(fn (array $attributes) => [
            'nsrl_or_madl' => null,
        ]);
    }

    /**
     * Create a cancer-causing chemical.
     */
    public function cancer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_of_toxicity' => 'cancer',
        ]);
    }

    /**
     * Create a reproductive toxicity chemical.
     */
    public function reproductiveToxicity(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_of_toxicity' => 'reproductive toxicity',
        ]);
    }
}
