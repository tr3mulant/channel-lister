<?php

namespace IGE\ChannelLister\Database\Factories;

use IGE\ChannelLister\Models\WishBrandDirectory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WishBrandDirectory>
 */
class WishBrandDirectoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<WishBrandDirectory>
     */
    protected $model = WishBrandDirectory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => strtoupper($this->faker->bothify('??###')),
            'brand_name' => $this->faker->company(),
            'brand_website_url' => $this->faker->optional(0.7)->url(),
            'last_update' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Create a brand with a website.
     */
    public function withWebsite(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_website_url' => $this->faker->url(),
        ]);
    }

    /**
     * Create a brand without a website.
     */
    public function withoutWebsite(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_website_url' => null,
        ]);
    }

    /**
     * Create a brand with a secure HTTPS URL.
     */
    public function withSecureUrl(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_website_url' => 'https://'.$this->faker->domainName(),
        ]);
    }

    /**
     * Create a brand with an insecure HTTP URL.
     */
    public function withInsecureUrl(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_website_url' => 'http://'.$this->faker->domainName(),
        ]);
    }

    /**
     * Create a brand with a specific domain.
     */
    public function withDomain(string $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_website_url' => 'https://'.$domain,
        ]);
    }

    /**
     * Create a recently updated brand.
     */
    public function recentlyUpdated(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_update' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
