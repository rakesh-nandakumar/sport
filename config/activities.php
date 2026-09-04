<?php

return [
    'pricing_units' => [
        'per_hour' => [
            'label' => 'Per hour',
            'time_based' => true,
        ],
        'per_session' => [
            'label' => 'Per session',
            'time_based' => false,
        ],
        'per_game' => [
            'label' => 'Per game / frame',
            'time_based' => false,
            'quantity_label' => 'Frames',
        ],
        'per_person' => [
            'label' => 'Per person',
            'time_based' => false,
            'quantity_label' => 'Players',
        ],
    ],

    'categories' => [
        'sports_court' => 'Sports / Court Booking',
        'console_gaming' => 'Console Gaming',
        'table_games' => 'Physical Table Games',
    ],

    'types' => [
        'futsal' => [
            'name' => 'Futsal',
            'category' => 'sports_court',
            'icon' => 'bx-football',
            'default_pricing_unit' => 'per_hour',
            'default_capacity' => 10,
            'fields' => [
                'surface' => [
                    'type' => 'select', 'label' => 'Surface',
                    'options' => ['Artificial turf', 'Wooden', 'Concrete', 'Rubber'],
                ],
                'court_size' => ['type' => 'text', 'label' => 'Court size', 'placeholder' => '40m x 20m'],
                'floodlit' => ['type' => 'boolean', 'label' => 'Floodlit'],
            ],
        ],

        'ps5' => [
            'name' => 'PS5 / Console Gaming',
            'category' => 'console_gaming',
            'icon' => 'bx-joystick',
            'default_pricing_unit' => 'per_hour',
            'default_capacity' => 4,
            'fields' => [
                'controllers' => ['type' => 'number', 'label' => 'Controllers', 'min' => 1, 'max' => 8],
                'display' => ['type' => 'text', 'label' => 'Display', 'placeholder' => '55" 4K TV'],
                'game_library' => [
                    'type' => 'list',
                    'label' => 'Game library',
                    'bookable' => true,
                    'select_label' => 'Choose a game',
                    'placeholder' => 'EA FC 25',
                ],
            ],
        ],

        'pool_snooker' => [
            'name' => 'Pool / Snooker / Billiards',
            'category' => 'sports_court',
            'icon' => 'bx-circle',
            'default_pricing_unit' => 'per_game',
            'default_capacity' => 4,
            'fields' => [
                'table_size' => [
                    'type' => 'select', 'label' => 'Table size',
                    'options' => ['6ft', '7ft', '8ft', '9ft', '12ft (full snooker)'],
                ],
                'game_type' => [
                    'type' => 'select', 'label' => 'Game',
                    'options' => ['8-ball pool', '9-ball pool', 'Snooker', 'Billiards'],
                ],
            ],
        ],

        'board_games' => [
            'name' => 'Board & Table Games',
            'category' => 'table_games',
            'icon' => 'bx-game',
            'default_pricing_unit' => 'per_session',
            'default_capacity' => 4,
            'fields' => [
                'seats' => ['type' => 'number', 'label' => 'Seats', 'min' => 1, 'max' => 12],
                'game_library' => [
                    'type' => 'list',
                    'label' => 'Game library',
                    'bookable' => true,
                    'select_label' => 'Choose a game',
                    'placeholder' => 'Monopoly',
                ],
            ],
        ],

        'football' => [
            'name' => 'Football',
            'category' => 'sports_court',
            'icon' => 'bx-football',
            'default_pricing_unit' => 'per_hour',
            'default_capacity' => 22,
            'fields' => [
                'surface' => [
                    'type' => 'select', 'label' => 'Surface',
                    'options' => ['Artificial turf', 'Grass', 'Wooden', 'Concrete'],
                ],
                'floodlit' => ['type' => 'boolean', 'label' => 'Floodlit'],
            ],
        ],

        'cricket' => [
            'name' => 'Cricket',
            'category' => 'sports_court',
            'icon' => 'bx-trophy',
            'default_pricing_unit' => 'per_hour',
            'default_capacity' => 22,
            'fields' => [
                'surface' => [
                    'type' => 'select', 'label' => 'Surface',
                    'options' => ['Artificial turf', 'Grass', 'Concrete'],
                ],
                'pitch_type' => ['type' => 'text', 'label' => 'Pitch type', 'placeholder' => '5m x 7m artificial'],
            ],
        ],

        'badminton' => [
            'name' => 'Badminton',
            'category' => 'sports_court',
            'icon' => 'bx-target-lock',
            'default_pricing_unit' => 'per_hour',
            'default_capacity' => 4,
            'fields' => [
                'surface' => [
                    'type' => 'select', 'label' => 'Surface',
                    'options' => ['Wooden', 'Rubber', 'Synthetic'],
                ],
                'floodlit' => ['type' => 'boolean', 'label' => 'Floodlit'],
            ],
        ],

        'tennis' => [
            'name' => 'Tennis',
            'category' => 'sports_court',
            'icon' => 'bx-tennis-ball',
            'default_pricing_unit' => 'per_hour',
            'default_capacity' => 4,
            'fields' => [
                'surface' => [
                    'type' => 'select', 'label' => 'Surface',
                    'options' => ['Clay', 'Grass', 'Hard', 'Artificial turf'],
                ],
                'floodlit' => ['type' => 'boolean', 'label' => 'Floodlit'],
            ],
        ],

        'basketball' => [
            'name' => 'Basketball',
            'category' => 'sports_court',
            'icon' => 'bx-basketball',
            'default_pricing_unit' => 'per_hour',
            'default_capacity' => 10,
            'fields' => [
                'surface' => [
                    'type' => 'select', 'label' => 'Surface',
                    'options' => ['Wooden', 'Concrete', 'Rubber'],
                ],
                'hoops' => ['type' => 'number', 'label' => 'Hoops', 'min' => 1, 'max' => 4],
                'floodlit' => ['type' => 'boolean', 'label' => 'Floodlit'],
            ],
        ],

        'volleyball' => [
            'name' => 'Volleyball',
            'category' => 'sports_court',
            'icon' => 'bx-grid-small',
            'default_pricing_unit' => 'per_hour',
            'default_capacity' => 12,
            'fields' => [
                'surface' => [
                    'type' => 'select', 'label' => 'Surface',
                    'options' => ['Wooden', 'Rubber', 'Synthetic'],
                ],
                'floodlit' => ['type' => 'boolean', 'label' => 'Floodlit'],
            ],
        ],

        'squash' => [
            'name' => 'Squash',
            'category' => 'sports_court',
            'icon' => 'bx-chalkboard',
            'default_pricing_unit' => 'per_hour',
            'default_capacity' => 2,
            'fields' => [
                'court_type' => [
                    'type' => 'select', 'label' => 'Court type',
                    'options' => ['Glass court', 'Concrete court'],
                ],
                'floodlit' => ['type' => 'boolean', 'label' => 'Floodlit'],
            ],
        ],

        'pickleball' => [
            'name' => 'Pickleball',
            'category' => 'sports_court',
            'icon' => 'bx-mouse-alt',
            'default_pricing_unit' => 'per_hour',
            'default_capacity' => 4,
            'fields' => [
                'surface' => [
                    'type' => 'select', 'label' => 'Surface',
                    'options' => ['Hard', 'Synthetic', 'Grass'],
                ],
                'floodlit' => ['type' => 'boolean', 'label' => 'Floodlit'],
            ],
        ],

        'table_tennis' => [
            'name' => 'Table Tennis',
            'category' => 'table_games',
            'icon' => 'bx-grid',
            'default_pricing_unit' => 'per_game',
            'default_capacity' => 2,
            'fields' => [
                'table_size' => [
                    'type' => 'select', 'label' => 'Table size',
                    'options' => ['Standard', 'Tournament (25mm)'],
                ],
                'floodlit' => ['type' => 'boolean', 'label' => 'Floodlit'],
            ],
        ],

        'other' => [
            'name' => 'Other',
            'category' => 'sports_court',
            'icon' => 'bx-dumbbell',
            'default_pricing_unit' => 'per_hour',
            'default_capacity' => null,
            'fields' => [
                'notes' => ['type' => 'text', 'label' => 'Notes', 'placeholder' => 'Anything customers should know'],
            ],
        ],
    ],
];
