<?php

namespace Database\Seeders;

use App\Models\BusinessRole;
use Illuminate\Database\Seeder;

class BusinessRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Owner', 'slug' => 'owner', 'sort_order' => 1],
            ['name' => 'Admin', 'slug' => 'admin', 'sort_order' => 2],
            ['name' => 'Staff', 'slug' => 'staff', 'sort_order' => 3],
        ];

        foreach ($roles as $role) {
            BusinessRole::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
