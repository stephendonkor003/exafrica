<?php

$defaultDescription = 'Extraordinary Africans celebrates exceptional African changemakers, innovators, leaders, and creators aligned with Agenda 2063 and The Africa We Want.';

return [
    'default_section' => 'home',
    'site_name' => 'Extraordinary Africans',
    'brand' => 'Extraordinary Africans | Agenda 2063',
    'description' => $defaultDescription,
    'keywords' => [
        'Extraordinary Africans',
        'Agenda 2063',
        'African Union',
        'The Africa We Want',
        'African changemakers',
        'African innovation',
        'African awards',
        'African nominations',
    ],
    'logos' => [
        'agenda_2063' => 'images/seo/agenda-2063-logo.png',
        'african_union' => 'images/seo/african-union-logo.png',
        'share_card' => 'images/seo/extraordinary-africans-share-card.png',
    ],
    'sections' => [
        'home' => [
            'label' => 'Home',
            'title' => 'Extraordinary Africans | Agenda 2063',
            'description' => $defaultDescription,
            'priority' => '1.0',
        ],
        'about' => [
            'label' => 'About',
            'title' => 'About Extraordinary Africans | Agenda 2063',
            'description' => 'Learn about the Extraordinary Africans initiative, a movement recognising value-driven African talent, innovation, excellence, and impact.',
            'priority' => '0.8',
        ],
        'categories' => [
            'label' => 'Categories and Descriptions',
            'title' => 'Competition Categories | Extraordinary Africans',
            'description' => 'Explore the active Extraordinary Africans competition categories connected to Agenda 2063 and managed through the back office.',
            'priority' => '0.8',
        ],
        'nominations' => [
            'label' => 'Nominations',
            'title' => 'Submit a Nomination | Extraordinary Africans',
            'description' => 'Nominate an extraordinary African whose achievements are transforming communities, driving innovation, and shaping Africa’s future.',
            'priority' => '0.9',
        ],
        'voting' => [
            'label' => 'Voting',
            'title' => 'Voting | Extraordinary Africans',
            'description' => 'View published nominees and participate in public voting for the Extraordinary Africans initiative.',
            'priority' => '0.7',
        ],
        'flow' => [
            'label' => 'Flow of Events',
            'title' => 'Flow of Events | Extraordinary Africans',
            'description' => 'See the 2026 Extraordinary Africans timeline, from nominations and screening to judging, voting, shortlisting, and awards.',
            'priority' => '0.7',
        ],
        'judges' => [
            'label' => 'Meet our Judges',
            'title' => 'Judges | Extraordinary Africans',
            'description' => 'Meet the judging panel supporting the review and recognition of Africa’s exceptional changemakers.',
            'priority' => '0.6',
        ],
        'winners' => [
            'label' => 'Winners',
            'title' => 'Winners | Extraordinary Africans',
            'description' => 'Discover the winners and featured honourees of the Extraordinary Africans initiative.',
            'priority' => '0.6',
        ],
    ],
];
