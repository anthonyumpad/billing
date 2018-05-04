<?php
/**
 * Autocharge Payment Failed Event
 */

namespace Anthonyumpad\Billing\Events\Autocharge;

use Illuminate\Queue\SerializesModels;

/**
 * Autocharge Payment Failed Event
 */
class Failed
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
