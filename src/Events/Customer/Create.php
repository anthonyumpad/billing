<?php
/**
 * Customer Create Event
 */

namespace Anthonyumpad\Billing\Events\Customer;

use Illuminate\Queue\SerializesModels;

/**
 * Customer Create Event
 */
class Create
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
