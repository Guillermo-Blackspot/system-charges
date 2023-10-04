<?php

namespace BlackSpot\SystemCharges\Concerns;

use BlackSpot\ServiceIntegrationsContainer\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationContainer;
use BlackSpot\SystemCharges\Exceptions\InvalidSystemChargesServiceIntegration;
use BlackSpot\SystemCharges\Models\SystemPaymentIntent;
use BlackSpot\SystemCharges\Models\SystemSubscription;

/**
 * Use in the subsidiary models
 * 
 * @method protected function searchSystemChargesService()
 * @method public function hasSystemChargesService() : boolean
 * @method public function hasActiveSystemChargesService() : boolean
 * @method public function getSystemChargesService() : object (\BlackSpot\ServiceIntegrationsContainer\ServiceIntegration)
 * @method public function getActiveSystemChargesService() : object (\BlackSpot\ServiceIntegrationsContainer\ServiceIntegration)
 * @method public function scopeSystemChargesService($query) : object
 * @method public function system_subscriptions() : object (HasMany)
 * @method public function system_payment_intents() : object (HasMany)
 */
trait HasSystemChargesIntegration
{
  /**
   * Boot on delete method
   */
  public static function bootHasSystemChargesIntegrations()
  {
    static::deleting(function ($model) {
      
      if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
        return;
      }

      // $model->system_subscriptions()
      //         ->where('status', '!=', SystemSubscription::STATUS_UNLINKED)
      //         ->where('status', '!=', SystemSubscription::STATUS_CANCELED)
      //         ->update([
      //           'service_integration_id' => null,
      //           'status' => SystemSubscription::STATUS_UNLINKED
      //         ]);

      $model->system_payment_intents()->update(['service_integration_id' => null]);
    });
  }

  /**
   * Find the system charges service integrations
   *    
   * @return void
   */
  protected function searchSystemChargesService()
  {
    return $this->service_integrations
              ->filter(function($service){
                return $service->name == ServiceIntegration::SYSTEM_CHARGES_SERVICE && 
                      $service->short_name == ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME;
              })
              ->first();            
  }

  public function hasSystemChargesService()
  { 
    return $this->searchSystemChargesService() !== null;
  }  

  public function hasActiveSystemChargesService()
  {  
    return optional($this->searchSystemChargesService())->active() == true;
  }

  public function getSystemChargesService()
  {
    return $this->searchSystemChargesService();
  }

  public function getActiveSystemChargesService()
  {
    $service = $this->searchSystemChargesService();
    
    if ($service == null) return ;
    if ($service->active()) return $service;
  }

  public function assertSystemChargesServiceExists()
  {
    if (! $this->hasSystemChargesService()) {
      throw InvalidSystemChargesServiceIntegration::notYetCreated($this);
    }
  }

  public function assertActiveSystemChargesServiceExists()
  {
    if (! $this->hasActiveSystemChargesService()) {
      throw InvalidSystemChargesServiceIntegration::isDisabled($this);
    }
  }

  public function scopeWhereHasSystemChargesServiceIntegration($query)
  {
    return $query->whereHas('service_integrations', function($query){
      $query
        ->where('name', ServiceIntegration::SYSTEM_CHARGES_SERVICE)
        ->where('short_name', ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME);
    });
  }

  public function scopeWhereHasActiveSystemChargesServiceIntegration($query)
  {
    return $query->whereHas('service_integrations', function($query){
      $query
        ->where('name', ServiceIntegration::SYSTEM_CHARGES_SERVICE)
        ->where('short_name', ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME)
        ->where('active', true);      
    });
  }
}
