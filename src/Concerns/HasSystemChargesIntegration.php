<?php

namespace BlackSpot\SystemCharges\Concerns;

use BlackSpot\SystemCharges\Services\SystemChargesService;

/**
 * Use in the subsidiary models
 * 
 * @property public system_charges
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
