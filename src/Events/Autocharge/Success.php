<?php
/**
 * Autocharge Payment Success Event
 */

namespace Anthonyumpad\Billing\Events\Autocharge;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;

/**
 * Autocharge Payment Success Event
 */
class Success extends Event
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
