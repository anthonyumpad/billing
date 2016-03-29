<?php
/**
 * Autocharge Payment Defaulted Event
 */

namespace Anthonyumpad\Billing\Events\Autocharge;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;

/**
 * Autocharge Payment Defaulted Event
 */
class Defaulted extends Event
{

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($billableId  = null)
    {
        $this->billableId = $billableId;
    }
}
