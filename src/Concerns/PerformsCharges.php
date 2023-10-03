<?php

namespace BlackSpot\SystemCharges\Concerns;

use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\SystemCharges\Exceptions\InvalidSystemPaymentMethod;
use BlackSpot\SystemCharges\Models\SystemPaymentIntent;
use Illuminate\Support\Facades\Date;
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
     * @return \App\Models\Charges\SystemPaymentIntent 
     * 
     * @throws InvalidSystemPaymentMethod|InvalidSystemChargesServiceIntegration
     */
    public function createSystemPaymentIntent($serviceIntegrationId = null, $amount, $options)
    {  
        if (! isset($options['payment_method'])) {            
            throw InvalidSystemPaymentMethod::isEmpty();
        }

        if (! in_array($options['payment_method'],['wire_t','in_sub','agree'])) {
            throw InvalidSystemPaymentMethod::notSupported($this, $options['payment_method']);
        }

        $serviceIntegration = $this->getSystemChargesServiceIntegration($serviceIntegrationId);

        $metadata = [
            'owner_id'   => $this->id,
            'owner_type' => get_class($this),
        ];

        if (isset($options['metadata'])) {
            $metadata = array_merge($metadata, $options['metadata']);
        }
        
        unset($options['metadata']);
        
        $data = array_merge([
            'amount'                 => $amount,
            'paid'                   => false,
            'status'                 => 'pen',
            'owner_name'             => $this->full_name ?? $this->name,
            'owner_email'            => $this->email,
            'due_date'               => (isset($options['created_at']) ? Date::parse($options['created_at'])->copy() : Date::now())->addDays(3),
            'service_integration_id' => $serviceIntegration->id,
            'metadata'               => $metadata
        ], $options);

        return $this->system_payment_intents()->create($data);
    }


    /**
     * Get the system_payment_intents, eloquent relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function system_payment_intents()
    {
        return $this->morphMany(ServiceIntegrationsContainerProvider::getFromConfig('system_charges_models.payment_intent', SystemPaymentIntent::class), 'owner');
    }
}

