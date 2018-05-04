<?php
/**
 * Autocharge Payment Retry Event
 */

namespace Anthonyumpad\Billing\Events\Autocharge;

use Illuminate\Queue\SerializesModels;

/**
 * Autocharge Payment Retry Event
 */
class Retry
{

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($subscriptionId = null)
    {
        $this->subscriptionId = $subscriptionId;
    }
}
