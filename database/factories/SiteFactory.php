<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->company(),
            'url' => fake()->unique()->url(),
            'description' => fake()->optional()->sentence(),
            'favicon_url' => fake()->optional()->imageUrl(32, 32),
            'is_public' => true,
            'is_active' => true,
            'clicks' => fake()->numberBetween(0, 1000),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Mark the site as private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    /**
     * Mark the site as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Associate the site with a category.
     */
    public function forCategory(int $categoryId): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $categoryId,
        ]);
    }
}
