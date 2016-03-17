<?php
/**
 * Refund Success Event
 */

namespace Anthonyumpad\Billing\Events\Refund;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;

/**
 * Refund Success Event
 */
class Success extends Event
{

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($refund_id = null)
    {
        $this->refund_id = $refund_id;
    }
}
