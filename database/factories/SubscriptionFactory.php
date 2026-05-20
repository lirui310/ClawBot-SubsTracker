<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'channel_id' => Channel::factory(),
            'title' => $this->faker->sentence(3),
            'content' => $this->faker->paragraph(),
            'deadline_at' => now()->addDays(60),
            'notify_days' => 30,
            'is_active' => true,
            'last_sent_at' => null,
        ];
    }

    public function expiring(): static
    {
        return $this->state(fn () => ['deadline_at' => now()->addDays(15)]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['deadline_at' => now()->subDay()]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
