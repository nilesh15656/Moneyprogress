<?php

namespace App\Listeners;

use App\Events\BulkEpin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;
use App\Models\Epin;
use App\Models\Payments;

class BulkEpinListener
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
     * @param  \App\Events\BulkEpin  $event
     * @return void
     */
    public function handle(BulkEpin $event)
    {
        $payment = $event->payment;
        $user = $payment->user;
        $epin = $payment->amount/100;
        for ($i=0; $i < (int) $epin; $i++) { 
            Epin::create([
                'user_id' => $payment->user_id,
                'pin' => $this->generateEpin(),
                'status' => 'active',
                'approved_at' => now(),
                'approved_by' => \Auth::user()->id,
                'payment_id' => $payment->id,
                'requested_at' => $payment->created_at,
            ]);
        }
    }

    public function generateEpin()
    {
        $pin = Str::random(12);
        if (Epin::where('pin',$pin)->count() > 0) self::generateEpin();
        return $pin;
    }
}
