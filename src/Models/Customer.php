<?php
/**
 * Customer model
 *
 * @author     Anthony Umpad
 * @version    1.0
 */

namespace Anthonyumpad\Billing\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Omnipay\Common\Message\ResponseInterface;
use Anthonyumpad\Billing\Models\PaymentToken;

/**
 * Customer model
 *
 * Stores the necessary parameters to hold a customer in the payment gateway.
 */
class Customer extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;

    public $fillable = [
        'gateway_id',
        'billable_id',
        'token',
        'extended_attributes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'extended_attributes' => 'array'
    ];

    /**
     * Many:1 relationship with Gateway
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function gateway()
    {
        return $this->belongsTo('Anthonyumpad\Billing\Models\Gateway');
    }

    /**
     * 1:Many relationship with PaymentToken
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function paymenttokens()
    {
        return $this->hasMany('Anthonyumpad\Billing\Models\PaymentToken');
    }
}
