<?php 

namespace BlackSpot\SystemCharges;

use BlackSpot\SystemCharges\Services\SystemChargesService;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\SystemCharges\Models\SystemPaymentIntent;

trait BillableForSystemCharges
{
    protected $systemChargeServiceInstance;

    public function usingSystemChargesService($serviceIntegration)
    {
        if ($this->systemChargeServiceInstance !== null) {
            return $this->systemChargeServiceInstance;
        }

        return $this->systemChargeServiceInstance = new SystemChargesService($serviceIntegration, $this);
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
