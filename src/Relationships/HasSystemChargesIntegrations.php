<?php

namespace BlackSpot\SystemCharges\Relationships;

use BlackSpot\ServiceIntegrationsContainer\Models\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;

trait HasSystemChargesIntegrations
{
  public function scopeSystemChargesService($query)
  {
      return $query->where('name', ServiceIntegration::SYSTEM_CHARGES_SERVICE)
                  ->where('short_name', ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME);
  }

  /**
   * Find the system charges service integrations
   *
   * @param boolean $evaluatesActiveStatus
   * @return void
   */
  protected function findSystemChargesServiceIntegration($evaluatesActiveStatus = false)
  {
    return $this->service_integrations
              ->filter(function($service){
                return $service->name == ServiceIntegration::SYSTEM_CHARGES_SERVICE && $service->short_name == ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME;
              }) 
              ->filter(function($service) use($evaluatesActiveStatus){
                if ($evaluatesActiveStatus) return $service->active == true;
                return true; // continue
              });
  }

  /**
   * Determine if the owner model has the system charges service integration
   *
   * For a better performance use the scope ->withSystemChargesServiceIntegration() before use 
   * this function
   * 
   * @param boolean $evaluatesActiveStatus
   * @return boolean
   */
  public function hasSystemChargesServiceIntegration($evaluatesActiveStatus = false)
  {  
    return $this->findSystemChargesServiceIntegration($evaluatesActiveStatus)->isNotEmpty();
  }  

  /**
   * Determine if the owner model has the system charges service integration active
   * 
   * For a better performance use the scope ->withSystemChargesServiceIntegration() before use 
   * this function
   * 
   * @return boolean
   */
  public function hasSystemChargesServiceIntegrationActive()
  {  
    return $this->findSystemChargesServiceIntegration(true)->isNotEmpty();
  }

  /**
   * Get the system charges service integration if active
   * 
   * For a better performance use the scope ->withSystemChargesServiceIntegration() before use 
   * this function
   * 
   * @return object|null
   */
  public function getActiveSystemChargesServiceIntegration()
  {
    return $this->findSystemChargesServiceIntegration(true)->first();
  }

  /**
   * Get the system charges service integration
   * 
   * For a better performance use the scope ->withSystemChargesServiceIntegration() before use 
   * this function
   * 
   * @return object|null
   */
  public function getSystemChargesServiceIntegration()
  {
    return $this->findSystemChargesServiceIntegration(false)->first();
  }

  public function scopeWithSystemChargesServiceIntegration($query)
  {
    $payloadColumn = ServiceIntegrationsContainerProvider::getFromConfig('payload_column','payload');

    return $query->with([
      'service_integrations' => function($query){
        $query
          ->select('id',$payloadColumn,'owner_id','owner_type','active','name','short_name')
          ->where('name', ServiceIntegration::SYSTEM_CHARGES_SERVICE)
          ->where('short_name', ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME);
      }
    ]);
  }

  public function scopeWithSystemChargesServiceIntegrationIfActive($query)
  {
    $payloadColumn = ServiceIntegrationsContainerProvider::getFromConfig('payload_column','payload');

    return $query->with([
      'service_integrations' => function($query){
        $query
          ->select('id',$payloadColumn,'owner_id','owner_type','active','name','short_name')
          ->where('name', ServiceIntegration::SYSTEM_CHARGES_SERVICE)
          ->where('short_name',ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME)
          ->where('active', true);
      }
    ]);
  }


}
