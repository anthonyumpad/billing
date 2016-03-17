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
    protected $intervalTypes = [
        'DAY'   => 1,
        'DAYS'  => 1,
        'WEEK'  => 1,
        'MONTH' => 1,
        'YEAR'  =>1
    ];

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
        $paymentToken = $this->getDefaultCard($billableId);

        if (empty($paymentToken)) {
            throw new \Exception('Cannot get default payment token.');
        }

        $nextAttempt  = (! empty($subscriptionData['nextAttempt']))   ? $subscriptionData['nextAttempt']  : null;
        $interval     = (! empty($subscriptionData['interval']))      ? $subscriptionData['interval']     : 0;
        $intervalType = (! empty($subscriptionData['intervalType']))  ? $subscriptionData['intervalType'] : null;
        $data         = (! empty($subscriptionData['data']))          ? $subscriptionData['data']         : [];

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

        if (! isset($this->intervalTypes[strtoupper($intervalType)])) {
            throw new \Exception('Invalid subscription interval type.');
        }

        if ($interval <= 0) {
            throw new \Exception('Interval value should be greater than 0.');
        }

        $subscription = Subscription::where('billableId', $this->id)
            ->where('status', Subscription::ACTIVE)
            ->first();

        if (is_null($nextAttempt)) {
            $nextAttempt = new \DateTime('+' . $interval . ' ' . $intervalType);
        }

        if (empty($subscription)) {
            return $this->recurring()->create([
                'billable_id'       => $billableId,
                'chargeable_id'     => (! empty($data['packageId'])) ? $data['packageId'] : '',
                'customer_id'       => $paymentToken->customer_id,
                'paymenttoken_id'   => $paymentToken->id,
                'amount'            => (! empty($data['amount']))   ? $data['amount']   : '',
                'currency'          => (! empty($data['currency'])) ? $data['currency'] : 'USD',
                'ran'               => 0,
                'interval'          => $interval,
                'interval_type'     => $intervalType,
                'failed_attempts'   => 0,
                'next_attempt'      => $nextAttempt,
                'last_attempt'      => null,
                'defaulted'         => false,
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
                ->('status', Subscription::ACTIVE)
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

    /**
     * Refund a Transaction
     *
     * @param Payment $transaction
     * @param float $amount
     * @return Refund
     * @throws \Exception
     */
    public function refund($transaction, $amount = null)
    {
        // Billable model should be configurable
        if (!method_exists($this, 'asCustomer')) {
            throw new \Exception('This model is not associated to a Billable trait.');
        }

        // There should be a better way to get a gateway
        $app = app();
        $gateway_name = $transaction->gateway()->name;
        $gateway = $app['billing.gateways'][$gateway_name];

        // check the amount to be refunded based on amount_not_refunded
        if (empty($amount) || $amount > $transaction->amount_not_refunded) {
            $refund_amount = $transaction->amount_not_refunded;
        } else {
            $refund_amount = $amount;
        }

        $response = $gateway->refund(array(
            'amount'                   => number_format($refund_amount, 2, '.', ''),
            'transactionReference'     => $transaction->transaction_reference,
        ))->send();

        $billable = $this;

        if ($response->isSuccessful()) {

            // Adjust the purchase transaction to record the non refunded amount
            $remaining_amount = $transaction->amount_not_refunded - $refund_amount;
            $transaction->status = $remaining_amount == 0 ? Payment::REFUNDED : Payment::PARTIAL;
            $transaction->amount_not_refunded = $remaining_amount;
            $transaction->save();

            // Save a refund transaction in the database.
            $response = Refund::create([
                'billable_id'           => $billable->id,
                'chargeable_id'         => $transaction->chargeable_id,
                'payment_id'            => $transaction->id,
                'paymenttoken_id'       => $transaction->paymenttoken_id,
                'gateway_id'            => $transaction->gateway_id,
                'amount'                => $refund_amount,
                'service'               => $transaction->service,
                'transaction_date'      => new \DateTime,
                'transaction_reference' => $response->getTransactionReference(),
                'transaction_details'   => 'Refund of transaction ID ' . $transaction->transaction_reference,
                'status'                => Refund::COMPLETED,
            ]);

            Event::fire(new RefundSuccess($transaction, $amount, $this));
            return $response;
        }

        Event::fire(new RefundFailed($transaction, $amount, $this));
        $data = $response->getData();
        throw new \Exception($response->getMessage());
    }
}