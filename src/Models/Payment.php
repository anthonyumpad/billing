<?php
/**
 * Payment Model Class
 *
 * @author     Anthony Umpad
 * @version    1.0
 */

namespace Anthonyumpad\Billing\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;

/**
 * Payment Model Class
 *
 * Stores the necessary data for payment transactions.
 */
class Payment extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;

    const APPROVED            = 'APPROVED';
    const CANCELLED           = 'CANCELLED';
    const DECLINED            = 'DECLINED';
    const ERROR               = 'ERROR';
    const REFUNDED            = 'REFUNDED';
    const PROCESSING          = 'PROCESSING';
    const PENDING             = 'PENDING';
    const SUCCESS             = 'SUCCESS';
    const CHARGE_BACK         = 'CHARGE-BACK';
    const METHOD_CARD         = 'CARD';
    const METHOD_CARD_TOKEN   = 'CARD_TOKEN';
    const METHOD_AUTOCHARGE   = 'AUTOCHARGE';

    public $fillable = [
        'chargeable_id',
        'billable_id',
        'paymenttoken_id',
        'subscription_id',
        'gateway_id',
        'amount',
        'amount_usd',
        'currency',
        'amount_not_refunded',
        'method',
        'service',
        'platform',
        'transaction_date',
        'transaction_reference',
        'transaction_details',
        'extended_attributes',
        'status'
    ];

    protected $casts = [
        'extended_attributes' => 'array'
    ];

    protected $hidden = ['billable_id'];
    protected $dates  = ['transaction_date'];

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

    /**
     * 1:Many relationship with recurring.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscription()
    {
        return $this->hasMany('Anthonyumpad\Billing\Models\Subscription');
    }
}
