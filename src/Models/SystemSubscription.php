<?php

namespace BlackSpot\SystemCharges\Models;

use BlackSpot\ServiceIntegrationsContainer\Models\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\SystemCharges\Concerns\ManagesCredentials;
use BlackSpot\SystemCharges\Models\SystemPaymentIntent;
use BlackSpot\SystemCharges\Models\SystemSubscriptionItem;
use BlackSpot\SystemCharges\SubscriptionUtils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

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
        'billing_cycle_anchor'   => 'datetime:Y-m-d H:i:s',
        'current_period_start'   => 'datetime:Y-m-d H:i:s',
        'current_period_ends_at' => 'datetime:Y-m-d H:i:s',
        'trial_ends_at'          => 'datetime:Y-m-d H:i:s',
        'cancel_at'              => 'datetime:Y-m-d H:i:s',
        'keep_products_active_until' => 'datetime:Y-m-d H:i:s',
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
    public const STATUS_TRAILING             = 'trialing';

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
            case self::STATUS_INCOMPLETE         : return 'Primer cobro falló';                           break;
            case self::STATUS_INCOMPLETE_EXPIRED : return 'Primer cobro falló y ya no puede reactivarse'; break;
            case self::STATUS_ACTIVE             : return 'Activo';                                       break;
            case self::STATUS_PAST_DUE           : return 'La renovación falló';                          break;
            case self::STATUS_CANCELED           : return 'Cancelado';                                    break;
            case self::STATUS_UNPAID             : return 'No pagado, acumulando facturas';               break;
            case self::STATUS_PAUSED             : return 'Pausado';                                      break;
            case self::STATUS_UNLINKED           : return 'Desvinculado';                                 break;
            case self::STATUS_TRAILING           : return 'En periodo de prueba';                         break;
            default                              : return 'Desconocido';                                  break;
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
     * Determine if the subscription has multiple prices.
     *
     * @return bool
     */
    public function hasMultipleItems()
    {
        return $this->system_subscription_items->count() > 1;
    }

    /**
     * Determine if the subscription has a single price.
     *
     * @return bool
     */
    public function hasSingleItem()
    {
        return ! $this->hasMultipleItems();
    }

    /**
     * Determine if the subscription has a given status
     *
     * @param string $status
     * 
     * @return bool
     */
    public function hasStatus($status)
    {
        return $this->status === $status;
    }

    /**
     * Get the first subscription item
     *
     * @return \BlackSpot\SystemCharges\Models\SystemSubscriptionItem
     */
    public function firstItem()
    {
        return $this->system_subscription_items->first();
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

    /**
     * Determine if the subscription is incomplete expired
     *
     * @return bool
     */
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
        return ($this->cancel_at && $this->cancel_at->isPast()) ||  $this->status == self::STATUS_CANCELED;
    }

    /**
     * Determine if the subscription is in pause
     *
     * @return bool
     */
    public function paused()
    {
        return $this->status == self::STATUS_PAUSED;
    }

    /**
     * Determine if the subscription is unlinked
     *
     * @return bool
     */
    public function unlinked()
    {
        return ! isset($this->owner) || ! isset($this->owner_id) || $this->status == self::STATUS_UNLINKED;
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
     * Create a susbcription invoice if not exists with the give date
     *
     * @param \DateTimeInterface  $date
     * 
     * @return \BlackSpot\Models\SystemPaymentIntent
     * 
     * @throws \LogicException
     */
    public function createInvoiceIfNotExists($date)
    {
        $invoiceExists = $this->system_payment_intents->some(fn ($invoice) => $invoice->created_at->eq($date));

        if (! $invoiceExists) {
            $lastInvoice          = $this->system_payment_intents->sortBy(fn ($invoice) => $invoice->created_at)->values()->last();
            $lastInvoiceNumber    = $lastInvoice->metadata['invoice_number'];
            $currentInvoiceNumber = $lastInvoiceNumber + 1;
            $metadata             = array_merge($lastInvoice->metadata, ['invoice_number' => $currentInvoiceNumber]);

            return $this->createInvoiceOf($date, $lastInvoice->payment_method, $lastInvoice->amount, [
                'metadata' => $metadata
            ]);
        } 

        return null;
    }

    /**
     * Create a subscription invoice with the given date
     *
     * @param \DateTimeInterface  $date
     * @param string  $paymentMethod
     * @param string|int  $amount
     * @return void
     * 
     * @throws \LogicException
     */
    public function createInvoiceOf($date, $paymentMethod, $amount, $options)
    {    
        if (! isset($this->owner)) {
            throw new LogicException("You cant create an subscription invoice if the owner not exists", 1);
        }

        $options = array_merge([
            'system_subscription_id'  => $this->id,
            'subscription_identifier' => $this->identified_by,
            'subscription_name'       => $this->name,
            'owner_name'              => $this->owner->full_name ?? $this->owner->name,
            'owner_email'             => $this->owner->email,
            'created_at'              => $date,
        ], $options);

        $this->owner->createSystemPaymentIntentWith($this->service_integration_id, $paymentMethod, $amount, $options);
    }

    /**
     * Move the subscription period to next from the current_period_ends_at
     *
     * @return $this
     */
    public function moveToNextPeriod()
    {
        $newCurrentPeriodEndsAt =  SubscriptionUtils::calculateNextEndPeriod(
            $this->recurring_interval,
            $this->recurring_interval_count,
            $this->current_period_ends_at
        );

        $this->update([
            'current_period_start'   => $this->current_period_ends_at,
            'current_period_ends_at' => $newCurrentPeriodEndsAt
        ]);

        return $this;
    }

    /**
     * Move the subscription period to next from the current_period_ends_at
     *
     * @return \BlackSpot\Models\SystemPaymentIntent|null
     * 
     * @throws \LogicException
     */
    public function moveToNextPeriodAndInvoice()
    {
        if (! isset($this->owner)) {
            throw new LogicException("You cant move to the next period if the owner not exists", 1);
        }

        $invoiceCreatedAt     = $this->current_period_ends_at;
        $lastPaymentIntent    = $this->system_payment_intents->sortBy(fn ($invoice) => $invoice->created_at)->values()->last();
        $lastInvoiceNumber    = $lastPaymentIntent->metadata['invoice_number'];
        $currentInvoiceNumber = $lastInvoiceNumber + 1;
        $metadata             = array_merge($lastPaymentIntent->metadata, ['invoice_number' => $currentInvoiceNumber]);

        $this->moveToNextPeriod();

        return $this->owner->createSystemPaymentIntentWith($this->service_integration_id, $lastPaymentIntent->payment_method, $lastPaymentIntent->amount, [
            'system_subscription_id'  => $this->id,
            'subscription_identifier' => $this->identified_by,
            'subscription_name'       => $this->name,
            'owner_name'              => $this->owner->full_name ?? $this->owner->name,
            'owner_email'             => $this->owner->email,
            'metadata'                => $metadata,
            'created_at'              => $invoiceCreatedAt,
        ]);
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
