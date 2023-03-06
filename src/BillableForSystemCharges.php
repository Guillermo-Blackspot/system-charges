<?php 

namespace BlackSpot\SystemCharges;

use App\Services\Vendor\SystemCharges\Concerns\ManagesSubscriptions;
use App\Services\Vendor\SystemCharges\Concerns\ManagesCredentials;
use App\Services\Vendor\SystemCharges\Concerns\PerformsCharges;
use App\Services\Vendor\SystemCharges\Relationships\HasSystemSubscriptions;
use Exception;

trait BillableForSystemCharges
{
    use ManagesCredentials;
    use PerformsCharges;
    use ManagesSubscriptions;
    use HasSystemSubscriptions;
}
