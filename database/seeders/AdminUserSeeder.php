<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the admin panel user for local development, or reset its password.
     */
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new LogicException('AdminUserSeeder memakai kata sandi bawaan dan hanya boleh dijalankan di lingkungan lokal.');
        }

        $email = config('admin.email');

        if (blank($email)) {
            $this->command?->warn('ADMIN_EMAIL belum diisi, jadi user admin tidak dibuat.');

            return;
        }

        $admin = User::query()->firstOrNew(['email' => $email], ['name' => 'Admin']);
        $admin->password = 'password';
        $admin->save();
    }
}
