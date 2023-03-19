<?php

namespace BlackSpot\SystemCharges\Exceptions;

use Exception;

class InvalidSystemPaymentMethod extends Exception
{
    public static function isEmpty($owner)
    {
        return new static(class_basename($owner).' payment method is required null given.');
    }    

    public static function notSupported($owner, $paymentMethod)
    {
        return new static(class_basename($owner).' payment method "'.$paymentMethod.'" not supported.');
    }    
}
