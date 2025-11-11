<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Pertama, pastikan semua permission sudah dibuat
        $this->call(PermissionsTableSeeder::class);

        // Buat role admin jika belum ada
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        // Assign semua permission ke role admin
        $allPermissions = Permission::all();
        $adminRole->syncPermissions($allPermissions);

        $this->command->info('Role admin berhasil dibuat dan semua permission telah di-assign.');

        // Cari atau buat user admin
        $adminEmail = 'admin@gmail.com'; // Ganti dengan email admin yang diinginkan
        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'), // Ganti dengan password yang diinginkan
                'email_verified_at' => now(),
            ]
        );

        // Assign role admin ke user admin
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
            $this->command->info("User {$adminEmail} berhasil di-assign ke role admin.");
        } else {
            $this->command->info("User {$adminEmail} sudah memiliki role admin.");
        }

        // Jika ingin assign role admin ke user yang sudah ada berdasarkan email tertentu
        // Uncomment baris di bawah dan ganti dengan email user yang ingin di-assign
        /*
        $userEmail = 'user@example.com'; // Ganti dengan email user yang ingin di-assign
        $user = User::where('email', $userEmail)->first();
        if ($user && !$user->hasRole('admin')) {
            $user->assignRole('admin');
            $this->command->info("User {$userEmail} berhasil di-assign ke role admin.");
        }
        */
    }
}

