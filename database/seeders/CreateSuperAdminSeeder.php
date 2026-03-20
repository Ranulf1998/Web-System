<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class CreateSuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create superadmin with tenant_id = null (no tenant affiliation)
        User::on('central')->firstOrCreate(
            ['email' => 'admin@brewcloud.test'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'tenant_id' => null,
            ]
        );

        echo "Superadmin account created:\n";
        echo "Email: admin@brewcloud.test\n";
        echo "Password: password\n";
    }
}
