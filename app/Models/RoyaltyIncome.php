<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoyaltyIncome extends Model
{
    use HasFactory;

    protected $table = 'royalty_income';
    protected $fillable = [
        'user_id',
        'amount'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
