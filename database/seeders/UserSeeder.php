<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $create = 10; 
        $referrer_id = 3;
        for ($i=1; $i <= $create; $i++) { 
            $name = 'referral_'.$referrer_id.'_'.$i;
            $email = $name.'@example.com';
            $parent_id = NULL;;
            try {
                $user = User::create([
                    'referrer_id' => $referrer_id,
                    'mobile' => rand(1111111111,9999999999),
                    'name' => $name,
                    'email' => $email,
                    'email_verified_at' => now(),
                    'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                    'remember_token' => Str::random(10),
                    'package_id' => 1, // basic
                ]);
            } catch (Exception $e) {
                
            }
        }
    }
}
