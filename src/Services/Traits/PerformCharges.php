<?php 

namespace BlackSpot\SystemCharges\Services\Traits;

use BlackSpot\SystemCharges\Exceptions\InvalidSystemPaymentMethod;
use BlackSpot\SystemCharges\Models\SystemPaymentIntent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

trait PerformCharges
{
    public function createWireTransfer(Model $billable, $amount, $options = []) 
    {
        return $this->createWith($billable, SystemPaymentIntent::WIRE_TRANSFER, $amount, $options);
    }
    
    public function createPaymentInSubsidiary(Model $billable, $amount, $options = []) 
    {
        return $this->createWith($billable, SystemPaymentIntent::PAY_IN_SUBSIDIARY, $amount, $options);
    }
    
    public function createAgreement(Model $billable, $amount, $options = []) 
    {
        return $this->createWith($billable, SystemPaymentIntent::AGREEMENT, $amount, $options);
    }

    public function createPaymentAgainstDelivery(Model $billable, $amount, $options = []) 
    {
        return $this->createWith($billable, SystemPaymentIntent::PAYMENT_AGAINST_DELIVERY, $amount, $options);
    }

    public function createWith(Model $billable, $paymentMethod, $amount, $options = [])
    {
        $options['payment_method'] = $paymentMethod;
        
        return $this->createPaymentIntent($billable, $amount, $options);
    }

    public function createPaymentIntent(Model $billable, $amount, $options)
    {
        if (! isset($options['payment_method'])) {            
            throw InvalidSystemPaymentMethod::isEmpty();
        }

        if (! in_array($options['payment_method'], SystemPaymentIntent::getSupportedPaymentMethods())) {
            throw InvalidSystemPaymentMethod::notSupported($this, $options['payment_method']);
        }

        $serviceIntegration = $this->getService();

        $metadata = [
            'owner_id'   => $billable->getKey(),
            'owner_type' => get_class($billable),
        ];

        if (isset($options['metadata'])) {
            $metadata = array_merge($metadata, $options['metadata']);
        }
        
        unset($options['metadata']);
        
        $data = array_merge([
            'amount'                 => $amount,
            'paid'                   => false,
            'status'                 => 'pen',
            'owner_name'             => $billable->full_name ?? $billable->name,
            'owner_email'            => $billable->email,
            'due_date'               => (isset($options['created_at']) ? Date::parse($options['created_at'])->copy() : Date::now())->addDays(3),
            'service_integration_id' => $serviceIntegration->id,
            'metadata'               => $metadata
        ], $options);

        return $billable->system_payment_intents()->create($data);
    }

}