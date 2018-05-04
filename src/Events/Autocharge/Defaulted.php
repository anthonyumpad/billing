<?php
/**
 * Autocharge Payment Defaulted Event
 */

namespace Anthonyumpad\Billing\Events\Autocharge;

use Illuminate\Queue\SerializesModels;

/**
 * Autocharge Payment Defaulted Event
 */
class Defaulted
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
