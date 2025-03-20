<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            'superadmin' => 'Superadmin_Admin_Name',
            'admin' => 'Admin_Name',
            'user' => 'User_Name'
        ];

        foreach ($roles as $slug => $name) {
            Role::updateOrCreate([
                'slug' => $slug, 
                'name' => $name
            ]);
        }
    }
}
