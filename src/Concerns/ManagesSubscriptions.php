<?php

namespace BlackSpot\SystemCharges\Concerns;

use BlackSpot\SystemCharges\SubscriptionBuilder;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use Exception;

trait ManagesSubscriptions
{    
    public function newSystemSubscription($serviceIntegrationId = null, $identifier, $name, $items = [])
    {
        return new SubscriptionBuilder($this, $identifier, $name, $items, $serviceIntegrationId);
    }    

    /**
     * Determe if the model has a suscription with the subscription identifier 
     *
     * @param int|null  $serviceIntegrationId
     * @param string  $subscriptionIdentifier
     * 
     * @return bool
     */
    public function isSubscribedWithSystemChargesTo($serviceIntegrationId = null, $subscriptionIdentifier)
    {
        $serviceIntegrationId = optional($this->getSystemChargesServiceIntegration($serviceIntegrationId))->id;

        if (is_null($serviceIntegrationId)) {
            return false;
        }

        return $this->system_subscriptions()->serviceIntegration($serviceIntegrationId)->where('identified_by', $subscriptionIdentifier)->exists();
    }


    /**
     * Find one subscription that belongs to the model by subscription identifier 
     *
     * @param int|null  $serviceIntegrationId
     * @param string  $subscriptionIdentifier
     * @param array  $with  - eager loading relationships
     * 
     * @return null\BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationSubscription
     */
    public function findSystemSubscriptionByIdentifier($serviceIntegrationId = null, $subscriptionIdentifier, $with = [])
    {
        $serviceIntegrationId = optional($this->getSystemChargesServiceIntegration($serviceIntegrationId))->id;

        if (is_null($serviceIntegrationId)) {
            return null;
        }

        return $this->system_subscriptions()->serviceIntegration($serviceIntegrationId)->with($with)->where('identified_by', $subscriptionIdentifier)->first();
    }
    

    /**
     * Get the system_subscriptions relationship
     *
     * you must use this relationships with the serviceIntegration() scope on query, on the writting mode is not usually
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function system_subscriptions()
    {
        return $this->morphMany(ServiceIntegrationsContainerProvider::getFromConfig('system_charges_models.subscription', SystemSubscription::class), 'owner');
    }
}