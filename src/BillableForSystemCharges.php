<?php 

namespace BlackSpot\SystemCharges;

use BlackSpot\SystemCharges\Concerns\ManagesCredentials;
use BlackSpot\SystemCharges\Concerns\ManagesSubscriptions;
use BlackSpot\SystemCharges\Concerns\PerformsCharges;
use BlackSpot\SystemCharges\Relationships\HasSystemPaymentIntents;
use Exception;

trait BillableForSystemCharges
{
    use ManagesCredentials;
    use HasSystemPaymentIntents;
    use PerformsCharges;
    use ManagesSubscriptions;
}
