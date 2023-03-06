<?php

namespace BlackSpot\SystemCharges\Models;

use App\Actions\Charges\SystemPaymentIntent\GenerateTrackingNumber;
use App\Events\Quotes\SystemPaymentIntentCreated;
use App\Models\Morphs\ActionsHistory;
use App\Models\Sales\Quote;
use App\Models\Morphs\ServiceIntegration;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kirschbaum\PowerJoins\PowerJoins;

class SystemPaymentIntent extends Model
{
    use PowerJoins;
    
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

    // public function setDueDateAttribute($value = null)
    // {
    //     if ($value != null) {
    //         $this->attributes['due_date'] = ;
    //     }else{
    //         $this->attributes['due_date'] = null;
    //     }
    //}

    /**
     * Resolve the power join type using alias
     */
    private function resolvePowerJoinType($type)
    {
        if ($type == 'left') {
            return 'leftJoinRelationshipUsingAlias';
        }elseif ($type == 'right') {
            return 'rightJoinRelationshipUsingAlias';
        }else {
            return 'joinRelationshipUsingAlias';
        }
    }
    
    /**
     * Join the customer user related to the quotation 
     */
    public function scopeJoinCustomerUser($query, $joinType = 'inner')
    {
        return $query->selectRaw('
            cus_u.id as customer_user_id,
            cus_u.email as customer_user_email,
            CONCAT(cus_u.name," ",cus_u.last_name) as customer_user_full_name
        ')
        ->{$this->resolvePowerJoinType($joinType)}('customer_user','cus_u');
    }

    /**
     * Eloquent relationships
     */

    public function customer_user()
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function service_integration()
    {
        return $this->belongsTo(ServiceIntegration::class, 'service_integration_id');
    }
}
