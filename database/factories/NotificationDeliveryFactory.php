<?php

namespace Database\Factories;

use App\Models\NotificationDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationDelivery>
 */
class NotificationDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type'          => 'email',
            'category'      => 'general',
            'recipient'     => fake()->email(),
            'subject'       => fake()->sentence(),
            'body'          => fake()->text(),
            'status'        => 'pending',
            'error_message' => null,
        ];
    }
}
