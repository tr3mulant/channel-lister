<?php

namespace IGE\ChannelLister\Database\Factories;

use IGE\ChannelLister\Models\AmazonListing;
use Illuminate\Database\Eloquent\Factories\Factory;

class AmazonListingFactory extends Factory
{
    protected $model = AmazonListing::class;

    public function definition(): array
    {
        return [
            'product_type' => $this->faker->randomElement(['LUGGAGE', 'BACKPACK', 'CLOTHING', 'ELECTRONICS']),
            'marketplace_id' => 'ATVPDKIKX0DER', // US marketplace
            'form_data' => [
                'item_name' => $this->faker->words(3, true),
                'brand' => $this->faker->company(),
                'product_description' => $this->faker->paragraph(),
                'bullet_point' => $this->faker->sentence(),
                'item_type_keyword' => $this->faker->words(2, true),
                'country_of_origin' => $this->faker->countryCode(),
                'supplier_declared_dg_hz_regulation' => 'GHS',
            ],
            'status' => $this->faker->randomElement(['draft', 'validating', 'validated', 'submitted', 'error']),
            'validation_errors' => null,
            'file_path' => null,
            'file_format' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'validated',
            'validation_errors' => null,
        ]);
    }

    public function withErrors(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'error',
            'validation_errors' => [
                'brand' => 'Brand is required',
                'item_name' => 'Title must be at least 10 characters',
            ],
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'file_path' => 'amazon-listings/'.$this->faker->uuid().'.csv',
            'file_format' => 'csv',
        ]);
    }

    public function luggage(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'LUGGAGE',
            'form_data' => array_merge($attributes['form_data'] ?? [], [
                'item_name' => $this->faker->words(2, true).' Suitcase',
                'item_type_keyword' => 'luggage',
                'number_of_wheels' => $this->faker->numberBetween(0, 4),
                'wheel' => $this->faker->randomElement(['Spinner', 'In-Line Skate']),
                'material' => $this->faker->randomElement(['nylon', 'polyester', 'leather']),
            ]),
        ]);
    }

    public function backpack(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'BACKPACK',
            'form_data' => array_merge($attributes['form_data'] ?? [], [
                'item_name' => $this->faker->words(2, true).' Backpack',
                'item_type_keyword' => 'backpack',
                'material' => $this->faker->randomElement(['nylon', 'canvas', 'polyester']),
                'department' => 'Unisex',
            ]),
        ]);
    }
}
