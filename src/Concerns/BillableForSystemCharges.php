<?php 

namespace BlackSpot\SystemCharges\Concerns;

use BlackSpot\SystemCharges\Services\SystemChargesService;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\SystemCharges\Models\SystemPaymentIntent;

trait BillableForSystemCharges
{
    protected $systemsChargeServiceInstance;

    public function usingSystemChargesService($serviceIntegration)
    {
        if ($this->systemsChargeServiceInstance !== null) {
            return $this->systemsChargeServiceInstance;
        }

        return $this->systemsChargeServiceInstance = new SystemChargesService($serviceIntegration);
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
