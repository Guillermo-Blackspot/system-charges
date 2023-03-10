<?php

namespace BlackSpot\SystemCharges\Concerns;

use Exception;

trait PerformsCharges
{
    public function createSystemPaymentIntentWithWireTransfer($serviceIntegrationId = null, $amount, $options = [])
    {       
        return $this->createSystemPaymentIntentWith($serviceIntegrationId, 'wire_t', $amount, $options);
    }

    public function createSystemPaymentIntentWithPayInSubsidiary($serviceIntegrationId = null, $amount, $options = [])
    {       
        return $this->createSystemPaymentIntentWith($serviceIntegrationId, 'in_sub', $amount, $options);
    }

    public function createSystemPaymentIntentWithAgreement($serviceIntegrationId = null, $amount, $options = [])
    {
        return $this->createSystemPaymentIntentWith($serviceIntegrationId, 'agree', $amount, $options);
    }

    public function createSystemPaymentIntentWith($serviceIntegrationId = null, $paymentMethod, $amount, $options = [])
    {
        $options['payment_method'] = $paymentMethod;
        
        return $this->createSystemPaymentIntent($serviceIntegrationId, $amount, $options);
    }

    /**
     * Delete a quote and the related items
     * 
     * @param int|null $serviceIntegrationId
     * @param string|int $amount
     * @param array $options
     * @throws \Exception
     * 
     * @return \App\Models\Charges\SystemPaymentIntent 
     */
    public function createSystemPaymentIntent($serviceIntegrationId = null, $amount, $options)
    {  
        if (!in_array($options['payment_method'],['wire_t','in_sub','agree'])) {
            throw new Exception("{$options['payment_method']} Not Exists", 1);
        }

        if (!isset($options['payment_method'])) {            
            throw new Exception("You must define a payment method", 1);
        }

        $sytemChargesServiceIntegration = $this->getSystemChargesServiceIntegration($serviceIntegrationId);

        if (is_null($sytemChargesServiceIntegration)) {
            return ;
        }

        $data = array_merge([
            'amount'                 => $amount,
            'paid'                   => false,
            'status'                 => 'pen',
            'owner_name'             => $this->full_name ?? $this->name,
            'owner_email'            => $this->email,
            'due_date'               => now()->addDays(3),
            'service_integration_id' => $sytemChargesServiceIntegration->id,
        ], $options);

        return $this->system_payment_intents()->create($data);
    }
}

