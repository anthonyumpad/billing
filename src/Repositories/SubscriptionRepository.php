<?php
/**
 * SubscriptionRepository
 *
 * @author     Anthony Umpad
 * @version    1.0
 */

namespace Anthonyumpad\Billing\Repositories;

use Anthonyumpad\Billing\Models\Subscription;
use Anthonyumpad\Billing\Events\Autocharge\Success as AutochargeSuccess;
use Anthonyumpad\Billing\Events\Autocharge\Failed  as AutochargeFailed;
use Anthonyumpad\Billing\Events\Autocharge\Retry   as AutochargeRetry;
use Anthonyumpad\Billing\Events\Autocharge\Defaulted;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;

/**
 * Class SubscriptionRepository
 *
 * This implements all functions for subscriptions.
 *
 * @package Anthonyumpad\Billing\Repositories
 */
class SubscriptionRepository
{
    protected $intervalTypeCode = [
        'DAY'    => 'D',
        'DAYS'   => 'D',
        'WEEK'   => 'W',
        'WEEKS'  => 'W',
        'MONTH'  => 'M',
        'MONTHS' => 'M',
        'YEAR'   => 'Y',
        'YEARS'  => 'Y'
    ];

    /**
     * SubscriptionRepository constructor.
     *
     * Injects BillingRepository into SubscriptionRepository
     *
     * @param BillingRepository $billingRepository
     */
    public function __construct(BillingRepository $billingRepository)
    {
        $this->billingRepository  = $billingRepository;
    }

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
    public function subscribe($billableId, $subscriptionData)
    {
        $paymentToken = $this->billingRepository->getDefaultCard($billableId);

        if (empty($paymentToken)) {
            throw new \Exception('Cannot get default payment token.');
        }

        $nextAttempt  = (! empty($subscriptionData['nextAttempt']))   ? $subscriptionData['nextAttempt']  : null;
        $interval     = (! empty($subscriptionData['interval']))      ? $subscriptionData['interval']     : 0;
        $intervalType = (! empty($subscriptionData['intervalType']))  ? strtoupper($subscriptionData['intervalType']) : Subscription::DAYS_INTERVAL;
        $data         = (! empty($subscriptionData['data']))          ? $subscriptionData['data']         : [];

        if (empty($interval)
        ) {
            throw new \Exception('Please provide an interval');
        }

        if (empty($data)) {
            throw new \Exception('Please provide the subscription data.');
        }

        if (empty($data['amount'])      ||
            empty($data['currency'])    ||
            empty($data['description']) ||
            empty($data['packageId'])   ||
            empty($data['packageName'])
        ) {
            throw new \Exception('Please provide a subscription data amount, currency, description, packageId and packageName');
        }

        if (! isset($this->intervalTypeCode[$intervalType])) {
            throw new \Exception('Invalid subscription interval type.');
        }

        if ($interval <= 0) {
            throw new \Exception('Interval value should be greater than 0.');
        }

        $subscription = Subscription::where('billable_id', $billableId)
            ->where('status', Subscription::ACTIVE)
            ->first();


        if (is_null($nextAttempt)) {
            $nextAttempt = new \DateTime();
            $nextAttempt->add(new \DateInterval('P'.$interval.$this->intervalTypeCode[$intervalType]));
        }

        try {
            if (empty($subscription)) {
                $subscription = Subscription::create([
                    'billable_id'       => $billableId,
                    'chargeable_id'     => (! empty($data['packageId'])) ? $data['packageId'] : null,
                    'customer_id'       => $paymentToken->customer_id,
                    'paymenttoken_id'   => $paymentToken->id,
                    'amount'            => (! empty($data['amount']))   ? $data['amount']   : 0.00,
                    'currency'          => (! empty($data['currency'])) ? $data['currency'] : 'USD',
                    'ran'               => 0,
                    'interval'          => (int) $interval,
                    'interval_type'     => $intervalType,
                    'failed_attempts'   => 0,
                    'next_attempt'      => $nextAttempt,
                    'defaulted'         => false,
                    'status'            => Subscription::ACTIVE,
                    'data'              => $data
                ]);
            } else {
                $subscription->customer_id      = $paymentToken->customer_id;
                $subscription->chargeable_id    = (! empty($data['packageId'])) ? $data['packageId'] : '';
                $subscription->paymenttoken_id  = $paymentToken->id;
                $subscription->amount           = (! empty($data['amount']))   ? $data['amount'] : '';
                $subscription->currency         = (! empty($data['currency'])) ? $data['currency'] : 'USD';
                $subscription->interval         = $interval;
                $subscription->interval_type    = $intervalType;
                $subscription->next_attempt     = $nextAttempt;
                $subscription->defaulted        = false;
                $subscription->data             = $data;
                $subscription->save();
            }
        } catch (\Exception $e) {
            throw new \Exception ($e->getMessage(), $e->getCode());
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
            $subscription = Subscription::where('billable_id', $billableId)
                ->where('status', Subscription::ACTIVE)
                ->first();

            if(empty($subscription)) {
                throw new \Exception('Billable model does not have an active subscription');
            }

            $subscription->next_attempt    = '0000-00-00 00:00:00';
            $subscription->status          = Subscription::CANCELLED;
            $subscription->save();

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return true;

    }

    /**
     * Attempt auto-charge for subscriptions that are due.
     *
     * This goes through all the subscription data where the next attempt is due
     * and performs the charge.
     *
     * @return void
     */
     public function autoCharge()
     {
         $subscriptions = Subscription::where('next_attempt', '<=', new \DateTime)
             ->where('next_attempt', '!=', '0000-00-00 00:00:00')
             ->where('defaulted', false)
             ->where('status', Subscription::ACTIVE)
             ->with('paymenttoken', 'billable')
             ->get();


         foreach ($subscriptions as $subscription) {
             $card       = $subscription->paymenttoken;
             $billable   = $subscription->billable;

             if (empty($subscription->billable_id) && empty($card)) {
                 continue;
             }

             try {
                 $purchaseDetails                   = $subscription->data;
                 $purchaseDetails['cardReference']  = $card->token;

                 $payment = $this->billingRepository->purchase($subscription->billable_id, $subscription->data);
                 $ran     = $subscription->ran + 1;
                 $subscription->update([
                     'ran'          => $ran,
                     'last_attempt' => new \DateTime
                 ]);
                 $subscription->failed_attempts = 0;
                 $subscription->save();
                 Event::fire(new AutochargeSuccess($payment->id, $subscription->id));
             } catch (\Exception $e) {
                 if ($subscription->failed_attempts >= Config::get('billing.retry_attempts')) {
                     $subscription->update([
                         'defaulted'    => true,
                         'next_attempt' => null,
                         'last_attempt' => new \DateTime
                     ]);

                     Event::fire(new Defaulted($billable->id));
                 } else {
                     $intervals = Config::get('billing.retry_interval');
                     $subscription->update([
                         'failed_attempts' => $subscription->failed_attempts + 1,
                         'defaulted'       => false,
                         'next_attempt'    => new \DateTime('+' . $intervals[$subscription->failed_attempts] . ' days'),
                         'last_attempt'    => new \DateTime
                     ]);
                     Event::fire(new AutochargeRetry($subscription->id));
                 }
                 Event::fire(new AutochargeFailed($subscription->id));
             }
         }
     }
}