<?php 

namespace BlackSpot\SystemCharges\Concerns;

use BlackSpot\ServiceIntegrationsContainer\Concerns\ServiceIntegrationFinder;
use BlackSpot\ServiceIntegrationsContainer\Models\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
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
            return ;
        }

        $payloadColumn = ServiceIntegrationsContainerProvider::getFromConfig('payload_colum','payload');

        if (isset($systemChargesIntegration->{$payloadColumn})) {
            $systemChargesIntegration->{$payloadColumn.'_decoded'} = json_decode($systemChargesIntegration->{$payloadColumn}, true);
        }

        return $this->putServiceIntegrationFound($systemChargesIntegration);
    }

    /**
     * Determine if the customer has a Stripe customer ID and throw an exception if not.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function assertSystemChargesServiceIntegrationExists($serviceIntegrationId = null)
    {
        $query = $this->getSystemChargesServiceIntegrationQuery($serviceIntegrationId);

        if (is_null($query)) {
            throw new \Exception("System Charges Service Integration Not Created Yet", 1);
        }

        $service = $query->first();
        
        if (is_null($service)) {
            throw new \Exception("System Charges Service Integration Not Created Yet", 1);
        }
    }

    /**
     * Get the related stripe secret key
     * 
     * @param int|null 
     * @return string|null
     */
    public function getRelatedSystemChargesPayloadServiceIntegration($serviceIntegrationId = null)
    {
        $payloadColumn     = ServiceIntegrationsContainerProvider::getFromConfig('payload_column','payload');
        $stripeIntegration = $this->getSystemChargesServiceIntegration($serviceIntegrationId);

        if (is_null($stripeIntegration)) {
            return ;
        }

        if (!isset($stripeIntegration->{$payloadColumn})) {
            return ;
        }

        return json_decode($stripeIntegration->{$payloadColumn}, true);
    }
}
