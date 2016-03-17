<?php
/**
 * Subscription model
 *
 * @author     Anthony Umpad
 * @version    1.0
 */

namespace Anthonyumpad\Billing\Models;

use Anthonyumpad\Billing\Events\Autocharge\Success;
use Anthonyumpad\Billing\Events\Autocharge\Defaulted;
use Anthonyumpad\Billing\Events\Autocharge\Failed;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;

/**
 * Recurring model
 *
 * Handles data for recurring payments.
 */
class Subscription extends \Illuminate\Database\Eloquent\Model
{
    use Elocrypt;

    const DAY_INTERVAL     = 'DAY';
    const DAYS_INTERVAL    = 'DAYS';
    const WEEK_INTERVAL    = 'WEEK';
    const MONTH_INTERVAL   = 'MONTH';
    const YEAR_INTERVAL    = 'YEAR';
    const CREATED   = 'CREATED';
    const ACTIVE    = 'ACTIVE';
    const INACTIVE  = 'INACTIVE';
    const DELETED   = 'DELETED';
    const ERROR     = 'ERROR';
    const CANCELLED = 'CANCELLED';
    const SUCCESS   = 'SUCCESS';
    const SUSPENDED = 'SUSPENDED';

    public $fillable = [
        'billable_id',
        'chargeable_id',
        'customer_id',
        'paymenttoken_id',
        'amount',
        'currency',
        'repeat',
        'ran',
        'interval',
        'interval_type',
        'failed_attempts',
        'next_attempt',
        'last_attempt',
        'defaulted',
        'data',
        'status'
    ];

    protected $dates = [
        'next_attempt',
        'last_attempt'
    ];

    protected $hidden = [
        'id',
        'chargeable_id',
        'billable_id',
        'customer_id'
    ];

    protected $casts = [
        'data' => 'array'
    ];


    /**
     * Many:1 relationship with Billable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function billable()
    {
        return $this->belongsTo(Config::get('billing.billable_model'));
    }

    /**
     * Many:1 relationsiip with Customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo('Anthonyumpad\Billing\Models\Customer');
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
     * 1: Many relationship with Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\Payment
     */
    public function payments()
    {
        return $this->hasMany('Anthonyumpad\Billing\Models\Payment');
    }
}
