<?php
/**
 * BillingRepository
 *
 * @author     Anthony Umpad
 * @version    1.0
 */

namespace Anthonyumpad\Billing\Repositories;

use Anthonyumpad\Billing\Models\Customer;
use Anthonyumpad\Billing\Models\Gateway;
use Anthonyumpad\Billing\Models\Customer;
use Anthonyumpad\Billing\Models\Gateway;
use Anthonyumpad\Billing\Models\PaymentToken;
use Anthonyumpad\Billing\Models\Subscription;
use Anthonyumpad\Billing\Events\Customer\Create   as CustomerCreate;
use Anthonyumpad\Billing\Events\Customer\Delete   as CustomerDelete;
use Anthonyumpad\Billing\Events\Card\Create       as CardCreate;
use Anthonyumpad\Billing\Events\Card\Delete       as CardDelete;
use Omnipay\Common\AbstractGateway;
use Omnipay\Common\CreditCard;
use Illuminate\Support\Facades\Event;
/**
 * Class BillingRepository
 *
 * This implements all functions related to all billing data manipulations.
 *
 * @package Anthonyumpad\Billing\Repositories
 */
class BillingRepository
{
    /**
     * Create the customer depending on the Gateway provided
     *
     * If a null gateway is provided then this function registers the
     * customer on all gateways that are known to the system.
     *
     * @param int   $billableId
     * @param array $customerData
     * @param AbstractGateway|Fluent|string $gateway
     * @return Customer
     *
     * @throws \Exception
     */
    public function createCustomer($billableId, array $customerData, $gateway = null)
    {
        if (empty($billableId)) {
            throw new \Exception('Empty billable id.');
        }

        $gateway_id = null;
        // get the default gateway if none is provided
        if (empty($gateway)) {
            $app      = app();
            $gateway  = $app['billing.gateway'];
        } else {
            $gateway = Gateway::getGateway($gateway);
        }

        if (empty($gateway)) {
            throw new \Exception('Cannot get default gateway.');
        }

        $gateway_id = $gateway->model->id;

        if (! method_exists($gateway, 'createCustomer')) {
            throw new \Exception('Default gateway does not support customer creation.');
        }

        try {
            $response = $gateway->createCustomer([
                'accountId' => $billableId,
                'email'     => (! empty($customerData['email'])) ? $customerData['email'] : '',
            ])->send();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }


        if (! $response->isSuccessful()) {
            throw new \Exception($response->getMessage(), $e->getCode());
        }

        $customer_reference   = $response->getCustomerReference();
        $extended_attributes  = $response->getData();

        $newCustomer = Customer::create([
            'gateway_id'            => $gateway_id,
            'billable_id'           => $billableId,
            'token'                 => $customer_reference,
            'extended_attributes'   => $extended_attributes,
            'created_by'            => (! empty($customerData['createdBy'])) ? $customerData['createdBy'] : 'system',
            'updated_by'            => (! empty($customerData['updatedBy'])) ? $customerData['updatedBy'] : 'system',
        ]);

        Event::fire(new CustomerCreate($billableId, $newCustomer->id));
        return $newCustomer;
    }

    /**
     * Get the Customer depending on the billable_id and  Gateway provided
     *
     * @param int   $billableId
     * @param AbstractGateway|Fluent|string $gateway
     * @return Customer
     *
     * @throws Exception
     */
    public function getCustomer($billableId, $gateway = null)
    {
        if (empty($billableId)) {
            throw new \Exception('Empty billable id.');
        }

        $gateway_id = null;
        // get the default gateway if none is provided
        if (empty($gateway)) {
            $app      = app();
            $gateway  = $app['billing.gateway'];
        } else {
            $gateway = Gateway::getGateway($gateway);
        }

        if (empty($gateway)) {
            throw new \Exception('Cannot get default gateway.');
        }

        $gateway_id = $gateway->model->id;

        $customer = $customer = $this->customers()
            ->where('billable_id', $billableId)
            ->where('gateway_id', '=', $gateway_id)
            ->first();

        if( empty($customer)) {
            throw new \Exception('Cannot find customer.');
        }

        return $customer;
    }

    /**
     * Delete the Customer
     *
     * If a null gateway is provided then this function will
     * delete the customer from all gateways.
     *
     * @param int   $billableId
     * @param AbstractGateway|Fluent|string|null $gateway
     * @return bool
     *
     * @throws \Exception
     */
    public function deleteCustomer($billableId, $gateway = null)
    {
        if (empty($billableId)) {
            throw new \Exception('Empty billable id.');
        }

        if (empty($gateway)) {
            $app = app();
            $gateway_list = $app['billing.gateways'];

            foreach ($gateway_list as $gateway) {
                if (! method_exists($gateway, 'deleteCustomer')) {
                   continue;
                }

                $gateway_id = $gateway->model->id;
                $customer   = Customer::where('billable_id', $billableId)
                    ->('gateway_id', $gateway_id)
                    ->first();

                if (empty($customer)) {
                    continue;
                }

                try {
                    $result = $gateway->deleteCustomer([
                        'customerReference' => $customer->token
                    ])->send();

                    $customer->paymenttokens()->delete();
                    $customer->delete();
                    Event::fire(new CustomerDelete($billableId, $customer->id));
                } catch (\Exception $e) {
                    continue;
                }
            }
            return true;
        }

        $gateway    = Gateway::getGateway($gateway);
        $gateway_id = $gateway->model->id;

        if (! method_exists($gateway, 'deleteCustomer')) {
            throw new \Exception('Gateway does not support delete customer');
        }

        $customer   = Customer::where('billable_id', $billableId)
            ->('gateway_id', $gateway_id)
            ->first();

        // Check to see that something was found
        if (empty($customer)) {
            throw new \Exception('Billable id does not have a customer record');
        }

        try {
            $response = $gateway->deleteCustomer([
                'customerReference' => $customer->token
            ])->send();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        if (! $response->isSuccessful()) {
            return false;
        }

        $customer->paymenttokens()->delete();
        $customer->delete();
        Event::fire(new CustomerDelete($billableId, $customer->id));
        return true;
    }

    /**
     * Create the CreditCard object
     *
     * Returns a PaymentToken object if the card is successfully registered
     * against the gateway.  If the gateway does not support card registration
     * then the return value will be null.
     *
     * If a null customer is provided then this function registers the card
     * on all gateways where the user is registered as a customer.
     *
     * The return result can be null if the gateway or one of the gateways
     * (where $customer is null) does not support createCard.
     *
     * @param int $billableId
     * @param array $cardInfo
     * @param bool $default
     * @param AbstractGateway|Fluent|string $gateway
     * @return PaymentToken|null
     *
     * @throws \Exception
     */
    public function createCard($billableId, array $cardInfo = [], $gateway = null)
    {
        if (empty($billableId)) {
            throw new \Exception('Empty billable id.');
        }

        $gateway_id = null;
        // get the default gateway if none is provided
        if (empty($gateway)) {
            $app      = app();
            $gateway  = $app['billing.gateway'];
        } else {
            $gateway = Gateway::getGateway($gateway);
        }

        if (empty($gateway)) {
            throw new \Exception('Cannot get default gateway.');
        }

        $gateway_id = $gateway->model->id;

        if (! method_exists($gateway, 'createCard')) {
            throw new \Exception('Gateway does not support create card');
        }

        $customer   =  Customer::where('billable_id', $billableId)
            ->('gateway_id', $gateway_id)
            ->first();

        if (empty($customer)) {
            $default = true;
            try {
                $customer = $this->createCustomer($billableId, [], $gateway);
            } catch(\Exception $e) {
                throw new \Exception($e->getMessage(), $e->getCode());
            }
        }

        if (empty($customer)) {
            throw new \Exception('Cannot create card, failed to create customer object.');
        }

        $cardInfo = array_merge([
            'firstName'             => null,
            'lastName'              => null,
            'number'                => null,
            'expiryMonth'           => null,
            'expiryYear'            => null,
            'cvv'                   => null,
            'email'                 => null,
            'billingAddress1'       => null,
            'billingCountry'        => null,
            'billingCity'           => null,
            'billingPostcode'       => null,
            'billingState'          => null,
            'billingPhone'          => null,
        ], $cardInfo);

        $card = new CreditCard($cardInfo);
        try {
            $response = $gateway->createCard([
                'card'              => $card,
                'customerReference' => $customer->token,
            ])->send();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        if (! $response->isSuccessful()) {
            throw new \Exception($response->getMessage(), $response->getCode());
        }

        try {
            $paymentToken = $this->createPaymentToken($billableId, $customer->id, $cardInfo, $response->getCardReference());
        } catch (\Exception $e) {
            throw new \Exception ($e->getMessage(), $e->getCode());
        }

        Event::fire(new CardCreate($billableId, $paymentToken->id));
        return $paymentToken;
    }

    /**
     * createPaymentToken
     *
     * This creates or updates a PaymentToken record in the database.
     *
     * @param $billableId
     * @param null $customerId
     * @param $cardInfo
     * @throws \Exception
     */
    public function createPaymentToken($billableId, $customerId = null, $cardInfo, $cardReference)
    {
        $default = false;
        if (empty($customerId)) {
            $default = true;
            try {
                $customer = $this->createCustomer($billableId, [], $gateway);
            } catch(\Exception $e) {
                throw new \Exception($e->getMessage(), $e->getCode());
            }
            $customerId = $customer->id;
        } else {
            $paymentTokenCount = PaymentToken::where('billable_id')->count();
            if ($paymentTokenCount == 0) {
                $default = true;
            }
        }

        $cardInfo = array_merge([
            'firstName'             => null,
            'lastName'              => null,
            'number'                => null,
            'expiryMonth'           => null,
            'expiryYear'            => null,
            'cvv'                   => null,
            'email'                 => null,
            'billingAddress1'       => null,
            'billingCountry'        => null,
            'billingCity'           => null,
            'billingPostcode'       => null,
            'billingState'          => null,
            'billingPhone'          => null,
        ], $cardInfo);

        $card = new CreditCard($cardInfo);

        // Need to expire the card on the last day of the expiry month.
        $aDate     = $cardInfo['expiryYear'] . '-' . $card_info['expiryMonth'] . '-01';
        $lastDate  = date('Y-m-t', strtotime($aDate));
        $firstName = (! empty($cardInfo['firstName']))  ? $cardInfo['firstName'] : '';
        $lastName  = (! empty($cardInfo['lastName']))   ? $cardInfo['lastName']  : '';
        $extendedAttributes = [
            'payment_token' =>   [
                'card'  => [
                    'name'    => $firstName . ' ' . $lastName,
                    'number'  => (! empty($cardInfo['number']))       ? substr($cardInfo['number'], -4) : '',
                    'phone'   => (! empty($cardInfo['billingPhone'])) ? $cardInfo['billingPhone']       : '',
                    'token'   => $response->getCardReference(),
                    'address' => [
                        'street'  => (! empty($cardInfo['billingAddress1'])) ? $cardInfo['billingAddress1'] : '',
                        'city'    => (! empty($cardInfo['billingCity']))     ? $cardInfo['billingCity']     : '',
                        'state'   => (! empty($cardInfo['billingState']))    ? $cardInfo['billingState']    : '',
                        'country' => (! empty($cardInfo['billingCountry']))  ? $cardInfo['billingCountry']  : '',
                        'zip'     => (! empty($cardInfo['billingPostCode'])) ? $cardInfo['billingPostCode'] : '',
                    ],
                    'expires' => $lastDate,
                ],
            ]
        ];

        $paymentToken = PaymentToken::where('token', $cardReference)->first();
        if (empty($paymentToken)) {
            try {
                $paymentToken = PaymentToken::create([
                    'token'       => $cardReference,
                    'billable_id' => $billableId,
                    'customer_id' => $customer->Id,
                    'is_default'  => $default,
                    'extended_attributes' => $extendedAttributes,
                    'brand'               => $card->getBrand(),
                    'start_date'          => new \DateTime(),
                    'expiry_date'         => new \DateTime($lastDate),
                    'created_by'          => (!empty($cardInfo['createdBy'])) ? $cardInfo['createdBy'] : 'system',
                    'updated_by'          => (!empty($cardInfo['updatedBy'])) ? $cardInfo['updatedBy'] : 'system',
                ]);
            } catch (\Exception $e) {
                throw new \Exception ($e->getMessage(), $e->getCode());
            }
        } else {
            try {
                $paymentToken->extended_attributes = $extendedAttributes;
                $paymentToken->brand               = $card->getBrand();
                $paymentToken->billable_id         = $billableId;
                $paymentToken->customer_id         = $customer->id;
                $paymentToken->start_date          = new \DateTime();
                $paymentToken->expiry_date         = new \DateTime($lastDate);
                $paymentToken->save();
            } catch (\Exception $e) {
                throw new \Exception ($e->getMessage(), $e->getCode());
            }
        }

        return $paymentToken;
    }

    /**
     * Get default card
     *
     * This gets and returns the default card for the given billableId
     *
     * @param int $billableId
     * @return PaymentToken|null
     *
     * @throws \Exception
     */
    public function getDefaultCard($billableId)
    {
        if (empty($billableId)) {
            throw new \Exception('Empty billable id.');
        }

        try {
            $paymentToken = PaymentToken::where('billable_id', $billableId)
                ->where('is_default', true)
                ->first();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $paymentToken;
    }

    /**
     * Set default card
     *
     * This sets the default card for the given billableId
     *
     * @param int $billableId
     * @return bool
     *
     * @throws \Exception
     */
    public function setDefaultCard($billableId, $cardReference)
    {
        if (empty($billableId)) {
            throw new \Exception('Empty billable id.');
        }

        try {
            $paymentToken = PaymentToken::where('token', $cardReference)
                ->('billable_id', $billableId)
                ->first();

            PaymentToken::where('billable_id', $billableId)->update([
                'is_default' => false
            ]);

            $paymentToken->update([
                'is_default' => true
            ]);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return true;
    }

    /**
     * Get all Cards
     *
     * @param $billableId
     * @return PaymentToken Collection|null
     *
     * @throws \Exception
     */
    public function getCards($billableId)
    {
        if (empty($billableId)) {
            throw new \Exception('Empty billable id.');
        }

        try {
            $result = PaymentToken::where('billable_id', $billableId)
                ->get();
        } catch (\Exception $e) {
            throw new \Exception ($e->getMessage(), $e->getCode());
        }
        return $result;
    }

    /**
     * Delete a Card
     *
     * @param int $billableId
     * @param text $cardReference
     * @return bool
     *
     * @throws \Exception
     */
    public function deleteCard($billableId, $cardReference)
    {
        if (empty($billableId)) {
            throw new \Exception('Empty billable id.');
        }

        // Get the payment token model object
        $paymentToken = PaymentToken::with('customer')
            ->where('token', $cardReference)
            ->('billable_id', $billableId)
            ->first();

        if (empty($paymentToken)) {
            throw new \Exception('Cannot find card.');
        }

        if (empty($paymentToken->customer)) {
            throw new \Exception('Card has no attached customer record.');
        }

        $customer = $paymentToken->customer;

        $subscription = Subscription::where('paymenttoken_id', $paymentToken->id)
            ->where('status', Subscription::ACTIVE)
            ->first();

        if (! empty($subscription)) {
            throw new \Exception('An active subscription is using this card.');
        }

        // Delete the card from the gateway if possible
        $gateway = Gateway::find($customer->gateway_id);
        $gateway = Gateway::getGateway($gateway);

        if (! method_exists($gateway, 'deleteCard')) {
            throw new \Exception('Gateway does not support delete card.');
        }

        try {
            $response = $gateway->deleteCard([
                'cardReference'     => $cardReference,
                'customerReference' => $customer->token,
            ])->send();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        if (! $response->isSuccessful()) {
            return false;
        }

        Event::fire(new CardDelete($billableId, $paymentToken->id));
        $paymentToken->delete();

        return true;
    }

    /**
     * fetchTransaction
     *
     * This fetches a transaction record from the provided gateway, or the default gateway.
     *
     * @param text $transactionReference
     * @return array $transaction
     *
     * @throws \Exception
     */
    public function fetchTransaction($transactionReference, $gateway = null)
    {
        if (empty($transactionReference)) {
            throw new \Exception('Empty transaction reference.');
        }

        if (empty($gateway)) {
            $app      = app();
            $gateway  = $app['billing.gateway'];
        } else {
            $gateway = Gateway::getGateway($gateway);
        }

        if (empty($gateway)) {
            throw new \Exception('Cannot get default gateway.');
        }

        if (! method_exists($gateway, 'fetchTransaction')) {
            throw new \Exception('Gateway does not support fetchTransaction.');
        }

        try {
            $response = $gateway->fetchTransaction([
                'transactionReference'     => $transactionReference,
            ])->send();

            if ($response->isSuccessful()) {
                return $response->getData();
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        throw new \Exception('No transaction fetched.');
    }

    /**
     * Create a Charge
     *
     * This charges a customer's credit card with the amount passed in
     * ($amount, should be retrieved from the subscription data) and
     * stores a Payment transaction if the charge is successful.  It
     * does not update any customer balances, etc.
     *
     * @param PaymentToken $card
     * @param float $amount
     * @param array $options
     * @param Gateway $gateway
     * @return Payment
     * @throws \Exception
     */
    public function purchase($billableId, $purchaseDetails, $gateway)
    {
        if (empty($billableId)) {
            throw new \Exception('Empty billable id.');
        }

        $cardReference  = (! empty($purchaseDetails['cardReference'])) ? $purchaseDetails['cardReference'] : '';
        $cardInfo       = (! empty($purchaseDetails['card_info']))     ? $purchaseDetails['card_info']     : [];
        $chargeData     = (! empty($purchaseDetails['purchaseData']))  ? $purchaseDetails['purchaseData']  : null;
        $payment_method = null;

        $gateway_id = null;
        // get the default gateway if none is provided
        if (empty($gateway)) {
            $app      = app();
            $gateway  = $app['billing.gateway'];
        } else {
            $gateway = Gateway::getGateway($gateway);
        }

        if (empty($gateway)) {
            throw new \Exception('Cannot get default gateway.');
        }

        $customer   =  Customer::where('billable_id', $billableId)
            ->('gateway_id', $gateway_id)
            ->first();

        if (empty($customer)) {
            try {
                $customer = $this->createCustomer($billableId, [], $gateway);
            } catch(\Exception $e) {
                throw new \Exception($e->getMessage(), $e->getCode());
            }
        }

        if (empty($customer)) {
            throw new \Exception('Cannot create card, failed to create customer object.');
        }

        $description  = isset($purchaseDetails['description'])  ? $purchaseDetails['description']  : '';
        $packageId    = isset($purchaseDetails['package_id'])   ? $purchaseDetails['package_id']   : '';
        $packageName  = isset($purchaseDetails['package_name']) ? $purchaseDetails['package_name'] : '';
        $amount       = isset($purchaseDetails['amount'])       ? $purchaseDetails['amount']       : $amount;
        $currency     = isset($purchaseDetails['currency'])     ? $purchaseDetails['currency']     : 'USD';

        // Set default purchase options
        $purchaseOptions = [
            'amount'            => number_format($amount, 2, '.', ''),
            'currency'          => $currency,
            'description'       => $description,
            'packageId'         => $packageId,
            'packageName'       => $packageName,
            'customerReference' => $customer->token
        ];

        $paymentToken = null;
        if (empty($cardReference)) {
            if (empty($cardInfo)) {
                throw new \Exception('Empty credit card information.');
            }
            $payment_method           = Payment::METHOD_CARD;
            $card                     = new CreditCard($cardInfo);
            $purchaseOptions['card'] = $card;
        } else {
            $paymentToken                           = PaymentToken::where('token', $cardReference)->first();
            if (empty($paymentToken)) {
                throw new \Exception('Cannot get payment token data.');
            }
            $payment_method                         = Payment::METHOD_CARD_TOKEN;
            $purchaseOptions['cardReference']      = $cardReference;
            $purchaseOptions['customerReference']  = $customer->token;
        }

        //Create Payment object here
        $payment                        = new Payment();
        $payment->billable_id           = $billableId,
        $payment->chargeable_id         = $packageId,
        $payment->paymenttoken_id       = (! empty($paymentToken->id))    ? $paymentToken->id   : null,
        $payment->gateway_id            = $customer->gateway_id;
        $payment->amount                = $amount;
        $payment->amount_not_refunded   = $amount;
        $payment->method                = $payment_method;
        $payment->currency              = $currency;
        $payment->transaction_date      = new \DateTime();
        $payment->transaction_details   = $description;
        $payment->status                = Payment::PENDING;
        $payment->extended_attributes   = ['request_data' => $purchaseOptions];
        $payment->save();

        try {
            $response = $gateway->purchase($purchaseOptions)->send();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        if ($response->isSuccessful()) {
            //save token every cardCharge
            if ($payment_method == Payment::METHOD_CARD) {
                try {
                    $paymentToken = $this->createPaymentToken($billableId, $customer->id, $cardInfo, $response->getCardReference());
                } catch (\Exception $e) {
                    throw new \Exception ($e->getMessage(), $e->getCode());
                }

                Event::fire(new CardCreate($billableId, $paymentToken->id));
            }
            //update payment status
            $txn_ref                         = $response->getTransactionReference();
            $response_data                   = $response->getData();
            $payment->gateway_id             = (! empty($response_data['payment']['gateway_id'])) ? $response_data['payment']['gateway_id'] : '';
            $payment->paymenttoken_id        = (isset($paymentToken->id)) ? $paymentToken->id : null;
            $payment->transaction_reference  = $txn_ref;
            $payment->gateway_processor      = $gateway_processor_name;
            $extended_attributes             = $payment->extended_attributes;
            $extended_attributes['response'] = $response_data;
            $payment->amount_usd             = (! empty($response_data['payment']['product']['price_USD'])) ? $response_data['payment']['product']['price_USD'] : null;
            $payment->transaction_seq        = (! empty($response_data['payment']['transaction_seq'])) ? $response_data['payment']['transaction_seq'] : null;
            $payment->extended_attributes    = $extended_attributes;
            $payment->status                 = Payment::SUCCESS;
            $payment->save();

            Event::fire(new ChargeSuccess($this->id, $customer->id, $payment->id, $purchaseDetails));

            return $payment;
        }

        $extended_attributes             = $payment->extended_attributes;
        $extended_attributes['response'] = $response->getData();
        $payment->extended_attributes    = $extended_attributes;
        $payment->status                 = Payment::ERROR;
        $payment->save();

        $response_data    = $response->getData();
        $error            = (isset($response_data['error'])) ? $response_data['error'] : [];
        $response_message = (!empty($error['message'])) ? $error['message'] : '';
        $response_code    = (!empty($error['code']))    ? $error['code']    : 0;

        if (empty($response_message)) {
            $response_message = (! empty($response->getMessage())) ? $response->getMessage() : '';
        }
        Event::fire(new ChargeFailed($this->id, $customer->id, $payment->id, $purchaseDetails));
        throw new \Exception($response_message, $response_code);
    }
}