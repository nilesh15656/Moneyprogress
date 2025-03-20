<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Wallet extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'user_id', 'upgrade', 'main', 'referrer_id', 'withdraw', 'tds', 'admin_charge'
    ];

    protected $auditInclude = [
        'user_id', 'upgrade', 'main', 'referrer_id', 'withdraw', 'tds', 'admin_charge'
    ];
}
