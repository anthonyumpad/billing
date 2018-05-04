<?php
/**
 * Card Create Event
 */
namespace Anthonyumpad\Billing\Events\Card;

use Illuminate\Queue\SerializesModels;

/**
 * Card Create Event
 */
class Create
{

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($billableId = null,$paymenttokenId = null)
    {
        $this->billableId     = $billableId;
        $this->paymenttokenId = $paymenttokenId;
    }
}
