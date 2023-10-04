<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\StripeMultipleAccounts\Models\StripeCustomer;
use BlackSpot\SystemCharges\Models\SystemPaymentIntent;

trait HasSystemChargesRelationship
{    
    protected $systemChargesServiceInstance;
  
    public function getSystemChargesAttribute()
    {
        if ($this->systemChargesServiceInstance !== null) {
            return $this->systemChargesServiceInstance;
        }

        return $this->systemChargesServiceInstance = new StripeService($this);
    }

    public function system_payment_intents()
    {
        return $this->hasMany(ServiceIntegrationsContainerProvider::getFromConfig('system_charges_models.payment_intent', SystemPaymentIntent::class), 'service_integration_id');           
    }
}