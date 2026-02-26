<?php

namespace Database\Factories;

use App\Enums\InquiryIntent;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inquiry>
 */
class InquiryFactory extends Factory
{
    protected $model = Inquiry::class;

    public function definition(): array
    {
        $poster = User::factory()->create(['primary_role' => 'brand']);

        return [
            'brand_id' => null,
            'poster_id' => $poster->id,
            'poster_type' => 'brand',
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'status' => InquiryStatus::POSTED,
            'urgency' => 'normal',
            'inquiry_type' => InquiryType::MATERIAL,
            'intent' => InquiryIntent::BUY,
            'quantity' => 100,
            'quantity_unit' => 'kg',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'location' => 'New Delhi',
            'visibility' => 'all',
            'locked_at' => null,
            'expires_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InquiryStatus::LOCKED,
            'locked_at' => now(),
        ]);
    }

    public function forPoster(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'poster_id' => $user->id,
            'poster_type' => $user->primary_role ?? 'brand',
        ]);
    }

    public function visibilityDealers(): static
    {
        return $this->state(fn (array $attributes) => ['visibility' => 'dealers']);
    }

    public function visibilityConverters(): static
    {
        return $this->state(fn (array $attributes) => ['visibility' => 'converters']);
    }
}
