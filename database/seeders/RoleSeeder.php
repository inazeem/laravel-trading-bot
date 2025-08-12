<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin role
        $superAdmin = Role::create(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Create Admin role
        $admin = Role::create(['name' => 'Admin']);
        $admin->givePermissionTo([
            'view users', 'create users', 'edit users',
            'view roles', 'create roles', 'edit roles',
            'view permissions', 'create permissions', 'edit permissions',
            'access admin panel', 'view dashboard',
            'view content', 'create content', 'edit content', 'delete content', 'publish content',
            'manage settings', 'view logs'
        ]);

        // Create Manager role
        $manager = Role::create(['name' => 'Manager']);
        $manager->givePermissionTo([
            'view users',
            'view roles',
            'view permissions',
            'access admin panel', 'view dashboard',
            'view content', 'create content', 'edit content', 'publish content'
        ]);

        // Create Editor role
        $editor = Role::create(['name' => 'Editor']);
        $editor->givePermissionTo([
            'access admin panel', 'view dashboard',
            'view content', 'create content', 'edit content', 'publish content'
        ]);

        // Create User role
        $user = Role::create(['name' => 'User']);
        $user->givePermissionTo([
            'view content'
        ]);
    }
}
