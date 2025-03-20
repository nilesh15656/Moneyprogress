<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Epin extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'user_id',
        'pin',
        'status',
        'requested_at',
        'approved_at',
        'used',
        'payment_id',
        'approved_by'
    ];

    protected $auditInclude = [
        'user_id', 'pin', 'status', 'referrer_id', 'approved_at','used','payment_id','approved_by'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

}
