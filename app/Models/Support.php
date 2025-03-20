<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Support extends Model
{
    use HasFactory;

    protected $table = 'support';
    protected $fillable = [
        'user_id',
        'subject',
        'description',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function reply()
    {
        return $this->hasMany(SupportMessage::class, 'support_id', 'id');
    }
}
