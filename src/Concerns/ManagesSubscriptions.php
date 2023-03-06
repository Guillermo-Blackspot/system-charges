<?php

namespace BlackSpot\SystemCharges\Concerns;

use App\Services\Vendor\SystemCharges\SubscriptionBuilder;
use Exception;

trait ManagesSubscriptions
{    
    public function newSystemSubscription($identifier, $name, $items = [])
    {
        return new SubscriptionBuilder($this, $identifier, $name, $items);
    }
    
}