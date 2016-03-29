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

    public function __construct($billableId = null, $customerId = null, $paymentId = null, $data = [], $subscription = false)
    {
        $this->billableId  = $billableId;
        $this->customerId  = $customerId;
        $this->paymentId   = $paymentId;
        $this->data        = $data;
    }
}
