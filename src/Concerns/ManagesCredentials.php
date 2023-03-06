<?php 

namespace BlackSpot\SystemCharges\Concerns;

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
    private $systemChargesServiceIntegrationRecentlyFetched = null;

    /**
     * Get the stripe service integration query
     * 
     * @param int|null $serviceIntegrationId
     * 
     * @return \Illuminate\Database\Query\Builder|null
     */
    protected function getSystemChargesServiceIntegrationQuery($serviceIntegrationId = null)
    {
        $serviceIntegrationModel     = config('stripe-multiple-accounts.relationship_models.stripe_accounts');
        $serviceIntegrationTableName = $serviceIntegrationModel::TABLE_NAME;
        $query                       = DB::table($serviceIntegrationTableName)
                                        ->where('name', 'System_Charges')
                                        ->where('short_name', 'sys_ch');

        if (!is_null($serviceIntegrationId)) {
            $query = $query->where('id', $serviceIntegrationId);
        }elseif (isset($this->id) && self::class == config('stripe-multiple-accounts.relationship_models.stripe_accounts')) {
            $query = $query->where('id', $this->id);
        }else if (isset($this->service_integration_id)){
            $query = $query->where('id', $this->service_integration_id);
        }else if (method_exists($this, 'getSystemChargesServiceIntegrationId')){
            $query = $query->where('id', $this->getSystemChargesServiceIntegrationId());
        }else if (method_exists($this, 'getSystemChargesServiceIntegrationOwnerId') && method_exists($this,'getSystemChargesServiceIntegrationOwnerType')){
            $query = $query->where('owner_type', $this->getSystemChargesServiceIntegrationOwnerType())->where('owner_id', $this->getSystemChargesServiceIntegrationOwnerId());
        }else{
            $query = $query->where('owner_type', 'not-exists-expecting-null');
        }        

        return $query;
    }

    /**
     * Get the related stripe account where the user belongs to
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
        if (isset($this->id) && self::class == config('stripe-multiple-accounts.relationship_models.stripe_accounts')) {
            $service = (object) $this->toArray();
        }else{
            $query = $this->getSystemChargesServiceIntegrationQuery($serviceIntegrationId);

            if (is_null($query)) {
                return ;
            }
            
            $service = $query->first();
        }
        
        if (is_null($service)) {
            return ;
        }

        $payloadColumn = 'payload';

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
        $payloadColumn = 'payload';

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
