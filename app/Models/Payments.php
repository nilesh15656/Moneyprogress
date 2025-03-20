<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Payments extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = ['user_id', 'amount', 'paid_type', 'paid_for', 'requested_at', 'receipt', 'status', 'requested_at', 'approved_at', 'approved_by', 'package_id', 'upgrade_status', 'tds', 'admin_charge', 'main_amount', 'extra_json', 'description', 'tds_amount'];

    protected $auditInclude = [
        'user_id', 'amount', 'paid_type', 'paid_for', 'status', 'requested_at', 'approved_by', 'package_id', 'upgrade_status', 'tds', 'admin_charge', 'main_amount', 'tds_amount'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
