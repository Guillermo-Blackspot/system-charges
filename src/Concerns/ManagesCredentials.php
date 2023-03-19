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
 * @method assertSystemChargesServiceIntegrationExists($serviceIntegrationId = null)
 * @method getRelatedSystemChargesPayloadServiceIntegration($serviceIntegrationId = null)
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
        return $this->getServiceIntegrationQueryFinder($serviceIntegrationId, 'system_charges')
                    ->where('name', ServiceIntegration::SYSTEM_CHARGES_SERVICE)
                    ->where('short_name', ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME);
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
            return $this->getServiceIntegrationLoaded($serviceIntegrationId);
        }

        $systemChargesIntegration = $this->resolveServiceIntegrationFromInstance($this, $serviceIntegrationId);

        // Try to resolve
        if (is_null($systemChargesIntegration)){
            $systemChargesIntegration = $this->getStripeServiceIntegrationQuery($serviceIntegrationId)->first();
        }

        if (is_null($systemChargesIntegration)) {
            throw InvalidSystemChargesServiceIntegration::notYetCreated($this);
        }

        $payloadColumn = ServiceIntegrationsContainerProvider::getFromConfig('payload_colum','payload');

        if (! isset($systemChargesIntegration->{$payloadColumn})) {
            throw InvalidSystemChargesServiceIntegration::payloadColumnNotFound($this, $payloadColumn);
        }

        $payloadValue = $systemChargesIntegration->{$payloadColumn};

        $systemChargesIntegration->{$payloadColumn.'_decoded'} = is_array($payloadValue) ? $payloadValue : json_decode($payloadValue, true);

        return $this->putServiceIntegrationFound($systemChargesIntegration);
    }

    /**
     * Determine if the customer has a SystemCharges service and throw an exception if not.
     *
     * @return void
     *
     * @throws InvalidSystemChargesServiceIntegration
     */
    public function assertSystemChargesServiceIntegrationExists($serviceIntegrationId = null)
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
    public function assertActiveSystemChargesServiceIntegrationExists($serviceIntegrationId = null)
    {
        $isActive = $this->getSystemChargesServiceIntegration($serviceIntegrationId)->active;

        if (! $isActive) {
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
    public function getRelatedSystemChargesPayloadServiceIntegration($serviceIntegrationId = null)
    {
        $stripeIntegration = $this->getSystemChargesServiceIntegration($serviceIntegrationId);
        $payloadColumn     = ServiceIntegrationsContainerProvider::getFromConfig('payload_column','payload');

        if (! isset($stripeIntegration->{$payloadColumn})) {
            throw InvalidSystemChargesServiceIntegration::payloadColumnNotFound($this, $payloadColumn);
        }

        return $stripeIntegration->{$payloadColumn.'_decoded'};
    }
}
