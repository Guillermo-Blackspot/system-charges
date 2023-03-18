<?php

use BlackSpot\SystemCharges\Models\SystemPaymentIntent;
use BlackSpot\SystemCharges\Models\SystemSubscription;
use BlackSpot\SystemCharges\Models\SystemSubscriptionItem;

return  [

    /**
     * Copy this in the service-integrations-container.php config
     */
    
    'system_charges_models' => [
        'payment_intent'    => SystemPaymentIntent::class,
        'subscription'      => SystemSubscription::class,
        'subscription_item' => SystemSubscriptionItem::class,
    ]
];