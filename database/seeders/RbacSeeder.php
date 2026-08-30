<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

final class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect([
            ['name' => 'Access admin dashboard', 'slug' => 'admin.dashboard.view'],
            ['name' => 'Manage users', 'slug' => 'admin.users.manage'],
            ['name' => 'Manage roles', 'slug' => 'admin.roles.manage'],
            ['name' => 'Manage permissions', 'slug' => 'admin.permissions.manage'],
        ])->mapWithKeys(function (array $permission): array {
            return [
                $permission['slug'] => Permission::query()->firstOrCreate(
                    ['slug' => $permission['slug']],
                    [
                        'name' => $permission['name'],
                        'description' => 'System permission: '.$permission['name'],
                        'is_system' => true,
                    ],
                ),
            ];
        });

        $superAdmin = Role::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Protected platform owner role.',
                'is_system' => true,
                'is_protected' => true,
            ],
        );

        Role::query()->firstOrCreate(
            ['slug' => 'member'],
            [
                'name' => 'Member',
                'description' => 'Default member role.',
                'is_system' => true,
                'is_protected' => false,
            ],
        );

        $superAdmin->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());

        $bootstrapEmail = config('app.bootstrap_admin_email');

        if ($bootstrapEmail) {
            $user = User::query()->where('email', $bootstrapEmail)->first();

            if ($user) {
                $user->roles()->syncWithoutDetaching([$superAdmin->id]);
            }
        }
    }
}
