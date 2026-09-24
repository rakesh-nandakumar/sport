<?php

namespace Database\Seeders;

use App\Models\ActivityType;
use App\Models\Game;
use Illuminate\Database\Seeder;

class ActivityTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Futsal', 'image' => '/images/activities/futsal.jpg', 'icon' => 'fa-solid fa-futbol', 'color' => '#16a34a', 'unit_label' => 'Court', 'default_slot_minutes' => 60, 'is_featured' => true, 'description' => '5-a-side indoor and turf courts.'],
            ['name' => 'Football', 'image' => '/images/activities/football.jpg', 'icon' => 'fa-regular fa-futbol', 'color' => '#15803d', 'unit_label' => 'Pitch', 'default_slot_minutes' => 60, 'is_featured' => true, 'description' => '7-a-side and 11-a-side pitches.'],
            ['name' => 'Cricket', 'image' => '/images/activities/cricket.jpg', 'icon' => 'fa-solid fa-baseball-bat-ball', 'color' => '#2563eb', 'unit_label' => 'Net', 'default_slot_minutes' => 30, 'is_featured' => true, 'description' => 'Practice nets with or without bowling machines.'],
            ['name' => 'Badminton', 'image' => '/images/activities/badminton.jpg', 'icon' => 'fa-solid fa-table-tennis-paddle-ball', 'color' => '#f59e0b', 'unit_label' => 'Court', 'default_slot_minutes' => 60, 'is_featured' => true, 'description' => 'Indoor wooden and synthetic courts.'],
            ['name' => 'Basketball', 'image' => '/images/activities/basketball.jpg', 'icon' => 'fa-solid fa-basketball', 'color' => '#ea580c', 'unit_label' => 'Court', 'default_slot_minutes' => 60, 'description' => 'Full and half courts.'],
            ['name' => 'PlayStation & Gaming', 'image' => '/images/activities/gaming.jpg', 'icon' => 'fa-solid fa-gamepad', 'color' => '#7c3aed', 'unit_label' => 'Station', 'default_slot_minutes' => 60, 'requires_game' => true, 'is_featured' => true, 'description' => 'PS5, Xbox, PC and VR stations by the hour.'],
            ['name' => 'Paintball', 'image' => '/images/activities/paintball.jpg', 'icon' => 'fa-solid fa-crosshairs', 'color' => '#dc2626', 'unit_label' => 'Session', 'default_slot_minutes' => 90, 'is_featured' => true, 'description' => 'Outdoor and indoor arenas with gear included.'],
            ['name' => 'Swimming', 'image' => '/images/activities/swimming.jpg', 'icon' => 'fa-solid fa-person-swimming', 'color' => '#0891b2', 'unit_label' => 'Lane', 'default_slot_minutes' => 60, 'description' => 'Lane hire and pool sessions.'],
            ['name' => 'Bowling', 'image' => '/images/activities/bowling.jpg', 'icon' => 'fa-solid fa-bowling-ball', 'color' => '#db2777', 'unit_label' => 'Lane', 'default_slot_minutes' => 60, 'description' => 'Ten-pin lanes.'],
            ['name' => 'Table Tennis', 'image' => '/images/activities/table-tennis.jpg', 'icon' => 'fa-solid fa-table-tennis-paddle-ball', 'color' => '#0d9488', 'unit_label' => 'Table', 'default_slot_minutes' => 30, 'description' => 'Indoor tables.'],
            ['name' => 'Tennis', 'image' => '/images/activities/tennis.jpg', 'icon' => 'fa-solid fa-baseball', 'color' => '#65a30d', 'unit_label' => 'Court', 'default_slot_minutes' => 60, 'description' => 'Clay, hard and synthetic courts.'],
            ['name' => 'Fitness & Studio', 'image' => '/images/activities/fitness.jpg', 'icon' => 'fa-solid fa-dumbbell', 'color' => '#4b5563', 'unit_label' => 'Session', 'default_slot_minutes' => 60, 'description' => 'Group classes, yoga and studio hire.'],
        ];

        foreach ($types as $i => $data) {
            ActivityType::updateOrCreate(['name' => $data['name']], $data + ['sort_order' => $i, 'requires_game' => $data['requires_game'] ?? false, 'is_featured' => $data['is_featured'] ?? false]);
        }

        $gaming = ActivityType::where('name', 'PlayStation & Gaming')->first();
        $games = [
            ['EA Sports FC 26', 'PS5', 4], ['eFootball 2026', 'PS5', 4], ['Call of Duty: Black Ops 7', 'PS5', 4],
            ['Fortnite', 'PS5', 4], ['GTA V', 'PS5', 1], ['Gran Turismo 7', 'PS5', 2], ['Mortal Kombat 1', 'PS5', 2],
            ['Tekken 8', 'PS5', 2], ['WWE 2K25', 'PS5', 4], ['NBA 2K26', 'PS5', 4], ['Cricket 24', 'PS5', 2],
            ['Spider-Man 2', 'PS5', 1], ['God of War Ragnarök', 'PS5', 1], ['Rocket League', 'PS5', 4],
            ['Forza Horizon 5', 'Xbox Series X', 2], ['Halo Infinite', 'Xbox Series X', 4],
            ['Valorant', 'PC', 5], ['Counter-Strike 2', 'PC', 5], ['Dota 2', 'PC', 5], ['Beat Saber', 'VR', 1],
        ];
        foreach ($games as [$name, $platform, $players]) {
            Game::updateOrCreate(['activity_type_id' => $gaming->id, 'name' => $name], ['platform' => $platform, 'max_players' => $players]);
        }
    }
}
