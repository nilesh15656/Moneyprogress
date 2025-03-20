<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'refer_a_user' => 'User can refer to other users',
            'withdraw_money' => 'User can withdraw money',
            'edit-users' => 'User can edit users',
            'update-users' => 'User can update users',
        ];

        foreach ($permissions as $slug => $name) {
            Permission::updateOrCreate([
                'slug' => $slug, 
                'name' => $name
            ]);
        }

    }
}
