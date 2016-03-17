<?php
/**
 * Autocharge Payment Retry Event
 */

namespace Anthonyumpad\Billing\Events\Autocharge;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;

/**
 * Autocharge Payment Retry Event
 */
class Retry extends Event
{

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($subscription_id = null)
    {
        $this->subscription_id = $subscription_id;
    }
}
