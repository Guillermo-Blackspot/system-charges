<?php 

namespace BlackSpot\SystemCharges\Services;

use BlackSpot\ServiceIntegrationsContainer\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\SystemCharges\Exceptions\InvalidSystemChargesServiceIntegration;
use BlackSpot\SystemCharges\Services\PaymentsService;
use BlackSpot\SystemCharges\Services\Traits\PerformCharges;
use Illuminate\Database\Eloquent\Model;

class SystemChargesService
{
    use PerformCharges;

    public $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Main service
     */

    public function getService()
    {
        if (get_class($this->model) == ServiceIntegrationsContainerProvider::getFromConfig('model', ServiceIntegration::class) ) {            
            if ($this->model->name == ServiceIntegration::SYSTEM_CHARGES_SERVICE && $this->model->short_name == ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME) {
                return $this->model;
            }

            throw InvalidStripeServiceIntegration::incorrectProvider($this->model);
        }

        return $this->model->service_integrations
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
        if ($service->active) return $service;
    }

    public function hasService()
    { 
        return $this->getService() !== null;
    }  

    public function serviceIsActive()
    {
        return optional($this->getService())->active == true;
    }    


    /**
     * Assert
     */

    public function assertServiceExists()
    {
        if (! $this->hasService()) {
            throw InvalidSystemChargesServiceIntegration::notYetCreated($this->model);
        }
    }

    public function assertServiceIsActive()
    {
        if (! $this->serviceIsActive()) {
            throw InvalidSystemChargesServiceIntegration::isDisabled($this->model);
        }
    }
}