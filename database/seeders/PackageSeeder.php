<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $packages = [
            [
                'name' => 'Basic',
                'basic_amount' => 200, 
            ],
            [
                'name' => 'Silver',
                'basic_amount' => 2000, 
            ],
            [
                'name' => 'Gold',
                'basic_amount' => 20000, 
            ],
            [
                'name' => 'Diamond',
                'basic_amount' => 200000, 
            ],

            [
                'name' => 'Platinum',
                'basic_amount' => 2000000, 
            ],
        ];

        foreach ($packages as $key => $package) {
            Package::updateOrCreate([
                'name' => $package['name'],
            ],[
                'basic_amount' => $package['basic_amount'],
            ]);
        }
    }
}
