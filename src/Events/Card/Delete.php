<?php
/**
 * Card Delete Event
 */

namespace Anthonyumpad\Billing\Events\Card;

use Illuminate\Queue\SerializesModels;

/**
 * Card Delete Event
 */
class Delete
{

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($billableId = null, $paymenttokenId = null)
    {
        $this->billableId     = $billableId;
        $this->paymenttokenId = $paymenttokenId;
    }
}
