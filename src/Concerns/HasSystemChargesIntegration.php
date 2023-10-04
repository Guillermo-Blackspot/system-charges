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
	protected $systemChargeServiceInstance;

	/**
	 * Boot on delete method
	 */
	public static function bootHasSystemChargesIntegrations()
	{
		static::deleting(function ($model) {		
			if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
				return;
			}

			$model->system_payment_intents()->update(['service_integration_id' => null]);
		});
	}

	public function getSystemChargesAttribute()
	{
		if ($this->systemChargeServiceInstance !== null) {
			return $this->systemChargeServiceInstance;
		}

		return $this->systemChargeServiceInstance = new SystemChargesService($this);
	}
}
