<?php
/**
 * PaymentToken model
 *
 * @author     Anthony Umpad
 * @version    1.0
 */

namespace Anthonyumpad\Billing\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PaymentToken model
 *
 * Stores the necessary parameters to hold a payment token in the payment gateway.
 */
class PaymentToken extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;

    public $table = 'paymenttokens';

    public $fillable = [
        'customer_id',
        'billable_id',
        'token',
        'is_default',
        'start_date',
        'expiry_date',
        'brand',
        'created_by',
        'updated_by',
        'extended_attributes'
    ];

    protected $casts = [
        'is_default'          => 'bool',
        'extended_attributes' => 'array'
    ];

    /**
     * Many:1 relationship with Customer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo('Anthonyumpad\Billing\Models\Customer');
    }
}
