<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Indoor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resource>
 */
class ResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'indoor_id' => Indoor::factory(),
            'activity_id' => static::activityId('futsal', 'Futsal', 'sports_court'),
            'name' => 'Court A',
            'rate' => 1200,
            'pricing_unit' => 'per_hour',
            'min_duration_minutes' => 60,
            'slot_increment_minutes' => 30,
            'custom_fields' => [],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function ps5(): static
    {
        return $this->state(fn () => [
            'activity_id' => static::activityId('ps5', 'PS5 / Console Gaming', 'console_gaming'),
            'name' => 'PS5 Station 1',
            'pricing_unit' => 'per_hour',
            'rate' => 300,
        ]);
    }

    public function pool(): static
    {
        return $this->state(fn () => [
            'activity_id' => static::activityId('pool_snooker', 'Pool / Snooker / Billiards', 'sports_court'),
            'name' => 'Pool Table 1',
            'pricing_unit' => 'per_game',
            'rate' => 150,
        ]);
    }

    public function perSession(): static
    {
        return $this->state(fn () => [
            'activity_id' => static::activityId('board_games', 'Board & Table Games', 'table_games'),
            'name' => 'Board Table 1',
            'pricing_unit' => 'per_session',
            'rate' => 500,
        ]);
    }

    public function perPerson(): static
    {
        return $this->state(fn () => [
            'activity_id' => static::activityId('futsal', 'Futsal', 'sports_court'),
            'name' => 'Court B',
            'pricing_unit' => 'per_person',
            'rate' => 250,
        ]);
    }

    private static function activityId(string $slug, string $name, string $category): int
    {
        return Activity::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'category' => $category, 'default_pricing_unit' => 'per_hour']
        )->id;
    }
}
