<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if the user with the specified username already exists
        $existingUser = User::where('username', 'admin')->first();

        if (!$existingUser) {
            // User doesn't exist, so create it
            $adminUser = User::create([
                'name' => 'Admin',
                'username' => 'admin',
                'password' => bcrypt('iddrivesadmin'), // Use bcrypt() to hash the password
                'agn' => '',
                'brn' => '',
                'role' => 'admin',
                'icon' => '',
            ]);

            if (!($adminUser->hasRole('admin'))) {
                $adminUser->assignRole('admin');
            }

            $this->command->info("User Admin created successfully.");
        } else {
            if (!($existingUser->hasRole('admin'))) {
                $existingUser->assignRole('admin');
            }
            // User already exists, display a message
            $this->command->info("User Admin already exists.");
        }
    }
}
