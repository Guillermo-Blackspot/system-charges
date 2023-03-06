<?php

namespace BlackSpot\SystemCharges\Models;

use App\Models\Charges\SystemSubscriptionItem;
use App\Models\Subsidiary\Subsidiary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSubscription extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_subscriptions';
    public const TABLE_NAME = 'system_subscriptions';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'metadata'               => 'array',
        'billing_cycle_anchor'   => 'datetime',
        'current_period_start'   => 'datetime',
        'current_period_ends_at' => 'datetime',
        'cancel_at'              => 'datetime',
    ];

    public const SYSTEM_CHARGES_SERVICE_NAME = 'System_Charges';


    public const STATUS_INCOMPLETE         = 'incomplete';
    public const STATUS_INCOMPLETE_EXPIRED = 'incomplete_expired';
    public const STATUS_PAST_DUE           = 'past_due';
    public const STATUS_UNPAID             = 'unpaid';
    public const STATUS_CANCELED           = 'canceled';    

    /**
     * Overwrite cast json method
     */
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the owner of this subscription
     * 
     * @return object
     */
    public function owner()
    {
        return $this->morphTo('owner');   
    }

    /**
     * Get the subsidiary that owns the SystemSubscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subsidiary()
    {
        return $this->belongsTo(Subsidiary::class, 'subsidiary_id');
    }

    /**
     * Get all of the system_subscription_items for the SystemSubscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function system_subscription_items()
    {
        return $this->hasMany(SystemSubscriptionItem::class, 'system_subscription_id');
    }
}
