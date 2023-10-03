<?php

namespace BlackSpot\SystemCharges\Models;

use BlackSpot\ServiceIntegrationsContainer\Models\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\SystemCharges\Concerns\ManagesCredentials;
use BlackSpot\SystemCharges\Models\SystemSubscription;
use Illuminate\Database\Eloquent\Model;

class SystemPaymentIntent extends Model
{   
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_payment_intents';
    public const TABLE_NAME = 'system_payment_intents';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    public const SYSTEM_CHARGES_SERVICE_NAME = 'System_Charges';

    /**
     * Overwrite cast json method
     */
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
    
    public function getReadableStatusAttribute()
    {        
        if ($this->status == 'pen') {
            return 'Pendiente';
        }else if ($this->status == 'pai') {
            return 'Pagado';
        }else if ($this->status == 'exp') {
            return 'Expirado';
        }else{
            return 'No válido';
        }
    }

    public function getReadablePaymentMethodAttribute()
    {
        if ($this->payment_method == 'wire_t') {
            return 'Transferencia bancaria';
        }elseif ($this->payment_method == 'in_sub') {
            return 'Pago en sucursal';            
        }elseif ($this->payment_method == 'agree') {
            return 'Pago por acuerdo (Otro)';
        }else{
            return 'No válido';
        }
    }

    /**
     * Eloquent relationships
     */

    public function owner()
    {
        return $this->morphTo('owner');
    }

    public function system_subscription()
    {
        return $this->belongsTo(ServiceIntegrationsContainerProvider::getFromConfig('system_charges_models.subscription', SystemSubscription::class), 'system_subscription_id');
    }

    public function service_integration()
    {
        return $this->belongsTo(ServiceIntegrationsContainerProvider::getFromConfig('model', ServiceIntegration::class), 'service_integration_id');
    }
}
