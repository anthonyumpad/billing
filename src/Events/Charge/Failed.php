<?php
/**
 * Charge Failed Event
 */

namespace Anthonyumpad\Billing\Events\Charge;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;

/**
 * Charge Failed Event
 */
class Failed extends Event
{

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($billable_id = null, $customer_id = null, $payment_id = null, $data = [])
    {
        $this->billable_id = $billable_id;
        $this->customer_id = $customer_id;
        $this->payment_id  = $payment_id;
        $this->data        = $data;
    }
}
