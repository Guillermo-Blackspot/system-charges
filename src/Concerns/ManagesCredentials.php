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

    protected $systemChargesServiceIntegrationRecentlyFetched = null;

    /**
     * Get the system charges service integration query
     * 
     * @param int|null $serviceIntegrationId
     * 
     * @return \Illuminate\Database\Query\Builder|null
     */
    protected function getSystemChargesServiceIntegrationQuery($serviceIntegrationId = null)
    {
        return $this->getServiceIntegrationQuery($serviceIntegrationId, ['getSystemChargesServiceIntegrationId', 'getSystemChargesServiceIntegrationOwnerType'])
                    ->where('name', ServiceIntegration::SYSTEM_CHARGES_SERVICE)
                    ->where('short_name', ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME);
    }

    /**
     * Get the related system charges account where the user belongs to
     * 
     * @param int|null $serviceIntegrationId
     * 
     * @return object|null
     */
    public function getSystemChargesServiceIntegration($serviceIntegrationId = null)
    {
        if ($this->systemChargesServiceIntegrationRecentlyFetched !== null) {
            return $this->systemChargesServiceIntegrationRecentlyFetched;
        }
                
        // ServiceIntegration.php (Model)
        if (isset($this->id) && self::class == ServiceIntegrationsContainerProvider::getFromConfig('model')) {
            $service = (object) $this->toArray();
        }else{
            $query = $this->getSystemChargesServiceIntegrationQuery($serviceIntegrationId);

            if (is_null($query)) return ;
            
            $service = $query->first();
        }
        
        if (is_null($service)) {
            return ;
        }

        $payloadColumn = ServiceIntegrationsContainerProvider::getFromConfig('payload_colum','payload');

        if (isset($service->{$payloadColumn})) {
            $service->{$payloadColumn.'_decoded'} = json_decode($service->{$payloadColumn}, true);
        }

        return $this->systemChargesServiceIntegrationRecentlyFetched = $service;
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
