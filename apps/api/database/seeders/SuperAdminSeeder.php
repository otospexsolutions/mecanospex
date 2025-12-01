<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SuperAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SuperAdmin::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Super Admin',
            'email' => 'superadmin@mecanospex.com',
            'password' => Hash::make('superadmin123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->command->info('Super admin created: superadmin@mecanospex.com');
    }
}
