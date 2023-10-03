<?php 

namespace BlackSpot\SystemCharges;

use BlackSpot\SystemCharges\Concerns\ManagesCredentials;
use BlackSpot\SystemCharges\Concerns\ManagesSubscriptions;
use BlackSpot\SystemCharges\Concerns\PerformsCharges;

trait BillableForSystemCharges
{
    use ManagesCredentials;
    use PerformsCharges;
    // use ManagesSubscriptions;
}
