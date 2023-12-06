<?php

namespace BlackSpot\SystemCharges\Concerns;

use BlackSpot\ServiceIntegrationsContainer\ServiceIntegration;
use BlackSpot\SystemCharges\Services\SystemChargesService;

/**
 * Use in the subsidiary models
 * 
 * @property public system_charges
 */
trait HasSystemChargesIntegration
{
	protected $systemChargesServiceInstance;

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
		if ($this->systemChargesServiceInstance !== null) {
			return $this->systemChargesServiceInstance;
		}

		return $this->systemChargesServiceInstance = new SystemChargesService($this);
	}

	public function scopeWithSystemChargesServiceIntegration($query)
	{
		return $query->with([
			'service_integrations' => function($query) {
				$query->where('name', ServiceIntegration::SYSTEM_CHARGES_SERVICE)
					->where('short_name', ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME);
			}
		]);
	}

	public function scopeWhereHasSystemChargesServiceIntegration($query)
	{
		return $this->scopeWhereHasSystemChargesService($query);
	}

	public function scopeWhereHasSystemChargesService($query)
	{
		return $query->whereHas('service_integrations', function($query) {
			$query->where('name', ServiceIntegration::SYSTEM_CHARGES_SERVICE)
				->where('short_name', ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME);
		});
	}

	public function scopeWhereHasActiveSystemChargesServiceIntegration($query)
	{
		return $this->scopeWhereHasActiveSystemChargesService($query);
	}

	public function scopeWhereHasActiveSystemChargesService($query)
	{
		return $query->whereHas('service_integrations', function($query) {
			$query->where('name', ServiceIntegration::SYSTEM_CHARGES_SERVICE)
				->where('short_name', ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME)
				->where('active', true);
		});
	}
}
