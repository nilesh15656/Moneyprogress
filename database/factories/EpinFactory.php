<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;

class EpinFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $statues = ['pending','approved'];
        $status = $statues[array_rand($statues, 1)];
        $users = User::where('is_epin_requested','=',1)->doesntHave('epinRequest')->pluck('id');
        return [
            'user_id' => $this->faker->randomElement($users),
            'status' => $status,
            'pin' => ($status == 'approved') ? Str::random(5) : null,
            'requested_at' => now(),
            'approved_at' => ($status == 'approved') ? now() : null,
        ];
    }
}
