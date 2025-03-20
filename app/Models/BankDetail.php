<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankDetail extends Model
{
    use HasFactory;

    protected $table = 'bank_detail';
    protected $fillable = [
        'user_id',
        'bank_name',
        'bank_type',
        'bank_ac_number',
        'bank_holder_name',
        'bank_ifsc',
        'bank_holder_name',
        'cheque_image',
        'pancard_image',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
