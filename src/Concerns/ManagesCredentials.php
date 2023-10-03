<?php 

namespace BlackSpot\SystemCharges\Concerns;

use BlackSpot\ServiceIntegrationsContainer\Concerns\ServiceIntegrationFinder;
use BlackSpot\ServiceIntegrationsContainer\Models\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\SystemCharges\Exceptions\InvalidSystemChargesServiceIntegration;
use Illuminate\Support\Facades\DB;

/**
 * Manages the auth credentials to connect with stripe
 * 
 * @method getSystemChargesServiceIntegrationQuery($serviceIntegrationId = null)
 * @method getSystemChargesServiceIntegration($serviceIntegrationId = null)
 * @method assertSystemChargesServiceExists($serviceIntegrationId = null)
 * @method getSystemChargesPayloadService($serviceIntegrationId = null)
 */
trait ManagesCredentials
{
    use ServiceIntegrationFinder;

    /**
     * Get the system charges service integration query
     * 
     * @param int|null $serviceIntegrationId
     * 
     * @return \Illuminate\Database\Query\Builder|null
     */
    protected function getSystemChargesServiceIntegrationQuery($serviceIntegrationId = null)
    {
        return $this->getServiceIntegrationQuery($serviceIntegrationId, 'system_charges', function (&$query) {
            $query->where([
                'name'       => ServiceIntegration::SYSTEM_CHARGES_SERVICE,
                'short_name' => ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME,
            ]);
        });
    }

    /**
     * Get the related system charges account where the user belongs to
     * 
     * @param int|null $serviceIntegrationId
     * 
     * @return object|null
     * 
     * @throws \LogicException
     */
    public function getSystemChargesServiceIntegration($serviceIntegrationId = null)
    {
        if ($this->serviceIntegrationWasLoaded($serviceIntegrationId)) {
            return $this->getLoadedServiceIntegration($serviceIntegrationId);
        }

        $service = $this->resolveServiceIntegrationFromInstance($this, $serviceIntegrationId) ?? $this->getSystemChargesServiceIntegrationQuery($serviceIntegrationId)->first();

        if ($service == null) {
            throw InvalidSystemChargesServiceIntegration::notYetCreated($this);
        }

        $payloadColumn = ServiceIntegrationsContainerProvider::getFromConfig('payload_colum','payload');

        if (! isset($service->{$payloadColumn})) {
            throw InvalidSystemChargesServiceIntegration::payloadColumnNotFound($this, $payloadColumn);
        }

        $payloadValue = $service->{$payloadColumn};

        $service->{$payloadColumn.'_decoded'} = is_array($payloadValue) ? $payloadValue : json_decode($payloadValue, true);

        return $this->putServiceIntegrationFound($service);
    }

    /**
     * Determine if the customer has a SystemCharges service and throw an exception if not.
     *
     * @return void
     *
     * @throws InvalidSystemChargesServiceIntegration
     */
    public function assertSystemChargesServiceExists($serviceIntegrationId = null)
    {
        $this->getSystemChargesServiceIntegration($serviceIntegrationId);
    }

    /**
     * Determine if the customer has a SystemCharges service and throw an exception if not.
     *
     * @return void
     *
     * @throws InvalidSystemChargesServiceIntegration
     */
    public function assertActiveSystemChargesServiceExists($serviceIntegrationId = null)
    {
        if ($this->getSystemChargesServiceIntegration($serviceIntegrationId)->disabled()) {
            InvalidSystemChargesServiceIntegration::isDisabled($this);
        }
    }

    /**
     * Get the related stripe secret key
     * 
     * @param int|null 
     * @return string|null
     * 
     * @throws InvalidSystemChargesServiceIntegration
     */
    public function getSystemChargesPayloadService($serviceIntegrationId = null)
    {
        $stripeIntegration = $this->getSystemChargesServiceIntegration($serviceIntegrationId);
        $payloadColumn     = ServiceIntegrationsContainerProvider::getFromConfig('payload_column','payload');

        if (! isset($stripeIntegration->{$payloadColumn})) {
            throw InvalidSystemChargesServiceIntegration::payloadColumnNotFound($this, $payloadColumn);
        }

        return $stripeIntegration->{$payloadColumn.'_decoded'};
    }
}
