<?php

namespace BlackSpot\SystemCharges;

use BlackSpot\SystemCharges\Models\SystemSubscription;
use BlackSpot\SystemCharges\SubscriptionUtils;
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

    /**
     * The system charges service integration that provides this subscription
     *
     * @var int
     */
    protected $serviceIntegrationId = null;

    /**
     * Define the days or date when the product still active
     *
     * @var int|\DateTimeInterface
     */
    protected $keepProductsActiveUntil = null;

    /**
     * Recurring interval count to combine with the recurring interval
     * 
     * every $one month , every $two days, etc..
     *
     * @var int
     */
    protected $recurringIntervalCount = 1;

    /**
     * Recurring interval
     * 
     * day, week, month or year
     * 
     * @var string
     */
    protected $recurringInterval = null;

    
    /**
     * The expected invoices to reach 
     * 
     * after reached the subscription must be canceled
     *
     * null is a forever subscription 
     * 
     * @var int|null
     */
    protected $expectedInvoices = null;

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
        $this->serviceIntegrationId   = optional($this->owner->getSystemChargesServiceIntegration($serviceIntegrationId))->id;

        if ($this->serviceIntegrationId === null){
            return ; // unknow subsidiary
        }

        $existingRelatedSubscription = $this->owner->findSystemSubscriptionByIdentifier($this->serviceIntegrationId, $this->subscriptionIdentifier);

        if ($existingRelatedSubscription) {
            return $existingRelatedSubscription; // exists
        }

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
     * The billing cycles to invoice
     *
     * once the cycles is reached the subscription will be canceled
     * stopping the invoice generations
     * 
     * we struggly recommend to you, use the keepProductsActiveUntil() if you have an
     * action after all the invoices were paid and the subscription was canceled
     * 
     * @return $this
     */
    public function invoiceUntilReaching($expectedInvoices)
    {
        $this->expectedInvoices = $expectedInvoices;

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
        if ($date != null && ! ($date instanceof DateTimeInterface)) {
            $date = Carbon::parse($date);
        }

        if ($date instanceof DateTimeInterface && ($date->isPast() && !$date->isToday())) {
            throw new Exception("Can not create subscriptions with a past date", 1);
        }
    
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
        if ($date != null && ! ($date instanceof \DateTimeInterface)) {
            $date = Carbon::parse($date);
        }

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
        $this->trialExpires = (int) $trialDays;

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
        if ($trialUntil != null && ! ($trialUntil instanceof \DateTimeInterface)) {
            $trialUntil = Carbon::parse($trialUntil);
        }

        $this->trialExpires = $trialUntil;

        return $this;
    }

    /**
     * Define the days when the products still active after the subscription is cancelled of ended
     * 
     * Days or a concrete date, when null is received the products still active forever
     * 
     * @param null|int|\DateTimeInterface $daysOrDate
     * 
     * @return $this
     */
    public function keepProductsActiveUntil($daysOrDate)
    {
        $this->keepProductsActiveUntil = $daysOrDate;

        return $this;
    }

    /**
     * Interval
     *
     * @param int $interval
     * @return $this
     * 
     * @throws \Exception
     */
    public function interval($interval)
    {
        if (!in_array($interval, ['day','week','month','year'])) {
            throw new Exception("Unknow interval {$interval}, choose one of [day,week,month or year]", 1);
        }

        $this->recurringInterval = $interval;

        return $this;
    }

    /**
     * Interval count
     *
     * @param int $intervalCount
     * @return $this
     */
    public function intervalCount($intervalCount)
    {
        $this->recurringIntervalCount = (int) ($intervalCount ?? 1);

        return $this;
    }


    /**
     * Map the items before to send to stripe
     *
     * @param Closure $callback
     * @return $this
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
            'identified_by'              => $this->subscriptionIdentifier,
            'name'                       => $this->name,
            'description'                => $this->description,
            'status'                     => SystemSubscription::STATUS_INCOMPLETE,
            'billing_cycle_anchor'       => $this->getBillingCycleAnchorForPayload(),
            'current_period_start'       => $this->getBillingCycleAnchorForPayload(),
            'current_period_ends_at'     => $this->getCurrentPeriodEndsAtForPayload(),
            'trial_ends_at'              => $this->getTrialEndForPayload(),
            'cancel_at'                  => $this->getCancelAtForPayload(),
            'keep_products_active_until' => $this->getKeepProductsActiveUntilForPayload(),            
            'recurring_interval'         => $this->getIntervalForPayload(),
            'recurring_interval_count'   => $this->recurringIntervalCount,
            'expected_invoices'          => $this->expectedInvoices,
            'metadata'                   => $this->metadata,
            'owner_name'                 => $this->owner->full_name ?? $this->owner->name,
            'owner_email'                => $this->owner->email,
            'service_integration_id'     => $this->serviceIntegrationId
        ]);
        
        $this->createSubscriptionItems($subscription);
        
        return $subscription;
    }

    /**
     * Create the subscription items
     *
     * @param \BlackSpot\SystemCharges\SystemSubscription  $subscription
     * @return void
     */
    protected function createSubscriptionItems($subscription)
    {
        $agreggatedItems = [];

        /** @var \Stripe\SubscriptionItem $item */
        foreach ($this->items as $item) {
            $modelClass = get_class($item);        
            $subItem    = $subscription->system_subscription_items()->updateOrcreate(
                            [ 'model_id' => $item->id, 'model_type' => $modelClass],
                            [ 
                                'quantity'    => 1,         
                                'price  '     => $item->price,
                                'model_name'  => $item->name,
                                'model_class' => $modelClass,

                            ]
                        );

            $agreggatedItems[] = ['id' => $subItem->id];
        }

        $this->owner->createSystemPaymentIntentWith($this->serviceIntegrationId, $paymentMethod, $this->items->sum('price'), [
            'system_subscription_id'  => $subscription->id,
            'subscription_identifier' => $subscription->identified_by,
            'subscription_name'       => $subscription->name,
            'owner_name'              => $this->owner->full_name ? $this->owner->name,
            'owner_emai'              => $this->owner->email,
            'metadata' => [
                'uses_type'                 => 'for_system_subscriptions',
                'system_subscription_id'    => $subscription->id,
                'system_subscription_items' => $agreggatedItems,
            ]
        ]);

    }

    

    // /**
    //  * Build the items for the subscription items
    //  *
    //  * @return array
    //  */
    // protected function getItemsForPayload()
    // {
    //     if (is_array($this->items)) {
    //         return $this->items; // Was processed
    //     }

    //     $items = $this->items;

    //     if ($this->callbackToMapItemsBefore instanceof Closure) {
    //         $items = $items->map($this->callbackToMapItemsBefore);
    //     }

    //     return $items->values()->all();
    // }

    /**
     * Get the trial ending date for the Stripe payload.
     *
     * @return int|string|null
     */
    protected function getTrialEndForPayload()
    {
        if ($this->skipTrial) {
            return null; // has not trial days
        }

        $trialExpires = null;  // By default subscriptions not has trial days

        if (is_int($this->trialExpires)) {
            $trialExpires = $this->getBillingCycleAnchorForPayload()->addDays($this->trialExpires); // For days (int) Determine from the billing cycle anchor
        }else if ($this->trialExpires instanceof DateTimeInterface) {           
            if ($this->trialExpires->gt($this->getBillingCycleAnchorForPayload())){    
                $trialExpires = $this->trialExpires;
            }
        }

        return $trialExpires;
    }    

    /**
     * Determine the date until the related products still active
     *
     * @return DateTimeInterface|null
     */
    protected function getKeepProductsActiveUntilForPayload()
    {
        if ($this->keepProductsActiveUntil === null) {
            return null;
        }

        if (! $this->cancelAt) {
            return null;
        }

        $keepActiveUntil = null;

        if (is_int($this->keepProductsActiveUntil)) {
            $keepActiveUntil = $this->cancelAt->addDays($this->keepProductsActiveUntil);            
        }else{

            if ($this->keepProductsActiveUntil->gt($this->cancelAt)) {
                return $this->keepProductsActiveUntil;
            }

            $keepActiveUntil = $this->cancelAt;
        }

        return $keepActiveUntil;
    }

    /**
     * Get the subscription interval, defined by the user or the first item
     *
     * @return string
     * 
     * @throws \Exception
     */
    protected function getIntervalForPayload()
    {
        if (in_array($this->recurringInterval, ['day','week','month','year'])) {
            return $this->recurringInterval;
        }

        $recurringInterval = null;

        if (! $this->recurringInterval) {
            $recurringInterval = $this->items->first()->subscription_settings['interval'];
        }

        if ($recurringInterval == null) {
            throw new Exception("Unknow interval, \$firstItem->subscription_settings['interval'] property not found, choose one of [day,week,month or year]", 1);            
        }

        return $this->recurringInterval = $recurringInterval;
    }

    /**
     * Get the billing cycle anchor
     *
     * @return DateTimeInterface
     */
    protected function getBillingCycleAnchorForPayload()
    {
        return $this->billingCycleAnchor ?? now();
    }

    /**
     * Resolve the first current period ends at
     *
     * @return DateTimeInterface
     * 
     * @throws \Exception
     */
    protected function getCurrentPeriodEndsAtForPayload()
    {
        $recurringInterval = $this->getIntervalForPayload();
        $carbonFunction    = 'add'.(ucfirst($recurringInterval)).'s';

        // Calculating the renew date
        return $this->getBillingCycleAnchorForPayload()->{$carbonFunction}($this->recurringIntervalCount);
    }

    /**
     * Get the cancel date 
     * 
     * calculates from the expected invoices or get defined by customer
     *
     * @return null|\DateTimeInterface
     */
    protected function getCancelAtForPayload()
    {
        if (! $this->expectedInvoices) {
            return $this->cancelAt;
        }

        if (! $this->cancelAt) {
            return null;
        }

        return SubscriptionUtils::calculateCancelAtFromExpectedInvoices(
            $this->getIntervalForPayload(),
            $this->intervalCount,
            $this->expectedInvoices,
            $this->getBillingCycleAnchorForPayload()
        );
    }
}
