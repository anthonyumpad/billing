<?php
/**
 * Autocharge Payment Failed Event
 */

namespace Anthonyumpad\Billing\Events\Autocharge;

use Illuminate\Queue\SerializesModels;
use App\Events\Event;

/**
 * Autocharge Payment Failed Event
 */
class CardExpire extends Event
{

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * Autocharge $subscription
     *
     * @return void
     */
    public function __construct($subscriptionId = null)
    {
        $this->subscriptionId = $subscriptionId;
    }
}
