<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::updateOrCreate([
            'name' => 'Super Admin',
            'email' => 'superadmin@mailinator.com'
        ],[
            'password' => Hash::make('superadmin@123'),
            'referrer_id' => NULL,
            'level_id' => 1,
            'package_id' => NULL,
            'is_active' => 1,
            'email_verified_at' => now(),
        ]);

        $super_admin_role = Role::where('slug','superadmin')->first();  
        $user->roles()->detach(); 
        $user->roles()->attach($super_admin_role);

        $user = User::updateOrCreate([
            'name' => 'Admin',
            'email' => 'admin@moneyprogress.in'
        ],[
            'password' => Hash::make('admin@123'),
            'referrer_id' => NULL,
            'level_id' => 1,
            'package_id' => NULL,
            'is_active' => 1,
            'email_verified_at' => now(),
        ]);

        $admin_role = Role::where('slug','admin')->first();  
        $user->roles()->detach();  
        $user->roles()->attach($admin_role);


        $user = User::updateOrCreate([
            'name' => 'Company User',
            'email' => 'moneyprogress910@gmail.com'
        ],[
            'password' => Hash::make('money@1234'),
            'referrer_id' => NULL,
            'level_id' => 1,
            'package_id' => 1,
            'is_active' => 1,
            'email_verified_at' => now(),
        ]);

        $user_role = Role::where('slug','user')->first();  
        $user->roles()->detach();  
        $user->roles()->attach($user_role);
    }
}
