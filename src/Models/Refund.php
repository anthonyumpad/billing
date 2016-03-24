<?php
/**
 * Refund Model Class
 *
 * @author     Anthony Umpad
 * @version    1.0
 */

namespace Anthonyumpad\Billing\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Refund Model Class
 *
 * Holds the necessary data for refund transactions, that relate to a refunded or partially refunded payment.
 */
class Refund extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;

    const COMPLETED   = 'COMPLETED';
    const CANCELLED   = 'CANCELLED';
    const ERROR       = 'ERROR';
    const PENDING     = 'PENDING';
    const SUCCESS     = 'SUCCESS';
    const PARTIAL     = 'PARTIAL';

    public $fillable = [
        'chargeable_id',
        'billable_id',
        'payment_id',
        'paymenttoken_id',
        'gateway_id',
        'amount',
        'transaction_date',
        'transaction_reference',
        'transaction_details',
        'extended_attributes',
        'status'
    ];

    protected $casts = [
        'extended_attributes' => 'array'
    ];


    /**
     * Many:1 relationship with Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function chargeable()
    {
        return $this->belongsTo(Config::get('billing.chargeable_model'));
    }

    /**
     * Many:1 relationship with User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function billable()
    {
        return $this->belongsTo(Config::get('billing.billable_model'));
    }

    /**
     * Many:1 relationship with Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment()
    {
        return $this->belongsTo('Anthonyumpad\Billing\Models\Payment');
    }

    /**
     * Many:1 relationship with PaymentToken
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymenttoken()
    {
        return $this->belongsTo('Anthonyumpad\Billing\Models\PaymentToken');
    }

    /**
     * Many:1 relationship with Gateway
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function gateway()
    {
        return $this->belongsTo('Anthonyumpad\Billing\Models\Gateway');
    }
}
