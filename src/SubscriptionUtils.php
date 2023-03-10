<?php

namespace BlackSpot\SystemCharges;

use Illuminate\Support\Arr;
use Carbon\Carbon;

class SubscriptionUtils
{


    /**
     * Undocumented function
     *
     * @param null|\DateTimeInterface|string $billingCycleAnchor
     * @return \DateTimeInterface
     */
    public static function resolveBillingCycleAnchor($billingCycleAnchor = null)
    {
        if (is_null($billingCycleAnchor)) {
            return Carbon::now();
        }

        $billingCycleAnchor = Carbon::parse($billingCycleAnchor);

        if ($billingCycleAnchor->isPast() && ! $billingCycleAnchor->isToday()) {            
            $billingCycleAnchor = Carbon::now();
        }

        return $billingCycleAnchor;
    }

    /**
     * Calculate the cancel at
     *
     * @param string  $interval
     * @param int  $intervalCount  - default 1
     * @param int|null  $expectedInvoices  - null is never will be canceled
     * @param string|null|DateTimeInterface  $billingCycleAnchor  -- null is now
     * @return \DateTimeInterface|null
     */
    public static function calculateCancelAtFromExpectedInvoices($interval, $intervalCount = 1, $expectedInvoices, $billingCycleAnchor = null)
    {   
        if ($expectedInvoices === null) {
            return null;  // never will be canceled, only when accumulates two pending invoices
        }

        $billingCycleAnchor = static::resolveBillingCycleAnchor($billingCycleAnchor);
        $carbonFunction     = 'add'.(ucfirst($interval)).'s';  // addDays, addWeeks, addMonths, addYears

        if (! $intervalCount) {
            $intervalCount = 1;
        }

        $cancelAt = $billingCycleAnchor;
        
        for ($i = 0; $i < $expectedInvoices; $i++) { 
            $cancelAt = $cancelAt->{$carbonFunction}($intervalCount); // every $intervalCount(2) months or any interval
        }

        return $cancelAt;
    }


}
