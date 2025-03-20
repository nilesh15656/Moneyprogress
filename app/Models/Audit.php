<?php

namespace App\Models;

use OwenIt\Auditing\Audit as AuditTrait;
use OwenIt\Auditing\Contracts\Audit as AuditContract;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed auditable_table
 * @property mixed auditable_type
 * @property int id
 */
class Audit extends Model implements AuditContract
{
    use AuditTrait;

    /**
     * Specify the connection, since this implements multitenant solution
     * Called via constructor to faciliate testing
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
        $this->setConnection(config('database.audit_connection'));
    }
   
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'auditable_id',        
        'auditable_type',
        'event',
        'ip_address',
        'new_values',
        'old_values',
        'tags',
        'url',
        'user_agent',
        'user_id',
        'user_type',
        'updated_at',
    ];
}