<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Pereaksi Policy
            'view_any_pereaksi',
            'view_pereaksi',
            'create_pereaksi',
            'update_pereaksi',
            'delete_pereaksi',
            'delete_any_pereaksi',

            // RestockHistory Policy
            'view_any_restock::history',
            'view_restock::history',
            'create_restock::history',
            'update_restock::history',
            'delete_restock::history',
            'delete_any_restock::history',

            // Role Policy (Shield)
            'view_any_shield::role',
            'view_shield::role',
            'create_shield::role',
            'update_shield::role',
            'delete_shield::role',
            'delete_any_shield::role',

            // Setting Policy
            'view_any_setting',
            'view_setting',
            'create_setting',
            'update_setting',
            'delete_setting',
            'delete_any_setting',

            // Summary Policy
            'view_any_summary',
            'view_summary',
            'create_summary',
            'update_summary',
            'delete_summary',
            'delete_any_summary',

            // UsageHistory Policy
            'view_any_usage::history',
            'view_usage::history',
            'create_usage::history',
            'update_usage::history',
            'delete_usage::history',
            'delete_any_usage::history',

            // User Policy
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
            'delete_any_user',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
