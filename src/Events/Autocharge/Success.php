<?php
/**
 * Autocharge Payment Success Event
 */

namespace Anthonyumpad\Billing\Events\Autocharge;

use Illuminate\Queue\SerializesModels;

/**
 * Autocharge Payment Success Event
 */
class Success
{

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($paymentId = null, $subscriptionId = null)
    {
        $this->paymentId       = $paymentId;
        $this->subscriptionId  = $subscriptionId;
    }
}
