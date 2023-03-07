<?php

namespace BlackSpot\SystemCharges\Relationships;

use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\SystemCharges\Models\SystemPaymentIntent;
use BlackSpot\SystemCharges\Models\SystemSubscription;

trait HasSystemPaymentIntents
{
  /**
   * Boot on delete method
   */
  public static function bootHasSystemPaymentIntents()
  {
    static::deleting(function ($model) {
        if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
            return;
        }
        $model->system_payment_intents()->delete();
    });
  }

  /**
  * Get the system_payment_intents
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
  */
  public function system_payment_intents()
  {
    return $this->morphMany(ServiceIntegrationsContainerProvider::getFromConfig('system_charges_models.payment_intent', SystemPaymentIntent::class), 'customer');
  }
}
