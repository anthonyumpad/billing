<?php
/**
 * Autochargeable Trait
 *
 * @author      Del
 * @copyright   2015 Anthonyumpad.sg
 */

namespace Anthonyumpad\Billing\Traits;

use Anthonyumpad\Billing\Events\Charge\Success as ChargeSuccess;
use Anthonyumpad\Billing\Events\Charge\Failed as ChargeFailed;
use Anthonyumpad\Billing\Events\Refund\Success as RefundSuccess;
use Anthonyumpad\Billing\Events\Refund\Failed as RefundFailed;
use Anthonyumpad\Billing\Models\Gateway;
use Anthonyumpad\Billing\Models\Payment;
use Anthonyumpad\Billing\Models\PaymentToken;
use Illuminate\Support\Facades\Event;
use Omnipay\Common\CreditCard;
use Config;
use Log;

/**
 * TopupPayments Trait
 *
 * TopupPayments Trait is to be used where the billable model can be auto
 * charged based on a credit_balance field and a minimum_balance field.
 * Application of this trait depends on application of the Billable trait
 * to the same model as the TopupPayments trait.
 */
trait TopupTrait
{
    /**
     * @var Anthonyumpad/Billing/Repositories/BillingRepository
     */
    protected $billingRepository;

    /**
     * @var Anthonyumpad/Billing/Repositories/SubscriptionRepository
     */
    protected $subscriptionRepository;

    /**
     * @var Anthonyumpad/Billing/Repositories/TopupRepository
     */
    protected $topupRepository;
}
