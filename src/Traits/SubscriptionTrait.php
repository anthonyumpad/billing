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
     * Attempt Recurring Payments
     *
     * This goes through all the recurring data where the next attempt is defined
     * and performs the charge.
     *
     * @return void
     */
   /* public static function autochargeAttempt()
    {
        $recurring = Recurring::where('next_attempt', '<=', new \DateTime)
            ->where('next_attempt', '!=', '0000-00-00 00:00:00')
            ->where('agreement_id', null)
            ->where('defaulted', false)
            //->where('ran', '!=', 0)
            ->with('paymenttoken', 'billable')
            ->get();

        /*Log::info(__CLASS__.':'.__TRAIT__.':'.__FILE__.':'.__LINE__.':'.__FUNCTION__.':'.
            'Recurring', [
            'recurring'    => $recurring,
            'system_date'  => new \DateTime,
        ]);

        foreach ($recurring as $recur) {
            $card       = $recur->paymenttoken;
            $billable   = $recur->billable;
            $data       = $recur->data;

            /*Log::info(__CLASS__.':'.__TRAIT__.':'.__FILE__.':'.__LINE__.':'.__FUNCTION__.':'.
                'Recur', [
                'recur'    => $recur,
            ]);

            try {
                $payment = $billable->charge($card, $recur->recurring_amount, $data, null, true);
                //next_attempt should be handled in the charge success event of the site.
                $ran = $recur->ran + 1;
                $recur->update([
                    'ran'          => $ran,
                    'last_attempt' => new \DateTime
                ]);

                $recur->failed_attempts = 0;
                $recur->save();
                Event::fire(new RecurringSuccess($payment->id, $recur->id));
            } catch (\Exception $e) {
                Log::error(__CLASS__.':'.__TRAIT__.':'.__FILE__.':'.__LINE__.':'.__FUNCTION__.':'.
                    'Recurring Exception ', [
                    'message'    => $e->getMessage(),
                    'code'       => $e->getCode(),
                ]);

                if ($recur->failed_attempts >= Config::get('billing.retry_attempts')) {
                    $recur->update([
                        'defaulted'    => true,
                        'next_attempt' => null,
                        'last_attempt' => new \DateTime
                    ]);

                    Event::fire(new Defaulted($billable->id));
                } else {
                    $intervals = Config::get('billing.retry_interval');
                    $recur->update([
                        'failed_attempts' => $recur->failed_attempts + 1,
                        'defaulted'       => false,
                        'next_attempt'    => new \DateTime('+' . $intervals[$recur->failed_attempts] . ' days'),
                        'last_attempt'    => new \DateTime
                    ]);
                    Event::fire(new Retry($recur->id));
                }
                Event::fire(new RecurringFailed($recur->id));
            }
        }
    }*/

}
