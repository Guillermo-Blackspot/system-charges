<?php

namespace BlackSpot\SystemCharges\Models;

use BlackSpot\ServiceIntegrationsContainer\Models\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\SystemCharges\Concerns\ManagesCredentials;
use BlackSpot\SystemCharges\Models\SystemPaymentIntent;
use BlackSpot\SystemCharges\Models\SystemSubscriptionItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'trial_ends_at'          => 'datetime',
        'cancel_at'              => 'datetime',
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['system_subscription_items'];
    

    public const SYSTEM_CHARGES_SERVICE_NAME = 'System_Charges';
    public const STATUS_ACTIVE               = 'active';
    public const STATUS_INCOMPLETE           = 'incomplete';
    public const STATUS_INCOMPLETE_EXPIRED   = 'incomplete_expired';
    public const STATUS_PAST_DUE             = 'past_due';
    public const STATUS_UNPAID               = 'unpaid';
    public const STATUS_CANCELED             = 'canceled';    
    public const STATUS_PAUSED               = 'paused';
    public const STATUS_UNLINKED             = 'unlinked';

    /**
     * Overwrite cast json method
     */
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Helpers
     */
    public static function resolveStatusDescription($status)
    {
        switch ($status) {
            case self::STATUS_INCOMPLETE : return 'Primer cobro falló';                           break;
            case self::STATUS_INCOMPLETE : return 'Primer cobro falló y ya no puede reactivarse'; break;
            case self::STATUS_ACTIVE     : return 'Activo';                                       break;
            case self::STATUS_PAST_DUE   : return 'La renovación falló';                          break;
            case self::STATUS_CANCELED   : return 'Cancelado';                                    break;
            case self::STATUS_UNPAID     : return 'No pagado, acumulando facturas';               break;
            case self::STATUS_PAUSED     : return 'Pausado';                                      break;
            case self::STATUS_UNLINKED   : return 'Desvinculado';                                 break;
            default                      : return 'Desconocido';                                  break;
        }
    }
    /**
     * Accessors
     */
    public function getStatusDescriptionAttribute()
    {
        return static::resolveStatusDescription($this->status);
    }

    /**
     * Determine if the subscription is within its trial period.
     *
     * Automatically takes the service_integration provider (Stripe, another)
     * 
     * @return bool
     */
    public function onTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Determine if the subscription has multiple prices.
     *
     * @return bool
     */
    public function hasMultiplePrices()
    {
        return $this->system_subscription_items->count() > 1;
    }

    /**
     * Determine if the subscription has a single price.
     *
     * @return bool
     */
    public function hasSinglePrice()
    {
        return ! $this->hasMultiplePrices();
    }
    
    /**
     * Determine if the subscription's trial has expired.
     *
     * @return bool
     */
    public function hasExpiredTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Determine if the subscription is incomplete.
     *
     * @return bool
     */
    public function incomplete()
    {
        return $this->status === self::STATUS_INCOMPLETE;
    }

    public function incompleteExpired()
    {
        return $this->status === self::STATUS_INCOMPLETE_EXPIRED;
    }

    /**
     * Determine if the subscription is past due.
     *
     * @return bool
     */
    public function pastDue()
    {
        return $this->status === self::STATUS_PAST_DUE;
    }

    /**
     * Determine if the subscription is no longer active.
     *
     * @return bool
     */
    public function canceled()
    {
        return $this->status == self::STATUS_CANCELED;
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        return !$this->ended() &&
            $this->status !== self::STATUS_UNLINKED &&
            $this->status !== self::STATUS_INCOMPLETE &&
            $this->status !== self::STATUS_INCOMPLETE_EXPIRED &&
            $this->status !== self::STATUS_PAST_DUE &&
            $this->status !== self::STATUS_UNPAID;
    }

    /**
     * Determine if the subscription has ended and the grace period has expired.
     *
     * @return bool
     */
    public function ended()
    {
        return $this->canceled() && !$this->onGracePeriod();
    }

    public function paused()
    {
        return $this->status == self::STATUS_PAUSED;
    }

    public function unlinked()
    {
        return ! isset($this->owner) || ! isset($this->owner_id) || $this->status == self::STATUS_UNLINKED;
    }

    /**
     * Determine if the subscription is active, on trial, or within its grace period.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }    

    /**
     * Determine if the subscription has an incomplete payment.
     *
     * @return bool
     */
    public function hasIncompletePayment()
    {
        return $this->pastDue() || $this->incomplete();
    }

    /**
     * Determine if the subscription is within its grace period
     * 
     * The dead line of the subscription is future
     *
     * @return bool
     */
    public function onGracePeriod()
    {
        return $this->current_period_ends_at && $this->current_period_ends_at->isFuture();
    }


    /**
     * Set owner as null
     * 
     * Use this function if you need delete a subscription 
     * but preserving itself on the record with payment_intents
     * 
     * subscription status will be "canceled"
     *
     * @return void
     */
    public function unlinkNow()
    {
        $this->update([
            'owner_id'   => null,
            'owner_type' => null,
            'status'     => self::STATUS_UNLINKED,
        ]);


        DB::table(SystemPaymentIntent::TABLE_NAME)
            ->where('system_subscription_id', $this->id)
            ->update([
                'owner_id'   => null,
                'owner_type' => null,
            ]);
    }

    /**
     * Set the subscription status on "canceled"
     *
     * @return void
     */
    public function cancelNow()
    {
        $this->update([
            'status' => self::STATUS_CANCELED
        ]);
    }

    /**
     * Pause subscription
     * 
     * @return void
     */
    public function pauseNow()
    {
        $this->update([
            'status' => self::STATUS_PAUSED
        ]);
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
     * Get the owner of this subscription
     * 
     * @return object
     */
    public function system_payment_intents()
    {
        return $this->hasMany(ServiceIntegrationsContainerProvider::getFromConfig('system_charges_models.payment_intent', SystemPaymentIntent::class), 'system_subscription_id');   
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

    /**
     * Get the service_integration that owns the SystemSubscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service_integration()
    {
        return $this->belongsTo(ServiceIntegrationsContainerProvider::getFromConfig('model', ServiceIntegration::class), 'service_integration_id');
    }

    /**
     * Scope by service_integration
     * 
     * @param \Illuminate\Database\Query\Builder
     * @param int $serviceIntegrationId
     */
    public function scopeServiceIntegration($query, $serviceIntegrationId)
    {
        return $query->where('service_integration_id', $serviceIntegrationId);
    }
}
