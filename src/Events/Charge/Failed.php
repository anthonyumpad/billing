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
    public function __construct($billableId = null, $customerId = null, $paymentId = null, $data = [])
    {
        $this->billableId = $billableId;
        $this->customerId = $customerId;
        $this->paymentId  = $paymentId;
        $this->data       = $data;
    }
}
