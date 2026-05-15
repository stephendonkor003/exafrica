<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use App\Models\VotingPhase;
use Illuminate\Support\Facades\Hash;

class RoleAndPhaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Full system access'],
            ['name' => 'Evaluator', 'slug' => 'evaluator', 'description' => 'Evaluates nominations'],
            ['name' => 'Voting Analyst', 'slug' => 'voting_analyst', 'description' => 'Analyzes voting patterns'],
            ['name' => 'Judge', 'slug' => 'judge', 'description' => 'Voting judge'],
            ['name' => 'Committee Member', 'slug' => 'committee_member', 'description' => 'Committee participant'],
            ['name' => 'Voter', 'slug' => 'voter', 'description' => 'Public voter'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role_id' => Role::where('slug', 'super_admin')->value('id'),
                'is_active' => true,
            ]
        );

        $categories = [
            ['name' => 'Gender and Women Empowerment', 'description' => 'Women breaking barriers and championing equality across Africa.', 'icon' => 'fa-venus', 'position' => 1],
            ['name' => 'Advancing Trade, Investment, and Industrialization', 'description' => 'Leaders driving intra-African trade, investment, and industry.', 'icon' => 'fa-chart-line', 'position' => 2],
            ['name' => 'Agricultural Development', 'description' => 'Pioneers modernising African agriculture and food security.', 'icon' => 'fa-seedling', 'position' => 3],
            ['name' => 'Digital Innovation and Technology', 'description' => 'Innovators building Africa digital future.', 'icon' => 'fa-microchip', 'position' => 4],
            ['name' => 'Environmental Sustainability', 'description' => 'Champions of climate, conservation, and green communities.', 'icon' => 'fa-leaf', 'position' => 5],
            ['name' => 'Health and Well-being', 'description' => 'Visionaries improving healthcare access and community wellness.', 'icon' => 'fa-heart-pulse', 'position' => 6],
            ['name' => 'Youth Leadership', 'description' => 'Young change-makers shaping Africa tomorrow.', 'icon' => 'fa-people-group', 'position' => 7],
            ['name' => 'Arts, Culture & Heritage', 'description' => 'Cultural custodians preserving and celebrating Africa heritage.', 'icon' => 'fa-masks-theater', 'position' => 8],
            ['name' => 'Education and Skills Development', 'description' => 'Knowledge builders expanding education and skills access.', 'icon' => 'fa-graduation-cap', 'position' => 9],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                $category + [
                    'max_nominees' => 10,
                    'is_active' => true,
                    'created_by' => $admin->id,
                ]
            );
        }

        // Create default voting phases
        $phases = [
            [
                'name' => 'Nomination Phase',
                'description' => 'Accept nominations from the public',
                'phase_type' => 'nomination',
                'start_date' => now(),
                'end_date' => now()->addDays(7),
                'is_active' => true,
            ],
            [
                'name' => 'Evaluation Phase',
                'description' => 'Committee evaluates nominations',
                'phase_type' => 'evaluation',
                'start_date' => now()->addDays(7),
                'end_date' => now()->addDays(14),
                'is_active' => false,
            ],
            [
                'name' => 'Voting Phase',
                'description' => 'Public voting for nominees',
                'phase_type' => 'voting',
                'start_date' => now()->addDays(14),
                'end_date' => now()->addDays(21),
                'is_active' => false,
            ],
            [
                'name' => 'Results Phase',
                'description' => 'Announce winners',
                'phase_type' => 'results',
                'start_date' => now()->addDays(21),
                'end_date' => now()->addDays(22),
                'is_active' => false,
            ],
        ];

        foreach ($phases as $phase) {
            VotingPhase::firstOrCreate(['phase_type' => $phase['phase_type']], $phase);
        }
    }
}
