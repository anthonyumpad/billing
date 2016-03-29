<?php
/**
 * Chargeable Trait
 *
 * @author      jmrocela <johnr@Anthonyumpad.sg>
 * @copyright   2015 Anthonyumpad.sg
 * @TODO Refactor Parameters
 */

namespace Anthonyumpad\Billing\Traits;

use Carbon\Carbon;
use Anthonyumpad\Billing\Models\Subscription;

/**
 * SubscriptionPayments Trait
 *
 * SubscriptionPayments Trait to be used in a "Subscription"-esque model for handling
 * recurring payments against a subscription. Application of this trait depends
 * on application of the Billable trait to a related model.
 */
trait SubscriptionTrait
{
    /**
     * @var Anthonyumpad/Billing/Repositories/SubscriptionRepository
     */
    protected $subscriptionRepository;

    /**
     * subscribe
     *
     * This creates a subscription record for the billable object.
     *
     * @param $billableId
     * @param $subscriptionData
     * @return Subscription $subscription
     *
     * @throws \Exception
     */
    public function subscribe($billableId, array $subscriptionData)
    {
        try {
            $subscription = $this->subscriptionRepository->subscribe($billableId, $subscriptionData);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $subscription;
    }

    /**
     * unsubscribe
     *
     * This updates the billable model's subscription status to cancelled.
     * @param $billableId
     *
     * @return bool
     */
    public function unsubscribe($billableId)
    {
        try {
            $result = $this->subscriptionRepository->unsubscribe($billableId);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     * autoCharge
     *
     * This performs the auto-charges for all subscriptions that are due.
     *
     * @return void
     */
    public function autoCharge()
    {
        $this->subscriptionRepository->autoCharge();
    }
}
