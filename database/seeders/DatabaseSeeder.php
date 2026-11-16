<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * İlk rəhbərlik hesabını yaradır (yalnız heç bir istifadəçi yoxdursa).
     * Məlumatlar .env faylından götürülür: ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD.
     */
    public function run(): void
    {
        if (User::exists()) {
            $this->command?->info('İstifadəçilər artıq mövcuddur, admin yaradılmadı.');

            return;
        }

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        if (! $email || ! $password) {
            $this->command?->error('.env faylında ADMIN_EMAIL və ADMIN_PASSWORD yazın.');

            return;
        }

        User::create([
            'name' => env('ADMIN_NAME', 'Rəhbər'),
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
            'position' => env('ADMIN_POSITION', 'Rəqəmsal marketinq rəhbəri'),
            'is_active' => true,
        ]);

        $this->command?->info("Rəhbərlik hesabı yaradıldı: {$email}");
    }
}
