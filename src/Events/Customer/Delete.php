<?php
/**
 * Customer Delete Event
 */

namespace Anthonyumpad\Billing\Events\Customer;

use Illuminate\Queue\SerializesModels;

/**
 * Customer Delete Event
 */
class Delete
{

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($billableId = null, $customerId = null)
    {
        $this->billableId  = $billableId;
        $this->customerId  = $customerId;
    }
}
