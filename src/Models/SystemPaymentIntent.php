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

    // Payment methods
    public const WIRE_TRANSFER = 'wire_t';
    public const PAY_IN_SUBSIDIARY = 'in_sub';
    public const AGREEMENT = 'agree';
    public const PAYMENT_AGAINST_DELIVERY = 'pay_ag_del';

    // Status
    public const PENDING_STATUS = 'pen';
    public const PAID_STATUS = 'pai';
    public const EXPIRED_STATUS = 'exp';


    /**
     * Overwrite cast json method
     */
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public static function getSupportedPaymentMethods()
    {
        return  [
            self::WIRE_TRANSFER, 
            self::PAY_IN_SUBSIDIARY, 
            self::AGREEMENT, 
            self::PAYMENT_AGAINST_DELIVERY
        ];
    }
    
    public function getReadableStatusAttribute()
    {        
        if ($this->status == self::PENDING_STATUS) {
            return 'Pendiente';
        }else if ($this->status == self::PAID_STATUS) {
            return 'Pagado';
        }else if ($this->status == self::EXPIRED_STATUS) {
            return 'Expirado';
        }else{
            return 'No válido';
        }
    }

    public function getReadablePaymentMethodAttribute()
    {
        if ($this->payment_method == self::WIRE_TRANSFER) {
            return 'Transferencia bancaria';
        }elseif ($this->payment_method == self::PAY_IN_SUBSIDIARY) {
            return 'Pago en sucursal';            
        }elseif ($this->payment_method == self::AGREEMENT) {
            return 'Pago por acuerdo (Otro)';
        }elseif ($this->payment_method == self::PAYMENT_AGAINST_DELIVERY) {
            return 'Pago contra entrega';
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
