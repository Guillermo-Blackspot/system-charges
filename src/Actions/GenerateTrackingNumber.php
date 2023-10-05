<?php
namespace BlackSpot\SystemCharges\Actions;

use BlackSpot\SystemCharges\Models\SystemPaymentIntent;
use Illuminate\Support\Facades\DB;

class GenerateTrackingNumber
{
    public static function execute()
    {
        return (new self)->handle();
    }

    public function handle()
    {
        $number = $this->buildTrackingNumber();
        $number = number_format($number,0,'.','-');

        if (DB::table(SystemPaymentIntent::TABLE_NAME)->where('tracking_number', $number)->exists()) {
            return $this->handle();
        }
    
        return $number;
    }


    private function buildTrackingNumber()
    {
        //return mt_rand(100000000000000000, 999999999999999999);
        return mt_rand(100000000, 999999999);
    }
}
