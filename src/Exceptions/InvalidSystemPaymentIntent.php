<?php

namespace BlackSpot\SystemCharges\Exceptions;

use Exception;

class InvalidSystemPaymentIntent extends Exception
{
    public static function paymentMethodNotSupported($owner, $paymentMethod)
    {
        return new static(class_basename($owner).' payment method "'.$paymentMethod.'" not supported.');
    }    
}
