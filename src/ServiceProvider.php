<?php

namespace BlackSpot\SystemCharges;

use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\SystemCharges\Models\SystemPaymentIntent;
use BlackSpot\SystemCharges\SystemPaymentIntentObserver;
use Illuminate\Support\ServiceProvider as LaravelProvider;

class ServiceProvider extends LaravelProvider
{
    public const PACKAGE_NAME = 'system-charges';

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerPublishables();

        $paymentIntentClass = ServiceIntegrationsContainerProvider::getFromConfig('system_charges_models.payment_intent', SystemPaymentIntent::class);
        $paymentIntentClass = new '\\'.$paymentIntentClass;
        $paymentIntentClass::observe(SystemPaymentIntentObserver::class);
    }

    protected function registerConfig()
    {
        // Merge system charges config to ServiceIntegrationsContainerProvider config

        $this->mergeConfigFrom(__DIR__.'/../config/'.self::PACKAGE_NAME.'.php', ServiceIntegrationsContainerProvider::PACKAGE_NAME);
    }

    public static function getFromConfig($keys, $default = null)
    {
        return config(self::PACKAGE_NAME.'.'.$keys, $default);
    }

    protected function registerPublishables()
    {
        $this->publishes([
            __DIR__.'/../config/'.self::PACKAGE_NAME.'.php' => base_path('config/'.(self::PACKAGE_NAME).'.stub.php'),
        ], [self::PACKAGE_NAME.':config']);

        $this->publishes([
            __DIR__.'/../database/migrations' => base_path('database/migrations')
        ], [self::PACKAGE_NAME.':migrations']);
    }
}
