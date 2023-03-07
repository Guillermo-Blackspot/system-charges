<?php

namespace BlackSpot\SystemCharges\Concerns;

use BlackSpot\SystemCharges\SubscriptionBuilder;
use Exception;

trait ManagesSubscriptions
{    
    public function newSystemSubscription($serviceIntegrationId = null, $identifier, $name, $items = [])
    {
        return new SubscriptionBuilder($this, $identifier, $name, $items, $serviceIntegrationId);
    }    
}