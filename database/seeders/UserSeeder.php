<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@adready.test'],
            [
                'name' => 'AdReady Admin',
                'password' => Hash::make('ChangeMe!2024'),
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
                'country' => 'Bangladesh',
                'currency' => 'USD',
            ]
        );

        if ((float) $admin->credits_balance < 1) {
            $admin->grantCredits(100, 'bonus', 'Welcome credits for the administrator');
        }

        $user = User::updateOrCreate(
            ['email' => 'demo@adready.test'],
            [
                'name' => 'Demo Publisher',
                'password' => Hash::make('ChangeMe!2024'),
                'role' => User::ROLE_USER,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
                'country' => 'Bangladesh',
                'currency' => 'USD',
                'referred_by' => $admin->id,
            ]
        );

        if ((float) $user->credits_balance === 0.0) {
            $user->grantCredits(12, 'bonus', 'Welcome credits');
        }
    }
}
