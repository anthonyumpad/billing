<?php
/**
 * SubscriptionRepository
 *
 * @author     Anthony Umpad
 * @version    1.0
 */

namespace Anthonyumpad\Billing\Repositories;

use Anthonyumpad\Billing\Models\Payment;
use Anthonyumpad\Billing\Models\Subscription;
use Anthonyumpad\Billing\Models\Gateway;
use Anthonyumpad\Billing\Events\Charge\Success as ChargeSuccess;
use Anthonyumpad\Billing\Events\Charge\Failed  as ChargeFailed;

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
        'DAY'   => 'D',
        'DAYS'  => 'D',
        'WEEK'  => 'W',
        'MONTH' => 'M',
        'YEAR'  => 'Y'
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
        $intervalType = (! empty($subscriptionData['intervalType']))  ? $subscriptionData['intervalType'] : Subscription::DAYS_INTERVAL;
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

        if (! isset($this->intervalTypeCode[strtoupper($intervalType)])) {
            throw new \Exception('Invalid subscription interval type.');
        }

        if ($interval <= 0) {
            throw new \Exception('Interval value should be greater than 0.');
        }

        $subscription = Subscription::where('billableId', $billableId)
            ->where('status', Subscription::ACTIVE)
            ->first();

        if (is_null($nextAttempt)) {
            $nextAttempt = new \DateTime();
            $nextAttempt->add(new \DateInterval('P'.$interval.$this->intervalTypeCode[$intervalType]));
        }

        try {
            if (empty($subscription)) {
                return Subscription::create([
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

            $subscription['is_auto_renew'] = 0;
            $subscription->next_attempt    = '0000-00-00 00:00:00';
            $subscription->status          = Subscription::CANCELLED;
            $subscription->save();

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return true;

    }
}