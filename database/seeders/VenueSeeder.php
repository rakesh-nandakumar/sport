<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\VendorStatus;
use App\Models\ActivityType;
use App\Models\Game;
use App\Models\Service;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueHour;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $types = ActivityType::all()->keyBy('name');
        $ps = Game::where('platform', 'PS5')->pluck('id', 'name');
        $pc = Game::where('platform', 'PC')->pluck('id', 'name');
        $vr = Game::where('platform', 'VR')->pluck('id', 'name');
        $xbox = Game::where('platform', 'Xbox Series X')->pluck('id', 'name');

        $vendors = [
            'vendor@entrypoint.lk' => ['Dinesh Perera', '0771234567', 'Havelock Sports Holdings (Pvt) Ltd'],
            'ciel@entrypoint.lk' => ['Ruwan Fernando', '0312234567', 'Ciel Sports (Pvt) Ltd'],
            'misfits@entrypoint.lk' => ['Shanaka Silva', '0712345678', 'Misfits Entertainment (Pvt) Ltd'],
            'sportsworld@entrypoint.lk' => ['Nadeesha Jayawardena', '0112825555', 'Sports World Lanka (Pvt) Ltd'],
            'unisports@entrypoint.lk' => ['Kasun Wickramasinghe', '0112650301', 'Uni Sports Academy (Pvt) Ltd'],
            'levelup@entrypoint.lk' => ['Tharindu Bandara', '0812223344', 'Level Up Kandy (Pvt) Ltd'],
            'strikezone@entrypoint.lk' => ['Ayesha Rahman', '0112575757', 'Strike Zone Entertainment (Pvt) Ltd'],
            'galle@entrypoint.lk' => ['Roshan de Silva', '0912245678', 'Southern Sports Club'],
            'aqua@entrypoint.lk' => ['Malith Gunasekara', '0112930303', 'Aqua Fitness Studios (Pvt) Ltd'],
            'jaffna@entrypoint.lk' => ['Thavaraj Kumar', '0212223355', 'Jaffna Sports Club'],
        ];
        $owners = [];
        $businessNames = [];
        foreach ($vendors as $email => [$name, $phone, $business]) {
            $owners[$email] = User::updateOrCreate(['email' => $email], ['name' => $name, 'phone' => $phone, 'password' => 'password', 'role_id' => Role::Vendor, 'email_verified_at' => now()]);
            $businessNames[$email] = $business;
        }

        $standardHours = fn ($open = '07:00', $close = '23:00') => array_fill(0, 7, [$open, $close]);
        $peakEvenings = ['name' => 'Evening peak', 'days' => [1, 2, 3, 4, 5], 'starts_at' => '17:00', 'ends_at' => '22:00', 'multiplier' => 1.25];
        $weekend = ['name' => 'Weekend rate', 'days' => [0, 6], 'starts_at' => '08:00', 'ends_at' => '22:00', 'multiplier' => 1.2];

        $venues = [
            [
                'owner' => 'vendor@entrypoint.lk', 'name' => 'CR7 Futsal Arena', 'geo' => [6.8834, 79.866, '00500'], 'city' => 'Colombo 05', 'district' => 'Colombo',
                'address' => '48 Havelock Road, Havelock Town', 'phone' => '0112589100', 'email' => 'bookings@cr7arena.lk', 'cover' => '/images/CR7.png',
                'tagline' => 'Colombo\'s favourite floodlit 5-a-side turf, open till midnight',
                'description' => 'Two FIFA-grade artificial turf courts under full floodlights, with covered spectator stands, changing rooms and a juice bar. Bibs and match balls included with every booking. Ideal for corporate leagues, birthday tournaments and late-night kick-abouts.',
                'amenities' => ['Parking', 'Changing rooms', 'Showers', 'Floodlights', 'Cafeteria', 'Equipment rental', 'Spectator seating'],
                'bank' => ['Commercial Bank', 'Havelock Town', 'CR7 Arena (Pvt) Ltd', '8012345678'],
                'hours' => $standardHours('06:00', '00:00'), 'featured' => true,
                'services' => [
                    ['type' => 'Futsal', 'name' => 'Court A (Main)', 'desc' => '40m × 20m FIFA-approved turf with 3-side spectator seating.', 'slot' => 60, 'min' => 1, 'max' => 4, 'buffer' => 10, 'lead' => 60, 'players' => 12,
                        'options' => [['Full court', 'Both halves, up to 12 players', 5000, 1, true]], 'rates' => [$peakEvenings, $weekend]],
                    ['type' => 'Futsal', 'name' => 'Court B', 'desc' => 'Slightly smaller court, perfect for 4-a-side and training.', 'slot' => 60, 'min' => 1, 'max' => 3, 'buffer' => 10, 'lead' => 60, 'players' => 10,
                        'options' => [['Full court', null, 4000, 1, true], ['Half court', 'One half for drills / small sided', 2500, 2, false]], 'rates' => [$peakEvenings]],
                    ['type' => 'Football', 'name' => '7-a-side Pitch', 'desc' => 'Outdoor 60m × 40m turf pitch, floodlit.', 'slot' => 60, 'min' => 1, 'max' => 3, 'buffer' => 15, 'lead' => 120, 'players' => 16,
                        'options' => [['Full pitch', null, 8500, 1, true]], 'rates' => [$weekend]],
                ],
            ],
            [
                'owner' => 'vendor@entrypoint.lk', 'name' => 'Club Fusion Gaming Lounge', 'geo' => [6.9095, 79.8935, '10100'], 'city' => 'Rajagiriya', 'district' => 'Colombo',
                'address' => '212/1 Kotte Road, Rajagiriya', 'phone' => '0112889977', 'email' => 'play@clubfusion.lk', 'cover' => '/images/club-fusion.png',
                'tagline' => 'PS5 pods, esports PCs and a VR corner — air-conditioned and open late',
                'description' => 'Twelve PS5 stations on 55" 4K screens, six RTX gaming PCs, a private VIP pod for four and a Meta Quest VR corner. Snacks, energy drinks and controller hire available. Tournaments every Friday night.',
                'amenities' => ['Air conditioning', 'Wi-Fi', 'Cafeteria', 'Parking'],
                'bank' => ['Sampath Bank', 'Rajagiriya', 'Club Fusion Entertainment', '1023456789'],
                'hours' => $standardHours('10:00', '02:00'), 'featured' => true,
                'services' => [
                    ['type' => 'PlayStation & Gaming', 'name' => 'PS5 Stations', 'desc' => 'Individual pods with 55" 4K TV, two DualSense controllers and headset.', 'slot' => 60, 'min' => 1, 'max' => 6, 'buffer' => 0, 'lead' => 30, 'players' => 4,
                        'options' => [['Standard seat', 'Open-floor pod, 2 controllers', 500, 8, true], ['VIP pod', 'Private room, 4 controllers, couch', 1200, 2, false]],
                        'rates' => [['name' => 'Weekend peak', 'days' => [5, 6, 0], 'starts_at' => '18:00', 'ends_at' => '00:00', 'multiplier' => 1.3]],
                        'games' => $ps->only(['EA Sports FC 26', 'eFootball 2026', 'Call of Duty: Black Ops 7', 'Fortnite', 'GTA V', 'Gran Turismo 7', 'Mortal Kombat 1', 'Tekken 8', 'WWE 2K25', 'NBA 2K26', 'Cricket 24', 'Spider-Man 2', 'Rocket League'])->values()->all()],
                    ['type' => 'PlayStation & Gaming', 'name' => 'Esports PC Row', 'desc' => 'RTX 4070 rigs, 240Hz monitors, mechanical keyboards.', 'slot' => 60, 'min' => 1, 'max' => 8, 'buffer' => 0, 'lead' => 30, 'players' => 1,
                        'options' => [['PC seat', null, 400, 6, true]], 'rates' => [],
                        'games' => $pc->values()->all()],
                    ['type' => 'PlayStation & Gaming', 'name' => 'VR Corner', 'desc' => 'Meta Quest 3 with room-scale tracking.', 'slot' => 30, 'min' => 1, 'max' => 4, 'buffer' => 5, 'lead' => 30, 'players' => 1,
                        'options' => [['VR session', null, 600, 2, true]], 'rates' => [], 'games' => $vr->values()->all()],
                ],
            ],
            [
                'owner' => 'ciel@entrypoint.lk', 'name' => 'Ciel Sports Complex', 'geo' => [7.265, 79.856, '11540'], 'city' => 'Negombo', 'district' => 'Gampaha',
                'address' => 'Chilaw Road, Kochchikade', 'phone' => '0312277888', 'email' => 'info@cielsports.lk', 'cover' => '/images/ciel.jpg',
                'tagline' => 'Negombo\'s all-in-one indoor complex: futsal, badminton and cricket nets',
                'description' => 'A 30,000 sq ft indoor complex on the Chilaw road with a futsal court, four badminton courts and two cricket practice nets. Fully covered, so play on through the monsoon.',
                'amenities' => ['Parking', 'Changing rooms', 'Cafeteria', 'Equipment rental', 'First aid'],
                'bank' => ['Bank of Ceylon', 'Kochchikade', 'Ciel Sports Complex', '78945612'],
                'hours' => $standardHours('06:00', '23:00'), 'featured' => true,
                'services' => [
                    ['type' => 'Futsal', 'name' => 'Indoor Futsal Court', 'desc' => 'Indoor synthetic turf, air-cooled.', 'slot' => 60, 'min' => 1, 'max' => 3, 'buffer' => 10, 'lead' => 60, 'players' => 12,
                        'options' => [['Full court', null, 4500, 1, true]], 'rates' => [$peakEvenings]],
                    ['type' => 'Badminton', 'name' => 'Badminton Courts', 'desc' => 'Four BWF-standard synthetic mat courts.', 'slot' => 60, 'min' => 1, 'max' => 3, 'buffer' => 0, 'lead' => 30, 'players' => 4,
                        'options' => [['Court', 'Shuttles not included', 1200, 4, true]], 'rates' => [$peakEvenings]],
                    ['type' => 'Cricket', 'name' => 'Practice Nets', 'desc' => 'Two astro-turf nets, 22-yard pitch.', 'slot' => 30, 'min' => 1, 'max' => 4, 'buffer' => 0, 'lead' => 30, 'players' => 6,
                        'options' => [['Net only', 'Bring your own bowlers', 900, 2, true], ['Net + bowling machine', 'Includes machine and 60 balls', 1800, 1, false]], 'rates' => []],
                ],
            ],
            [
                'owner' => 'misfits@entrypoint.lk', 'name' => 'Misfits Arena', 'geo' => [6.851, 79.87, '10350'], 'city' => 'Dehiwala', 'district' => 'Colombo',
                'address' => '15 Hill Street, Dehiwala', 'phone' => '0777123456', 'email' => 'hello@misfitsarena.lk', 'cover' => '/images/misfits-arena.png',
                'tagline' => 'Indoor paintball, laser tag and a rooftop basketball court',
                'description' => "Colombo's only indoor paintball arena with themed obstacle rooms, plus a full-size rooftop basketball court under lights. Gear, mask, marker and 100 paintballs included in every session.",
                'amenities' => ['Parking', 'Changing rooms', 'Showers', 'Equipment rental', 'First aid', 'Cafeteria'],
                'bank' => ['HNB', 'Dehiwala', 'Misfits Arena', '0330020123456'],
                'hours' => $standardHours('09:00', '22:00'), 'featured' => true,
                'services' => [
                    ['type' => 'Paintball', 'name' => 'Indoor Paintball Session', 'desc' => 'Themed arena, marker, mask, overalls and 100 balls per player included.', 'slot' => 90, 'min' => 1, 'max' => 2, 'buffer' => 30, 'lead' => 180, 'players' => 16,
                        'options' => [['Standard session', 'Up to 8 players, 100 balls each', 12000, 1, true], ['Group battle', 'Up to 16 players, referee, 150 balls each', 22000, 1, false]],
                        'rates' => [$weekend]],
                    ['type' => 'Basketball', 'name' => 'Rooftop Court', 'desc' => 'Full court, acrylic surface, floodlit until 10pm.', 'slot' => 60, 'min' => 1, 'max' => 3, 'buffer' => 0, 'lead' => 60, 'players' => 10,
                        'options' => [['Full court', null, 3500, 1, true], ['Half court', null, 2000, 2, false]], 'rates' => [$peakEvenings]],
                ],
            ],
            [
                'owner' => 'sportsworld@entrypoint.lk', 'name' => 'Sports World Nugegoda', 'geo' => [6.865, 79.899, '10250'], 'city' => 'Nugegoda', 'district' => 'Colombo',
                'address' => '120 High Level Road, Nugegoda', 'phone' => '0112825555', 'email' => null, 'cover' => '/images/sports-world.jpg',
                'tagline' => 'Cricket nets with bowling machines, plus badminton and table tennis',
                'description' => 'Four cricket nets (two with Leverage bowling machines), three badminton courts and four table-tennis tables under one roof. Coaching available on request.',
                'amenities' => ['Parking', 'Changing rooms', 'Equipment rental', 'Air conditioning'],
                'bank' => ['Seylan Bank', 'Nugegoda', 'Sports World (Pvt) Ltd', '0040012345678'],
                'hours' => $standardHours('06:00', '22:00'),
                'services' => [
                    ['type' => 'Cricket', 'name' => 'Cricket Nets', 'desc' => 'Astro nets, floodlit.', 'slot' => 30, 'min' => 2, 'max' => 6, 'buffer' => 0, 'lead' => 30, 'players' => 6,
                        'options' => [['Standard net', null, 800, 2, true], ['Bowling machine net', 'Leverage machine, speeds up to 140 km/h', 1600, 2, false]], 'rates' => [$peakEvenings]],
                    ['type' => 'Badminton', 'name' => 'Badminton Courts', 'desc' => 'Wooden sprung floor.', 'slot' => 60, 'min' => 1, 'max' => 2, 'buffer' => 0, 'lead' => 30, 'players' => 4,
                        'options' => [['Court', null, 1000, 3, true]], 'rates' => [$peakEvenings]],
                    ['type' => 'Table Tennis', 'name' => 'Table Tennis', 'desc' => 'Butterfly tables, bats and balls provided.', 'slot' => 30, 'min' => 1, 'max' => 4, 'buffer' => 0, 'lead' => 15, 'players' => 4,
                        'options' => [['Table', null, 400, 4, true]], 'rates' => []],
                ],
            ],
            [
                'owner' => 'unisports@entrypoint.lk', 'name' => 'Uni Sports Center', 'geo' => [6.796, 79.901, '10400'], 'city' => 'Moratuwa', 'district' => 'Colombo',
                'address' => 'Katubedda Junction, Moratuwa', 'phone' => '0112650301', 'email' => 'sports@unicenter.lk', 'cover' => '/images/uni-sports-center.png',
                'tagline' => 'Campus-side multi-sport centre with a 25m pool',
                'description' => 'Futsal court, indoor basketball, four badminton courts and a 25-metre six-lane pool. Student discounts on weekdays before 5pm.',
                'amenities' => ['Parking', 'Changing rooms', 'Showers', 'Cafeteria', 'Floodlights', 'Spectator seating'],
                'bank' => ['People\'s Bank', 'Moratuwa', 'Uni Sports Center', '204100112233'],
                'hours' => $standardHours('06:00', '22:00'),
                'services' => [
                    ['type' => 'Futsal', 'name' => 'Futsal Court', 'desc' => 'Outdoor turf, floodlit.', 'slot' => 60, 'min' => 1, 'max' => 3, 'buffer' => 10, 'lead' => 60, 'players' => 12,
                        'options' => [['Full court', null, 3500, 1, true]], 'rates' => [['name' => 'Student off-peak', 'days' => [1, 2, 3, 4, 5], 'starts_at' => '06:00', 'ends_at' => '17:00', 'multiplier' => 0.8], $weekend]],
                    ['type' => 'Basketball', 'name' => 'Indoor Basketball', 'desc' => 'Hardwood full court.', 'slot' => 60, 'min' => 1, 'max' => 2, 'buffer' => 0, 'lead' => 60, 'players' => 10,
                        'options' => [['Full court', null, 3000, 1, true]], 'rates' => []],
                    ['type' => 'Badminton', 'name' => 'Badminton Hall', 'desc' => 'Four synthetic courts.', 'slot' => 60, 'min' => 1, 'max' => 2, 'buffer' => 0, 'lead' => 30, 'players' => 4,
                        'options' => [['Court', null, 900, 4, true]], 'rates' => [$peakEvenings]],
                    ['type' => 'Swimming', 'name' => '25m Pool Lanes', 'desc' => 'Six lanes, heated in season, lifeguard on duty.', 'slot' => 60, 'min' => 1, 'max' => 2, 'buffer' => 0, 'lead' => 60, 'players' => 1,
                        'options' => [['Lane (single swimmer)', null, 700, 6, true]], 'rates' => []],
                ],
            ],
            [
                'owner' => 'levelup@entrypoint.lk', 'name' => 'Level Up eSports Hub', 'geo' => [7.286, 80.626, '20000'], 'city' => 'Kandy', 'district' => 'Kandy',
                'address' => '88 Peradeniya Road, Kandy', 'phone' => '0812223344', 'email' => 'gg@levelup.lk', 'cover' => '/images/slide1.jpeg',
                'tagline' => 'Kandy\'s biggest console and PC gaming hub',
                'description' => 'Ten PS5 and four Xbox Series X stations, twelve gaming PCs, streaming booth and a snack bar. Home of the Central Province FC league.',
                'amenities' => ['Air conditioning', 'Wi-Fi', 'Cafeteria'],
                'bank' => ['NDB Bank', 'Kandy', 'Level Up Gaming', '101234567890'],
                'hours' => $standardHours('10:00', '23:00'), 'featured' => true,
                'services' => [
                    ['type' => 'PlayStation & Gaming', 'name' => 'Console Stations', 'desc' => 'PS5 and Xbox pods with 50" screens.', 'slot' => 60, 'min' => 1, 'max' => 6, 'buffer' => 0, 'lead' => 30, 'players' => 4,
                        'options' => [['PS5 seat', null, 450, 10, true], ['Xbox seat', null, 400, 4, false]],
                        'rates' => [['name' => 'Evening peak', 'days' => [0, 1, 2, 3, 4, 5, 6], 'starts_at' => '18:00', 'ends_at' => '23:00', 'multiplier' => 1.2]],
                        'games' => $ps->merge($xbox)->values()->all()],
                    ['type' => 'PlayStation & Gaming', 'name' => 'PC Gaming Bays', 'desc' => 'RTX 4060 rigs, 165Hz.', 'slot' => 60, 'min' => 1, 'max' => 8, 'buffer' => 0, 'lead' => 30, 'players' => 1,
                        'options' => [['PC seat', null, 350, 12, true]], 'rates' => [], 'games' => $pc->values()->all()],
                ],
            ],
            [
                'owner' => 'misfits@entrypoint.lk', 'name' => 'Colombo Paintball Park', 'geo' => [6.889, 79.935, '10120'], 'city' => 'Battaramulla', 'district' => 'Colombo',
                'address' => 'Diyawanna Gardens, Pelawatte', 'phone' => '0765554433', 'email' => 'book@colombopaintball.lk', 'cover' => '/images/parallax2.jpg',
                'tagline' => 'Outdoor jungle-style paintball on 3 acres by the lake',
                'description' => 'Three outdoor fields — Village, Bunker and Jungle — with bridges, towers and bunkers. Camo overalls, masks and markers included. Corporate team-building packages available.',
                'amenities' => ['Parking', 'Changing rooms', 'Showers', 'Equipment rental', 'First aid'],
                'bank' => ['Commercial Bank', 'Battaramulla', 'Colombo Paintball Park', '8098765432'],
                'hours' => $standardHours('08:00', '18:00'),
                'services' => [
                    ['type' => 'Paintball', 'name' => 'Outdoor Field Session', 'desc' => 'Two-hour session across all three fields. 200 balls per player.', 'slot' => 120, 'min' => 1, 'max' => 2, 'buffer' => 30, 'lead' => 1440, 'players' => 20,
                        'options' => [['Squad (up to 10)', null, 25000, 1, true], ['Platoon (up to 20)', 'Two referees', 45000, 1, false]], 'rates' => [$weekend]],
                ],
            ],
            [
                'owner' => 'strikezone@entrypoint.lk', 'name' => 'Strike Zone Bowling', 'geo' => [6.911, 79.852, '00300'], 'city' => 'Colombo 03', 'district' => 'Colombo',
                'address' => 'Level 5, Liberty Plaza, Kollupitiya', 'phone' => '0112575757', 'email' => 'lanes@strikezone.lk', 'cover' => '/images/parallax5.jpg',
                'tagline' => 'Eight glow-in-the-dark ten-pin lanes in the heart of Colombo',
                'description' => 'Eight Brunswick lanes with bumpers for kids, cosmic bowling after 8pm, shoe hire included. Birthday and corporate packages available.',
                'amenities' => ['Air conditioning', 'Cafeteria', 'Parking', 'Wi-Fi'],
                'bank' => ['DFCC Bank', 'Kollupitiya', 'Strike Zone Entertainment', '2010012345'],
                'hours' => $standardHours('11:00', '23:00'),
                'services' => [
                    ['type' => 'Bowling', 'name' => 'Ten-pin Lanes', 'desc' => 'Up to 6 bowlers per lane, shoes included.', 'slot' => 60, 'min' => 1, 'max' => 3, 'buffer' => 0, 'lead' => 30, 'players' => 6,
                        'options' => [['Lane', 'Up to 6 bowlers', 3200, 8, true]],
                        'rates' => [['name' => 'Cosmic bowling', 'days' => [4, 5, 6], 'starts_at' => '20:00', 'ends_at' => '23:00', 'multiplier' => 1.35]]],
                ],
            ],
            [
                'owner' => 'sportsworld@entrypoint.lk', 'name' => 'Smash Badminton Academy', 'geo' => [6.841, 79.964, '10230'], 'city' => 'Kottawa', 'district' => 'Colombo',
                'address' => 'Makumbura, Kottawa', 'phone' => '0112783939', 'email' => null, 'cover' => '/images/imgslide1.avif',
                'tagline' => 'Six wooden courts, coaching and a pro shop',
                'description' => 'Six BWF-standard wooden sprung courts with Yonex mats, a pro shop for stringing and a café. Coaching for juniors every weekend.',
                'amenities' => ['Parking', 'Changing rooms', 'Cafeteria', 'Equipment rental', 'Air conditioning'],
                'bank' => ['Sampath Bank', 'Kottawa', 'Smash Academy', '1098765432'],
                'hours' => $standardHours('06:00', '23:00'),
                'services' => [
                    ['type' => 'Badminton', 'name' => 'Wooden Courts', 'desc' => 'Sprung floor, competition lighting.', 'slot' => 60, 'min' => 1, 'max' => 3, 'buffer' => 0, 'lead' => 30, 'players' => 4,
                        'options' => [['Court', null, 1400, 6, true]], 'rates' => [$peakEvenings, $weekend]],
                ],
            ],
            [
                'owner' => 'galle@entrypoint.lk', 'name' => 'Galle Fort Turf', 'geo' => [6.034, 80.238, '80000'], 'city' => 'Galle', 'district' => 'Galle',
                'address' => 'Matara Road, Katugoda, Galle', 'phone' => '0912245678', 'email' => null, 'cover' => '/images/parallax3.jpg',
                'tagline' => 'Sea-breeze futsal and cricket nets in the south',
                'description' => 'Floodlit futsal turf and two cricket nets five minutes from the Fort. Popular with hotel guests and local leagues.',
                'amenities' => ['Parking', 'Floodlights', 'Changing rooms'],
                'bank' => ['Bank of Ceylon', 'Galle', 'Galle Fort Turf', '85123456'],
                'hours' => $standardHours('06:00', '23:00'),
                'services' => [
                    ['type' => 'Futsal', 'name' => 'Turf Court', 'desc' => 'Outdoor turf, floodlit.', 'slot' => 60, 'min' => 1, 'max' => 3, 'buffer' => 10, 'lead' => 60, 'players' => 12,
                        'options' => [['Full court', null, 3000, 1, true]], 'rates' => [$peakEvenings]],
                    ['type' => 'Cricket', 'name' => 'Cricket Nets', 'desc' => 'Two turf nets.', 'slot' => 30, 'min' => 2, 'max' => 4, 'buffer' => 0, 'lead' => 30, 'players' => 6,
                        'options' => [['Net', null, 700, 2, true]], 'rates' => []],
                ],
            ],
            [
                'owner' => 'aqua@entrypoint.lk', 'name' => 'Aqua Lanka Swim Centre', 'geo' => [6.99, 79.892, '11300'], 'city' => 'Wattala', 'district' => 'Gampaha',
                'address' => 'Negombo Road, Wattala', 'phone' => '0112930303', 'email' => 'swim@aqualanka.lk', 'cover' => '/images/imgslide2.avif',
                'tagline' => '50m Olympic pool lanes and aqua-fitness classes',
                'description' => 'Eight-lane 50-metre pool with electronic timing, plus a heated learner pool. Lane hire for squads and individuals, aqua-aerobics mornings.',
                'amenities' => ['Parking', 'Changing rooms', 'Showers', 'Cafeteria', 'First aid'],
                'bank' => ['HNB', 'Wattala', 'Aqua Lanka (Pvt) Ltd', '0350020987654'],
                'hours' => $standardHours('05:30', '21:00'),
                'services' => [
                    ['type' => 'Swimming', 'name' => '50m Pool Lanes', 'desc' => 'Eight lanes, lifeguard on duty.', 'slot' => 60, 'min' => 1, 'max' => 2, 'buffer' => 0, 'lead' => 60, 'players' => 1,
                        'options' => [['Lane (single swimmer)', null, 900, 8, true], ['Lane (squad, up to 6)', 'Coach must accompany', 3500, 2, false]], 'rates' => []],
                    ['type' => 'Fitness & Studio', 'name' => 'Aqua Aerobics Class', 'desc' => 'Instructor-led, 45-minute sessions, all levels.', 'slot' => 60, 'min' => 1, 'max' => 1, 'buffer' => 0, 'lead' => 120, 'players' => 1,
                        'options' => [['Class spot', null, 1500, 15, true]], 'rates' => []],
                ],
            ],
            [
                'owner' => 'aqua@entrypoint.lk', 'name' => 'Zen Yoga & Fitness Studio', 'geo' => [6.909, 79.866, '00700'], 'city' => 'Colombo 07', 'district' => 'Colombo',
                'address' => '31 Horton Place, Cinnamon Gardens', 'phone' => '0112696969', 'email' => 'namaste@zenstudio.lk', 'cover' => '/images/imgslide3.avif',
                'tagline' => 'Boutique yoga, pilates and HIIT studio hire',
                'description' => 'Two light-filled studios with sprung floors, mats, blocks and reformers. Book a spot in a class or hire the whole studio for your own group.',
                'amenities' => ['Air conditioning', 'Changing rooms', 'Showers', 'Wi-Fi'],
                'bank' => ['Commercial Bank', 'Cinnamon Gardens', 'Zen Studio', '8054321098'],
                'hours' => $standardHours('06:00', '21:00'),
                'services' => [
                    ['type' => 'Fitness & Studio', 'name' => 'Studio Hire', 'desc' => 'Private hire of Studio 2 (up to 12 people).', 'slot' => 60, 'min' => 1, 'max' => 3, 'buffer' => 15, 'lead' => 240, 'players' => 12,
                        'options' => [['Whole studio', null, 4000, 1, true]], 'rates' => []],
                ],
            ],
            [
                'owner' => 'jaffna@entrypoint.lk', 'name' => 'Jaffna Sports Club Courts', 'geo' => [9.665, 80.025, '40000'], 'city' => 'Jaffna', 'district' => 'Jaffna',
                'address' => 'Kandy Road, Jaffna', 'phone' => '0212223355', 'email' => null, 'cover' => '/images/slide2.jpeg',
                'tagline' => 'Tennis, badminton and table tennis in the north',
                'description' => 'Two floodlit hard tennis courts, an indoor badminton hall and a table-tennis room. Racquet hire and coaching available.',
                'amenities' => ['Parking', 'Floodlights', 'Equipment rental'],
                'bank' => ['Bank of Ceylon', 'Jaffna', 'Jaffna Sports Club', '73123456'],
                'hours' => $standardHours('06:00', '21:00'),
                'services' => [
                    ['type' => 'Tennis', 'name' => 'Hard Courts', 'desc' => 'Two acrylic hard courts, floodlit.', 'slot' => 60, 'min' => 1, 'max' => 2, 'buffer' => 0, 'lead' => 60, 'players' => 4,
                        'options' => [['Court', null, 1500, 2, true]], 'rates' => [$peakEvenings]],
                    ['type' => 'Badminton', 'name' => 'Badminton Hall', 'desc' => 'Three synthetic courts.', 'slot' => 60, 'min' => 1, 'max' => 2, 'buffer' => 0, 'lead' => 30, 'players' => 4,
                        'options' => [['Court', null, 800, 3, true]], 'rates' => []],
                    ['type' => 'Table Tennis', 'name' => 'TT Room', 'desc' => 'Two tables.', 'slot' => 30, 'min' => 1, 'max' => 4, 'buffer' => 0, 'lead' => 15, 'players' => 4,
                        'options' => [['Table', null, 300, 2, true]], 'rates' => []],
                ],
            ],
        ];

        // Every seeded vendor is an already-verified business, so their venues are live immediately.
        $admin = User::where('role_id', Role::SuperAdministrator)->first();
        foreach ($venues as $data) {
            $owner = $owners[$data['owner']];
            if (! $owner->vendorProfile()->exists()) {
                $businessName = $businessNames[$data['owner']];
                $owner->vendorProfile()->create([
                    'business_name' => $businessName,
                    'business_type' => str_contains($businessName, 'Club') ? 'club' : 'private_limited',
                    'registration_number' => 'PV'.rand(100000, 999999),
                    'owner_nic' => rand(199000000000, 199999999999),
                    'contact_person' => $owner->name,
                    'contact_phone' => $data['phone'],
                    'business_email' => $data['email'] ?? $owner->email,
                    'address_line1' => $data['address'],
                    'city' => $data['city'],
                    'district' => $data['district'],
                    'postal_code' => $data['geo'][2],
                    'latitude' => $data['geo'][0],
                    'longitude' => $data['geo'][1],
                    'description' => $data['description'],
                    'years_operating' => rand(2, 12),
                    'status' => VendorStatus::Active,
                    'reviewed_by' => $admin?->id,
                    'reviewed_at' => now()->subMonths(rand(1, 18)),
                    'terms_accepted_at' => now()->subMonths(rand(1, 18)),
                ]);
            }

            $venue = Venue::updateOrCreate(['name' => $data['name']], [
                'user_id' => $owners[$data['owner']]->id,
                'tagline' => $data['tagline'],
                'description' => $data['description'],
                'address' => $data['address'],
                'city' => $data['city'],
                'district' => $data['district'],
                'postal_code' => $data['geo'][2],
                'latitude' => $data['geo'][0],
                'longitude' => $data['geo'][1],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'cover_image' => $data['cover'],
                'amenities' => $data['amenities'],
                'bank_name' => $data['bank'][0],
                'bank_branch' => $data['bank'][1],
                'bank_account_name' => $data['bank'][2],
                'bank_account_number' => $data['bank'][3],
                'is_approved' => true,
                'is_featured' => $data['featured'] ?? false,
            ]);

            foreach ($data['hours'] as $day => [$open, $close]) {
                VenueHour::updateOrCreate(['venue_id' => $venue->id, 'day_of_week' => $day], ['opens_at' => $open, 'closes_at' => $close, 'is_closed' => false]);
            }

            foreach ($data['services'] as $s) {
                $service = Service::updateOrCreate(['venue_id' => $venue->id, 'name' => $s['name']], [
                    'activity_type_id' => $types[$s['type']]->id,
                    'description' => $s['desc'],
                    'slot_minutes' => $s['slot'],
                    'min_slots' => $s['min'],
                    'max_slots' => $s['max'],
                    'buffer_minutes' => $s['buffer'],
                    'lead_time_minutes' => $s['lead'],
                    'max_players' => $s['players'],
                    'is_active' => true,
                ]);

                $service->options()->delete();
                foreach ($s['options'] as $i => [$name, $desc, $price, $capacity, $default]) {
                    $service->options()->create(['name' => $name, 'description' => $desc, 'price_per_slot' => $price, 'capacity' => $capacity, 'is_default' => $default, 'sort_order' => $i]);
                }

                $service->rates()->delete();
                foreach ($s['rates'] as $rate) {
                    $service->rates()->create($rate);
                }

                $service->games()->sync($s['games'] ?? []);
            }
        }
    }
}
