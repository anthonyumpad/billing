<?php
/**
 * Charge Success Event
 */

namespace Anthonyumpad\Billing\Events\Charge;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;

/**
 * Charge Success Event
 */
class Success extends Event
{
    use SerializesModels;

    public function __construct($billable_id = null, $customer_id = null, $payment_id = null, $data = [], $subscription = false)
    {
        $this->billable_id     = $billable_id;
        $this->customer_id     = $customer_id;
        $this->payment_id      = $payment_id;
        $this->is_subscription = $subscription;
        $this->data            = $data;
    }
}
