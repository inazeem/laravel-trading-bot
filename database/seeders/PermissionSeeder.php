<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // User management permissions
        Permission::create(['name' => 'view users']);
        Permission::create(['name' => 'create users']);
        Permission::create(['name' => 'edit users']);
        Permission::create(['name' => 'delete users']);

        // Role management permissions
        Permission::create(['name' => 'view roles']);
        Permission::create(['name' => 'create roles']);
        Permission::create(['name' => 'edit roles']);
        Permission::create(['name' => 'delete roles']);

        // Permission management permissions
        Permission::create(['name' => 'view permissions']);
        Permission::create(['name' => 'create permissions']);
        Permission::create(['name' => 'edit permissions']);
        Permission::create(['name' => 'delete permissions']);

        // Admin panel permissions
        Permission::create(['name' => 'access admin panel']);
        Permission::create(['name' => 'view dashboard']);

        // Content management permissions
        Permission::create(['name' => 'view content']);
        Permission::create(['name' => 'create content']);
        Permission::create(['name' => 'edit content']);
        Permission::create(['name' => 'delete content']);
        Permission::create(['name' => 'publish content']);

        // System permissions
        Permission::create(['name' => 'manage settings']);
        Permission::create(['name' => 'view logs']);
        Permission::create(['name' => 'backup system']);
    }
}
