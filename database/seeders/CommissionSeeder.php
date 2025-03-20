<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Commission;

class CommissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $commissions = [
            [
                'level_id' => 1, 
                'package_id' => 1, 
                'deductions' => json_encode([
                    'main' => 40,
                    'upgrade' => 20,
                    'total' => 60, 
                ]),
            ],
            [
                'level_id' => 2, 
                'package_id' => 1, 
                'deductions' => json_encode([
                    'main' => 24,
                    'upgrade' => 16, 
                    'total' => 40, 
                ]),
            ],
            [
                'level_id' => 3,
                'package_id' => 1,
                'deductions' => json_encode([
                    'main' => 28,
                    'upgrade' => 12, 
                    'total' => 30, 
                ]),
            ],

            [
                'level_id' => 1, 
                'package_id' => 2, 
                'deductions' => json_encode([
                    'main' => 400,
                    'upgrade' => 200,
                    'total' => 600,
                ]),
            ],
            [
                'level_id' => 2, 
                'package_id' => 2, 
                'deductions' => json_encode([
                    'main' => 240,
                    'upgrade' => 160, 
                    'total' => 400, 
                ]),
            ],
            [
                'level_id' => 3,
                'package_id' => 2,
                'deductions' => json_encode([
                    'main' => 280,
                    'upgrade' => 120, 
                    'total' => 400, 
                ]),
            ],
            
            [
                'level_id' => 1, 
                'package_id' => 3, 
                'deductions' => json_encode([
                    'main' => 4000,
                    'upgrade' => 2000,
                    'total' => 6000,
                ]),
            ],
            [
                'level_id' => 2, 
                'package_id' => 3, 
                'deductions' => json_encode([
                    'main' => 2400,
                    'upgrade' => 1600, 
                    'total' => 4000, 
                ]),
            ],
            [
                'level_id' => 3,
                'package_id' => 3,
                'deductions' => json_encode([
                    'main' => 2800,
                    'upgrade' => 1200, 
                    'total' => 4000, 
                ]),
            ],
            
            [
                'level_id' => 1, 
                'package_id' => 4, 
                'deductions' => json_encode([
                    'main' => 20000,
                    'upgrade' => 20000,
                    'total' => 60000,
                ]),
            ],
            [
                'level_id' => 2, 
                'package_id' => 4, 
                'deductions' => json_encode([
                    'main' => 24000,
                    'upgrade' => 16000, 
                    'total' => 40000, 
                ]),
            ],
            [
                'level_id' => 3,
                'package_id' => 4,
                'deductions' => json_encode([
                    'main' => 28000,
                    'upgrade' => 12000, 
                    'total' => 40000, 
                ]),
            ],
            
            [
                'level_id' => 1, 
                'package_id' => 5, 
                'deductions' => json_encode([
                    'main' => 400000,
                    'upgrade' => 200000,
                    'total' => 600000,
                ]),
            ],
            [
                'level_id' => 2, 
                'package_id' => 5, 
                'deductions' => json_encode([
                    'main' => 240000,
                    'upgrade' => 160000, 
                    'total' => 400000, 
                ]),
            ],
            [
                'level_id' => 3,
                'package_id' => 5,
                'deductions' => json_encode([
                    'main' => 280000,
                    'upgrade' => 120000, 
                    'total' => 400000, 
                ]),
            ],
        ];

        foreach ($commissions as $key => $commission) {
            Commission::updateOrCreate([
                'level_id' => $commission['level_id'],
                'package_id' => $commission['package_id'],
            ],[
                'deductions' => $commission['deductions'],
            ]);
        }
    }
}
