<?php

use App\Models\Activity;
use App\Models\Indoor;
use App\Models\Resource;
use Database\Seeders\ActivitySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Tag string (trimmed, lowercased) -> activity slug. */
    private array $aliasMap = [
        'futsal' => 'futsal',
        'football' => 'football',
        'cricket' => 'cricket',
        'badminton' => 'badminton',
        'tennis' => 'tennis',
        'basketball' => 'basketball',
        'volleyball' => 'volleyball',
        'squash' => 'squash',
        'pickleball' => 'pickleball',
        'table tennis' => 'table_tennis',
        'tabletennis' => 'table_tennis',
        'pool' => 'pool_snooker',
        'snooker' => 'pool_snooker',
        'billiards' => 'pool_snooker',
        'ps5' => 'ps5',
        'playstation' => 'ps5',
        'console gaming' => 'ps5',
        'board games' => 'board_games',
        'boardgame' => 'board_games',
        'board game' => 'board_games',
        'board' => 'board_games',
        'games' => 'board_games',
    ];

    public function up(): void
    {
        // The catalog table must exist for the backfill; seeding is idempotent.
        (new ActivitySeeder)->run();

        $activities = Activity::all()->keyBy('slug');

        foreach (Indoor::all() as $indoor) {
            // Idempotent: skip venues that already have resources.
            if ($indoor->resources()->exists()) {
                continue;
            }

            $slugs = $this->matchedSlugs($indoor->tags);

            if ($activities->has('other') && $slugs === []) {
                $slugs = ['other'];
            }

            $rate = (float) preg_replace('/[^0-9.]/', '', (string) $indoor->price);

            $firstResourceId = null;

            foreach ($slugs as $slug) {
                $activity = $activities->get($slug);

                if (! $activity) {
                    continue;
                }

                $resourceId = $this->createResource($indoor, $activity, $rate, $slugs === ['other']);
                $firstResourceId ??= $resourceId;
            }

            if ($firstResourceId !== null) {
                DB::table('bookings')
                    ->where('indoor_id', $indoor->id)
                    ->whereNull('resource_id')
                    ->update([
                        'resource_id' => $firstResourceId,
                        'status' => 'confirmed',
                        'total_price' => null,
                    ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('bookings')->whereNotNull('resource_id')->update(['resource_id' => null]);
        DB::table('resources')->truncate();
    }

    private function matchedSlugs(string $tags): array
    {
        $slugs = [];

        foreach (explode(',', $tags) as $tag) {
            $tag = Str::lower(trim($tag));

            if ($tag === '') {
                continue;
            }

            $slug = $this->aliasMap[$tag] ?? null;

            if ($slug !== null && ! in_array($slug, $slugs, true)) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    private function createResource(Indoor $indoor, Activity $activity, float $rate, bool $fallback): ?int
    {
        $resource = Resource::create([
            'indoor_id' => $indoor->id,
            'activity_id' => $activity->id,
            'name' => $fallback ? $indoor->title : $activity->name . ' 1',
            'rate' => $rate,
            'pricing_unit' => 'per_hour',
            'custom_fields' => [],
            'sort_order' => 0,
        ]);

        return $resource->id;
    }
};
