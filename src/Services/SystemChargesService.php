<?php 

namespace BlackSpot\SystemCharges\Services;

use BlackSpot\ServiceIntegrationsContainer\ServiceIntegration;
use BlackSpot\SystemCharges\Exceptions\InvalidSystemChargesServiceIntegration;
use BlackSpot\SystemCharges\Services\PaymentsService;
use Illuminate\Database\Eloquent\Model;

class SystemChargesService
{
    public $model;
    protected $payments;

    public function __construct(Model $model, ?Model $billable = null)
    {
        $this->model = $model;
        $this->payments = new PaymentsService($this, $billable);
    }

    /**
     * Main service
     */

    public function getService()
    {
        return $this->service_integrations
              ->filter(function($service){
                return $service->name == ServiceIntegration::SYSTEM_CHARGES_SERVICE && 
                      $service->short_name ==ServiceIntegration ::SYSTEM_CHARGES_SERVICE_SHORT_NAME;
              })
              ->first();   
    }

    public function getServiceIfActive()
    {
        $service = $this->getService();
        
        if ($service == null) return ;
        if ($service->active()) return $service;
    }

    public function hasService()
    { 
        return $this->getService() !== null;
    }  

    public function serviceIsActive()
    {
        return optional($this->getService())->active() == true;
    }    


    /**
     * Assert
     */

    public function assertSystemChargesServiceExists()
    {
        if (! $this->hasService()) {
            throw InvalidSystemChargesServiceIntegration::notYetCreated($this->model);
        }
    }

    public function assertActiveSystemChargesServiceExists()
    {
        if (! $this->serviceIsActive()) {
            throw InvalidSystemChargesServiceIntegration::isDisabled($this->model);
        }
    }
}