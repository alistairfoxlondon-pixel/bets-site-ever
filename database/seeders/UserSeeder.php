<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(trim((string) config('adready.bootstrap.admin_email')));
        $password = (string) config('adready.bootstrap.admin_password');

        $admin = null;

        if ($email === '' || strlen($password) < 20) {
            $this->command?->warn('Bootstrap administrator was not seeded. Set ADREADY_BOOTSTRAP_ADMIN_EMAIL and a unique ADREADY_BOOTSTRAP_ADMIN_PASSWORD of at least 20 characters, then re-run the seeder.');

            if (! app()->environment(['local', 'testing'])) {
                return;
            }
        } else {
            $admin = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => (string) config('adready.bootstrap.admin_name', 'AdReady Administrator'),
                    'password' => Hash::make($password),
                    'role' => User::ROLE_ADMIN,
                    'status' => User::STATUS_ACTIVE,
                    'email_verified_at' => now(),
                    'country' => 'Bangladesh',
                    'currency' => 'USD',
                ]
            );

            if (! $admin->wasRecentlyCreated) {
                $this->command?->info('The configured bootstrap administrator already exists; existing role, status and credentials were left unchanged.');
            }
        }

        if (! app()->environment(['local', 'testing'])) {
            $demoEmail = trim((string) config('adready.bootstrap.demo_email'));
            $demoPassword = (string) config('adready.bootstrap.demo_password');

            if ($demoEmail !== '' || $demoPassword !== '') {
                $this->command?->warn('Demo accounts are disabled outside local and testing environments.');
            }

            return;
        }

        $demoEmail = strtolower(trim((string) config('adready.bootstrap.demo_email')));
        $demoPassword = (string) config('adready.bootstrap.demo_password');

        if ($demoEmail === '' && $demoPassword === '') {
            $this->command?->info('No local demo account configured; set ADREADY_DEMO_EMAIL and ADREADY_DEMO_PASSWORD to seed one.');

            return;
        }

        if ($demoEmail === '' || strlen($demoPassword) < 20) {
            $this->command?->warn('Local demo account was not seeded. Set both ADREADY_DEMO_EMAIL and a unique ADREADY_DEMO_PASSWORD of at least 20 characters.');

            return;
        }

        $demo = User::firstOrCreate(
            ['email' => $demoEmail],
            [
                'name' => (string) config('adready.bootstrap.demo_name', 'Demo Publisher'),
                'password' => Hash::make($demoPassword),
                'role' => User::ROLE_USER,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
                'country' => 'Bangladesh',
                'currency' => 'USD',
                'referred_by' => $admin->id,
            ]
        );

        if ($demo->wasRecentlyCreated) {
            $demo->grantCredits(12, 'bonus', 'Local demonstration account credits');
        }
    }
}
