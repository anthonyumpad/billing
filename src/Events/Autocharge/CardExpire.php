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
    public function __construct($subscription_id = null)
    {
        $this->subscription_id = $recur_id;
    }
}
