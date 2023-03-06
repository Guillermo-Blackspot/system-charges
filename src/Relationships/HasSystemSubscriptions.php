<?php

namespace BlackSpot\SystemCharges\Relationships;

use App\Models\Charges\SystemSubscription;

trait HasSystemSubscriptions
{
  /**
   * Boot on delete method
   */
  public static function bootHasSystemSubscriptions()
  {
    static::deleting(function ($model) {
        if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
            return;
        }
        $model->system_subscriptions()->delete();
    });
  }


  /**
   * Determe if the model has a suscription with the identifier 
   * in the local database
   *
   * @return bool
   */
  public function isSubscribedWithSystemChargesTo($identifier)
  {
    return $this->system_subscriptions()->where('identified_by', $identifier)->exists();
  }

  /**
   * Find one subscription that belongs to the model by identifier 
   * in the local database
   *
   * @return BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationSubscription
   */
  public function findSystemSubscriptionByIdentifier($identifier, $with = [])
  {
    return $this->system_subscriptions()->with($with)->where('identified_by', $identifier)->first();
  }


  /**
  * Get the system_subscriptions
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
  */
  public function system_subscriptions()
  {
    return $this->morphMany(SystemSubscription::class, 'owner');
  }
}
