<?php

namespace BlackSpot\SystemCharges;

use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SubscriptionBuilder
{
    /**
     * The model that is subscribing.
     *
     * @var \App\Services\Vendor\SystemCharges\IsBillableToSystemCharges|\Illuminate\Database\Eloquent\Model
     */
    protected $owner;


    /**
     * The subscription identifier
     * 
     * It needs to be unique by user
     * for prevents that one user can be subscribed many times to same subscriptable item
     *
     * @var string
     */
    protected $subscriptionIdentifier;


    /**
     * The subscription name
     *
     * @var string
     */
    protected $name;

    /**
     * Collection of Service Integration Products 
     * 
     * The products synced to some stripe account
     *
     * @var \Illuminate\Support\Collection <\BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationProduct>
     */
    protected $items;

    /**
     * The metadata to apply to the subscription.
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * The description to apply to the subscription
     *
     * @var string
     */
    protected $description = null;

    /**
     * The curency to apply to the subscription
     *
     * @var string
     */
    protected $currency = 'mxn';

    /**
     * The date on which the billing cycle should be anchored.
     *
     * @var int|null
     */
    protected $billingCycleAnchor = null;

    /**
     * The date and time the trial will expire.
     *
     * @var \Carbon\Carbon|\Carbon\CarbonInterface|null
     */
    protected $trialExpires;

    /**
     * Indicates that the trial should end immediately.
     *
     * @var bool
     */
    protected $skipTrial = false;

    /**
     * The date when the subscription will be canceled
     * 
     * @var \Carbon\Carbon|\Carbon\CarbonInterface|null
     */
    protected $cancelAt = null;

    /**
     * Callback to be called before to send the subscriptio to stripe
     *
     * @var \Closure
     */
    protected $callbackToMapItemsBefore = null;

    protected $serviceIntegrationId = null;

    /**
     * Constructor
     * 
     * @param \\App\Services\Vendor\SystemCharges\IsBillableToSystemCharges|\Illuminate\Database\Eloquent\Model  $owner
     * @param string $identifier
     * @param string $name
     * @param int|null $serviceIntegrationId
     * @param Model|Illuminate\Database\Eloquent\Collection
     */
    public function __construct(EloquentModel $owner, $identifier, $name, $items, $serviceIntegrationId = null)
    {
        $this->owner                  = $owner;
        $this->subscriptionIdentifier = $identifier;
        $this->name                   = $name;
        $this->items                  = $items instanceof EloquentModel ? Collection::make([$items]) : $items;

        if ($this->owner->getSystemChargesServiceIntegration($serviceIntegrationId) == null){
            return ;
        }

        $this->serviceIntegrationId = $serviceIntegrationId ?? $this->owner->getSystemChargesServiceIntegration()->id;
        
        if (! $this->validItems()) {
            return ;
        }        
    }    

    /**
     * The subscriptable items must pass the validations
     *
     * @return boolean
     */
    protected function validItems()
    {
        return $this->items
                    ->filter(fn ($item) => $item->payment_type == 'sub' || $item->allow_recurring)
                    ->filter(fn ($item) => $item->price != null )
                    ->isNotEmpty();
    }


    /**
     * The metadata to apply to a new subscription.
     *
     * @param  array  $metadata
     * @return $this
     */
    public function withMetadata($metadata)
    {
        $this->metadata = (array) $metadata;

        return $this;
    }

    /**
     * Change the billing cycle anchor on a subscription creation.
     *
     * @param  \DateTimeInterface|int  $date
     * @return $this
     */
    public function anchorBillingCycleOn($date)
    {
        $this->billingCycleAnchor = $date;

        return $this;
    }

    /**
     * The description to apply to a new subscription.
     *
     * @param  array  $description
     * @return $this
     */
    public function description($description)
    {
        $this->description = (string) $description;

        return $this;
    }

    /**
     * The currency to apply to a new subscription.
     *
     * @param  array  $currency
     * @return $this
     */
    public function currency($currency)
    {
        $this->currency = (string) $currency;

        return $this;
    }

    /**
     * Force the trial to end immediately.
     *
     * @return $this
     */
    public function skipTrial()
    {
        $this->skipTrial = true;

        return $this;
    }

    /**
     * Set the date when the subscription should cancel
     *
     * can be used for define the billing cycles
     * 
     * @param  \DateTimeInterface|int  $date
     * @return $this
     */
    public function cancelAt($date)
    {
        $this->cancelAt = $date;

        return $this;
    }

    /**
     * Specify the number of days of the trial.
     *
     * @param  int  $trialDays
     * @return $this
     */
    public function trialDays($trialDays)
    {
        if ($trialDays != null || $trialDays != 0) {
            $this->trialExpires = Carbon::now()->addDays($trialDays);
        }

        return $this;
    }

    /**
     * Specify the ending date of the trial.
     *
     * @param  \Carbon\Carbon|\Carbon\CarbonInterface  $trialUntil
     * @return $this
     */
    public function trialUntil($trialUntil)
    {
        $this->trialExpires = $trialUntil;

        return $this;
    }

    /**
     * Map the items before to send to stripe
     *
     * @param Closure $callback
     * @return void
     */
    public function mapItems(Closure $callback)
    {
        $this->callbackToMapItemsBefore = $callback;

        return $this;
    }

    /**
     * Create a new System Subscription.
     *
     * @param  string $paymentMethod  The payment method for create the invoices
     * @return \App\Models\Charges\SystemSubscription
     *
     * @throws \Exception
     */
    public function create($paymentMethod)
    {
        if (empty($this->items)) {
            throw new Exception('At least one price is required when starting subscriptions.');
        }

        return $this->createSubscription($paymentMethod);
    }

    /**
     * Create a new System Subscription inside of an DB Transaction 
     *
     * @param  string $paymentMethod  The payment method for create the invoices
     * @param  bool $throwException  Sometimes you needs take the exception message
     * @return \App\Models\Charges\SystemSubscription
     *
     * @throws \Exception
     */
    public function safeCreate($paymentMethod, $throwException = false)
    {
        $subscription = null;
        try {
            DB::beginTransaction();
                $subscription = $this->create($paymentMethod);
            DB::commit();
        } catch (\Exception $err) {
            DB::rollback();
            if ($throwException) {
                throw $err;
            }
        }

        return $subscription;
    }

    /**
     * Create the Eloquent Subscription.
     *
     * @param string  $paymentMethod
     * @return \App\Models\Charges\SystemSubscription
     */
    protected function createSubscription($paymentMethod)
    {
        $subscription = $this->owner->system_subscriptions()->create([
            'identified_by'          => $this->subscriptionIdentifier,
            'name'                   => $this->name,            
            'description'            => $this->description,            
            'status'                 => \App\Models\Charges\SystemSubscription::STATUS_INCOMPLETE,
            'billing_cycle_anchor'   => $this->getBillingCycleAnchorForPayload(),
            'trial_ends_at'          => $this->getTrialEndForPayload(),
            'current_period_start'   => $this->getBillingCycleAnchorForPayload(),
            'current_period_ends_at' => $this->getCurrentPeriodEndsAtForPayload(),
            'cancel_at'              => $this->cancelAt,
            'metadata'               => $this->metadata,
            'service_integration_id' => $this->serviceIntegrationId
        ]);
        
        $subscriptionItemsMetadata = [];

        /** @var \Stripe\SubscriptionItem $item */
        foreach ($this->items as $item) {
            $subItem = $subscription->system_subscription_items()->updateOrcreate([
                'model_id'   => $item->id,
                'model_type' => get_class($item)
            ],[
                'quantity' => 1,
                'price'    => $item->price,
            ]);

            $subscriptionItemsMetadata[] = ['id' => $subItem->id];
        }

        $this->owner->createSystemPaymentIntentWith($this->serviceIntegrationId, $paymentMethod, $this->items->sum('price'), [
            'metadata' => [
                'uses_type'                 => 'for_system_subscriptions',
                'system_subscription_id'    => $subscription->id,
                'system_subscription_items' => $subscriptionItemsMetadata,
            ]
        ]);

        return $subscription;
    }

    public function getCurrentPeriodEndsAtForPayload()
    {
        $recurringInterval = $this->items->first()->subscription_settings['interval'];

        if (!$recurringInterval) {
            throw new Exception("Unknown Subscription Interval", 1);            
        }

        $carbonFunction = 'add'.(ucfirst($recurringInterval));

        // Calculating the renew date
        return $this->getBillingCycleAnchorForPayload()->{$carbonFunction}();
    }

    public function getBillingCycleAnchorForPayload()
    {
        return $this->billingCycleAnchor ?? now();
    }

    /**
     * Build the items for the subscription items
     *
     * @return array
     */
    protected function getItemsForPayload()
    {
        $items = $this->items;

        if ($this->callbackToMapItemsBefore instanceof Closure) {
            $items = $items->map($this->callbackToMapItemsBefore);
        }

        return $items->values()->all();
    }

    /**
     * Get the trial ending date for the Stripe payload.
     *
     * @return int|string|null
     */
    protected function getTrialEndForPayload()
    {
        if ($this->skipTrial) {
            return now();
        }

        if ($this->trialExpires instanceof DateTimeInterface) {
            return $this->trialExpires->getTimestamp();
        }

        return $this->trialExpires;
    }    
}
