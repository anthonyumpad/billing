<?php
/**
 * Refund Failed Event
 */

namespace Anthonyumpad\Billing\Events\Refund;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;

/**
 * Refund Failed Event
 */
class Failed extends Event
{

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($paymentId = null)
    {
        $this->paymentId = $paymentId;
    }
}
