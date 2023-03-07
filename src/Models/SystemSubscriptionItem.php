<?php

namespace BlackSpot\SystemCharges\Models;

use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\SystemCharges\Models\SystemSubscription;
use Illuminate\Database\Eloquent\Model;

class SystemSubscriptionItem extends Model
{   
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_subscription_items';
    public const TABLE_NAME = 'system_subscription_items';

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
        'metadata' => 'array',
    ];

    public const SYSTEM_CHARGES_SERVICE_NAME = 'System_Charges';

    /**
     * Overwrite cast json method
     */
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the model that belongs to this item
     * 
     * @return object
     */
    public function model()
    {
        return $this->morphTo('model');   
    }

    /**
     * Get the system_subscription that owns the SystemSubscriptionItem
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function system_subscription()
    {
        return $this->belongsTo(ServiceIntegrationsContainerProvider::getFromConfig('system_charges_models.subscription', SystemSubscription::class), 'sytem_subscription_id');
    }
}
