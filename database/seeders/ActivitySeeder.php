<?php

namespace Database\Seeders;

use App\Models\Activity;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    /** Seed the activity catalog from config/activities.php (idempotent). */
    public function run(): void
    {
        foreach (config('activities.types', []) as $slug => $type) {
            Activity::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $type['name'] ?? ucfirst($slug),
                    'category' => $type['category'] ?? 'sports_court',
                    'icon' => $type['icon'] ?? null,
                    'default_pricing_unit' => $type['default_pricing_unit'] ?? 'per_hour',
                    'is_active' => true,
                    'sort_order' => array_search($slug, array_keys(config('activities.types')), true),
                ]
            );
        }
    }
}
