<?php

namespace Database\Factories;

use App\Models\Channel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'content' => $this->faker->paragraph(),
            'direction' => $this->faker->randomElement(['inbound', 'outbound']),
            'type' => 'text',
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the message is inbound.
     */
    public function inbound(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => 'inbound',
            'metadata' => ['from_user' => 'wxid_' . $this->faker->bothify('##########')],
        ]);
    }

    /**
     * Indicate that the message is outbound.
     */
    public function outbound(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => 'outbound',
            'metadata' => ['to_user' => 'wxid_' . $this->faker->bothify('##########')],
        ]);
    }
}
