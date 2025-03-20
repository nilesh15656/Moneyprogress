<?php

namespace App\Listeners;

use App\Events\UpdateWallet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;
use App\Models\Commission;
use App\Models\Wallet;
use App\Models\Level;

class UpdateWalletListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UpdateWallet  $event
     * @return void
     */
    public function handle(UpdateWallet $event)
    {
        $newJoin = $user = $event->user;
        $referrer = $newJoin->referrer ?? [];

        /*
            Check upline to upgdate parent 3 users
        */
        if($user && isset($referrer->id)){

            // (C) Level 3
            if(isset($user->parent)){
                \Log::channel('wallet-log')->info('------1 Upline ::user_id '.$user->parent->id);
                updateWallet($newJoin, $user = $user->parent, 1);
            }

            // (B) Level 2
            if(isset($user->parent)){
                \Log::channel('wallet-log')->info('------2 Upline ::user_id '.$user->parent->id);
                updateWallet($newJoin, $user = $user->parent, 2);
            }

            // (A) Level 1
            if(isset($user->parent)){
                \Log::channel('wallet-log')->info('------3 Upline ::user_id '.$user->parent->id);
                updateWallet($newJoin, $user = $user->parent, 3);
            }
        }
    }
}
