<?php

namespace BlackSpot\SystemCharges\Relationships;

use BlackSpot\ServiceIntegrationsContainer\Models\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\SystemCharges\Models\SystemPaymentIntent;
use BlackSpot\SystemCharges\Models\SystemSubscription;

trait HasSystemChargesIntegrations
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
      $model->system_subscriptions()
              ->where('status', '!=', SystemSubscription::STATUS_UNLINKED)
              ->where('status', '!=', SystemSubscription::STATUS_CANCELED)
              ->update([
                'service_integration_id' => null,
                'status' => SystemSubscription::STATUS_UNLINKED
              ]);

      $model->system_payment_intents()->update(['service_integration_id' => null]);
    });
  }

  /**
   * ------------------------------------------
   * Eloquent relationships
   * ------------------------------------------
   */

  /**
   * Get the system subscriptions of the service integration
   *
   * @return void
   */
  public function system_subscriptions()
  {
    return $this->hasMany(ServiceIntegrationsContainerProvider::getFromConfig('system_charges_models.subscription', SystemSubscription::class), 'service_integration_id');
  }

  /**
   * Get the system subscriptions of the service integration
   *
   * @return void
   */
  public function system_payment_intents()
  {
    return $this->hasMany(ServiceIntegrationsContainerProvider::getFromConfig('system_charges_models.payment_intent', SystemPaymentIntent::class), 'service_integration_id');
  }







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
  public function hasActiveSystemChargesServiceIntegration()
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

  public function scopeWithSystemChargesServiceIntegration($query)
  {
    $payloadColumn = ServiceIntegrationsContainerProvider::getFromConfig('payload_column','payload');

    return $query->with([
      'service_integrations' => function($query) use ($payloadColumn){
        $query
          ->select('id',$payloadColumn,'owner_id','owner_type','active','name','short_name')
          ->where('name', ServiceIntegration::SYSTEM_CHARGES_SERVICE)
          ->where('short_name', ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME);
      }
    ]);
  }

  public function scopeWithActiveSystemChargesServiceIntegration($query)
  {
    $payloadColumn = ServiceIntegrationsContainerProvider::getFromConfig('payload_column','payload');

    return $query->with([
      'service_integrations' => function($query) use ($payloadColumn){
        $query
          ->select('id',$payloadColumn,'owner_id','owner_type','active','name','short_name')
          ->where('name', ServiceIntegration::SYSTEM_CHARGES_SERVICE)
          ->where('short_name',ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME)
          ->where('active', true);
      }
    ]);
  }


}
