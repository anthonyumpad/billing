<?php
/**
 * Billing
 *
 * @author     Anthony Umpad
 * @version    1.0
 */

namespace Anthonyumpad\Billing;

use Anthonyumpad\Billing\Traits\BillableTrait;
use Anthonyumpad\Billing\Traits\SubscriptionTrait;
use Anthonyumpad\Billing\Repositories\BillingRepository;
use Anthonyumpad\Billing\Repositories\SubscriptionRepository;

/**
 * Class Billing
 *
 * This is the entrypoint for fall Billing plugin functions
 *
 * @package Anthonyumpad\Billing
 */
class Billing implements BillingInterface
{
    use BillableTrait, SubscriptionTrait;

    /**
     * Constructor
     *
     * This injects all the repository class used by the traits.
     *
     * @param BillingRepository $billingRepository
     * @param SubscriptionRepository $subscriptionRepository
     * @param TopupRepository $topupRepository
     */
    public function __construct(BillingRepository $billingRepository, SubscriptionRepository $subscriptionRepository)
    {

        $this->billingRepository      = $billingRepository;
        $this->subscriptionRepository = $subscriptionRepository;
    }
}