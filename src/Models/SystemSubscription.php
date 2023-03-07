<?php

namespace BlackSpot\SystemCharges\Models;

use BlackSpot\ServiceIntegrationsContainer\Models\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\SystemCharges\Concerns\ManagesCredentials;
use BlackSpot\SystemCharges\Models\SystemSubscriptionItem;
use Illuminate\Database\Eloquent\Model;

class SystemSubscription extends Model
{
    use ManagesCredentials;
    
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
     * Get all of the system_subscription_items for the SystemSubscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function system_subscription_items()
    {
        return $this->hasMany(ServiceIntegrationsContainerProvider::getFromConfig('system_charges_models.subscription_item', SystemSubscriptionItem::class), 'system_subscription_id');
    }

    public function service_integration()
    {
        return $this->belongsTo(ServiceIntegrationsContainerProvider::getFromConfig('model', ServiceIntegration::class), 'service_integration_id');
    }
}
