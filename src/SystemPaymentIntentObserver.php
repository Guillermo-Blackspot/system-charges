<?php
namespace BlackSpot\SystemCharges;

use BlackSpot\SystemCharges\Actions\GenerateTrackingNumber;
use Illuminate\Support\Facades\Date;

class SystemPaymentIntentObserver
{
    /**
     * Handle the SystemPaymentIntent "creating" event.
     *
     * @param  \BlackSpot\SystemCharges\Models\SystemPaymentIntent  $systemPaymentIntent
     * @return void
     */
    public function creating($systemPaymentIntent)
    {
        $systemPaymentIntent->tracking_number = GenerateTrackingNumber::execute();  

        $this->resolvePaidAndPaidAtColumns($systemPaymentIntent);
    }
    
    /**
     * Handle the SystemPaymentIntent "updating" event.
     *
     * @param  \BlackSpot\SystemCharges\Models\SystemPaymentIntent  $systemPaymentIntent
     * @return void
     */
    public function updating($systemPaymentIntent)
    {
        $this->resolvePaidAndPaidAtColumns($systemPaymentIntent);
    }


    private function resolvePaidAndPaidAtColumns(&$systemPaymentIntent)
    {
        if ($systemPaymentIntent->isDirty('status')) {
            if ($systemPaymentIntent->status == 'pai') {
                $systemPaymentIntent->paid = true;                
            }else {                
                $systemPaymentIntent->paid = false;
            }
        }

        if ($systemPaymentIntent->paid) {
            if (! $systemPaymentIntent->paid_at) {
                $systemPaymentIntent->paid_at = Date::now();                
            }
        }else{
            $systemPaymentIntent->paid_at = null;
        }
    }
}
