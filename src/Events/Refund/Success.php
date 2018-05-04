<?php
/**
 * Refund Success Event
 */

namespace Anthonyumpad\Billing\Events\Refund;

use Illuminate\Queue\SerializesModels;

/**
 * Refund Success Event
 */
class Success
{

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($refundId = null)
    {
        $this->refund_id = $refundId;
    }
}
