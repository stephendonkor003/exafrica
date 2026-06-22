<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SuperAdminSeeder extends Seeder
{
    public const NAME = 'African Union Super Admin';

    public const EMAIL = 'donkors@africanunion.org';

    private const SUPER_ADMINS = [
        [
            'name' => self::NAME,
            'email' => self::EMAIL,
            'password' => 'Amodon@2063',
        ],
        [
            'name' => self::NAME,
            'email' => 'jnadunga@gmail.com',
            'password' => 'Ex2026@Au',
        ],
    ];

    /**
     * Seed the application's default super admins.
     */
    public function run(): void
    {
        self::createOrUpdateSuperAdmins();
    }

    public static function createOrUpdateSuperAdmin(): User
    {
        return self::createOrUpdateSuperAdmins();
    }

    public static function createOrUpdateSuperAdmins(): User
    {
        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super_admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Full system access',
            ]
        );

        $primaryAdmin = self::createOrUpdateAccount(self::SUPER_ADMINS[0], $superAdminRole->id);

        foreach (array_slice(self::SUPER_ADMINS, 1) as $superAdmin) {
            self::createOrUpdateAccount($superAdmin, $superAdminRole->id);
        }

        self::deactivateOtherSuperAdmins($primaryAdmin, $superAdminRole->id);

        return $primaryAdmin;
    }

    /**
     * @param  array{name: string, email: string, password: string}  $superAdmin
     */
    private static function createOrUpdateAccount(array $superAdmin, int $superAdminRoleId): User
    {
        $admin = User::firstOrNew(['email' => $superAdmin['email']]);

        $admin->fill([
            'name' => $superAdmin['name'],
            'role_id' => $superAdminRoleId,
            'is_active' => true,
        ]);

        if (! $admin->exists || ! Hash::check($superAdmin['password'], (string) $admin->password)) {
            $admin->password = Hash::make($superAdmin['password']);
        }

        $admin->save();

        return $admin;
    }

    private static function deactivateOtherSuperAdmins(User $primaryAdmin, int $superAdminRoleId): void
    {
        $seededEmails = array_column(self::SUPER_ADMINS, 'email');

        $otherSuperAdminIds = User::where('role_id', $superAdminRoleId)
            ->whereNotIn('email', $seededEmails)
            ->pluck('id');

        if ($otherSuperAdminIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('categories')) {
            Category::whereIn('created_by', $otherSuperAdminIds)
                ->update(['created_by' => $primaryAdmin->id]);
        }

        User::whereKey($otherSuperAdminIds)
            ->update(['is_active' => false]);
    }
}
