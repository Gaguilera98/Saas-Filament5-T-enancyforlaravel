<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuario del panel central (/admin). Idempotente por email.
 *
 * php artisan db:seed --class=AdminUserSeeder
 */
final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => '12345678',
            ],
        );

        $this->command?->info('Usuario central: admin@admin.com (contraseña en el seeder).');
    }
}
