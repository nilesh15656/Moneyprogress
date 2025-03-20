<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Level;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $levels = [
            [
                'name' => 'Level 1',
                'max_referrer' => 5, 
            ],
            [
                'name' => 'Level 2',
                'max_referrer' => 25, 
            ],
            [
                'name' => 'Level 3',
                'max_referrer' => 125, 
            ],
        ];

        foreach ($levels as $key => $level) {
            Level::updateOrCreate([
                'name' => $level['name'],
                'max_referrer' => $level['max_referrer'],
            ]);
        }
    }
}
