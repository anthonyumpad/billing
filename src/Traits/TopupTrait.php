<?php
/**
 * Autochargeable Trait
 *
 * @author      Del
 * @copyright   2015 Anthonyumpad.sg
 */

namespace Anthonyumpad\Billing\Traits;

use Anthonyumpad\Billing\Events\Charge\Success as ChargeSuccess;
use Anthonyumpad\Billing\Events\Charge\Failed as ChargeFailed;
use Anthonyumpad\Billing\Events\Refund\Success as RefundSuccess;
use Anthonyumpad\Billing\Events\Refund\Failed as RefundFailed;
use Anthonyumpad\Billing\Models\Gateway;
use Anthonyumpad\Billing\Models\Payment;
use Anthonyumpad\Billing\Models\PaymentToken;
use Illuminate\Support\Facades\Event;
use Omnipay\Common\CreditCard;
use Config;
use Log;

/**
 * TopupPayments Trait
 *
 * TopupPayments Trait is to be used where the billable model can be auto
 * charged based on a credit_balance field and a minimum_balance field.
 * Application of this trait depends on application of the Billable trait
 * to the same model as the TopupPayments trait.
 *
 * The functionality here includes:
 *
 * * charge() -- Make a purchase transaction against a gateway and record
 *   this against the current model (as purchase.chargeable_id).  This
 *   also tops up the customer's account balance by either the autocharge_amount
 *   or the autocharge_plan_points depending on the setting of the
 *   billing.autocharge_recharge_points config variable.
 * * refund() -- Make a refund against a previous purchase transaction.
 *   This does not affect the customer's account balance, so you should
 *   adjust this manually in the case of a refund that affects the balance.
 *
 * Required database columns:
 *
 * <code>
 *   $table->boolean('autocharge')->default(false);
 *   $table->decimal('credit_balance',10,2)->default(0.00);
 *   $table->decimal('minimum_balance',10,2)->default(0.00);
 *   $table->decimal('autocharge_amount',10,2)->default(0.00);
 *   $table->decimal('autocharge_plan_points',10,2)->default(0.00);
 *   $table->string('autocharge_currency',10)->default('USD');
 *   $table->date('last_autocharge_date')->default('0000-00-00');
 *   $table->integer('autocharge_retries')->default(0);
 * </code>
 *
 * <h3>Examples</h3>
 *
 * <h4>Class Setup</h4>
 *
 * <code>
 *   class User extends \Cartalyst\Sentry\Users\Eloquent\User
 *   {
 *       use Billable, Autochargeable;
 *       // ...
 *   }
 * </code>
 *
 * <h4>Charge a Customer's Stored Card with Auto Amount</h4>
 *
 * <code>
 *   // Find the user
 *   $user = User::where(...)->first();
 *
 *   // Find the user's card
 *   $card = $user->getDefaultCard();
 *   if (empty($card)) {
 *       throw new \Exception('Oops, the customer has no default card.');
 *   }
 *
 *   // Do the charge and top up the customer's balance with stored amounts
 *   $payment = $user->charge($card);
 *   // Payment transaction ID is now stored in $payment->transaction_reference
 * </code>
 *
 * <h4>Charge a Customer's Stored Card with Application Defined Amount</h4>
 *
 * <code>
 *   // Find the user
 *   $user = User::where(...)->first();
 *
 *   // Find the user's card
 *   $card = $user->getDefaultCard();
 *   if (empty($card)) {
 *       throw new \Exception('Oops, the customer has no default card.');
 *   }
 *
 *   // Set the payment options
 *   $options = [
 *       'amount'        => 200.00,
 *       'currency'      => 'USD',
 *       'plan_points'   => 800.00,
 *       'description'   => 'This is a funky transaction',
 *   ];
 *
 *   // Do the charge and top up the customer's balance with plan_points
 *   $payment = $user->charge($card);
 *   // Payment transaction ID is now stored in $payment->transaction_reference
 * </code>
 *
 * <h4>Charge a Customer's New Card</h4>
 *
 * <code>
 *   // Find the user
 *   $user = User::where(...)->first();
 *
 *   // Set the payment options including the card data.
 *   // Provide as much information as you can, although
 *   // not all gateways will use that information.  You should test the
 *   // gateways that you plan to use to determine which of the card holder
 *   // information items the gateway requires.
 *   $options = [
 *       'amount'        => 200.00,
 *       'currency'      => 'USD',
 *       'plan_points'   => 800.00,
 *       'description'   => 'This is a transaction where we store the card after purchase',
 *       'card_info'     => [
 *           'firstName'             => 'Example',
 *           'lastName'              => 'Customer',
 *           'number'                => '4242424242424242',
 *           'expiryMonth'           => '01',
 *           'expiryYear'            => '2020',
 *           'cvv'                   => '123',
 *           'email'                 => 'example.customer@Anthonyumpad.sg',
 *           'billingAddress1'       => '1 Scrubby Creek Road',
 *           'billingCountry'        => 'AU',
 *           'billingCity'           => 'Scrubby Creek',
 *           'billingPostcode'       => '4999',
 *           'billingState'          => 'QLD',
 *           'billingPhone'          => '12341234',
 *       ],
 *   ];
 *
 *   // Do the charge and top up the customer's balance with plan_points
 *   $payment = $user->charge($card);
 *   // Payment transaction ID is now stored in $payment->transaction_reference
 * </code>
 */
trait TopupTrait
{
    /**
     * Create a Charge
     *
     * This charges a customer's credit card with the amount stored in
     * autocharge_amount and adds either that amount or the amount in
     * autocharge_plan_points to their credit_balance if the charge
     * transaction is successful.
     *
     * The options array can have the following optional parameters:
     *
     * * amount -- overrides the charge amount in $this->autocharge_amount
     * * currency -- overrides the charge currency
     * * plan_points -- overrides the top up amount
     * * description -- provides a transaction description.  'Autocharge
     *   transaction' is used if this is not provided.
     * * package_id -- provides a package ID for the package.  Ignored for
     *   gateways that do not use it, currently only used by Use2Pay.  Uses
     *   the user ID if not provided.
     * * package_name -- provides a name for the package.  description is
     *   used if this is not provided.  Ignored for gateways that do not
     *   use it, currently only used by Use2Pay.
     * * card_info -- instead of providing a PaymentToken object, the card
     *   data can be passed in via this array and a PaymentToken object will
     *   be saved when the transaction is complete.  This is required for
     *   Use2Pay and other gateways that don't support a createCard method.
     *
     * If the PaymentToken object is null then a payment token will be
     * created at the time of payment.  The $gateway parameter must then
     * be used to indicate which payment gateway to make the payment and
     * register the card against.  Otherwise if there is a valid PaymentToken
     * object parameter passed in then the $gateway parameter is ignored and
     * the gateway against which the card is registered is used for the
     * payment transaction.
     *
     * @param PaymentToken|null $paymentToken
     * @param array $options
     * @param AbstractGateway $gateway -- ignored if $paymentToken is not null.
     * @return Payment
     * @throws \Exception
     */
    public function charge($paymentToken = null, array $options = [], $gateway = null)
    {
        $payment_method = null;
        // Billable model should be configurable
        if (! method_exists($this, 'asCustomer')) {
            throw new \Exception('This model does not include a Billable trait.');
        }

        // Get or create the customer object
        $customer = $this->asCustomer($gateway);
        $default  = false;

        if (is_null($customer)) {
            $default = true;
            try {
                $customer = $this->createCustomer();
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage(), $e->getCode());
            }
        }

        Log::info(__CLASS__ . ':' . __TRAIT__ . ':' . __FILE__ . ':' . __LINE__ . ':' . __FUNCTION__ . ':' .
            'got customer', [
            'customer' => $customer
        ]);

        // Find the gateway model object from the customer and reform this nto an omnipay gateway object.
        $gateway_model = $customer->gateway;
        $gateway       = Gateway::getGateway($gateway_model);

        //get user_id
        $user_id = null;
        if (method_exists($this, 'scopeUserId')) {
            $user_id = $this->scopeUserId();
        }

        if (empty($user_id)) {
            $user_id = $this->id;
        }

        if (empty($user_id)) {
            $user_id = $this->uid;
        }

        // Get the transaction details from options if they are provided,
        // otherwise use defaults from the current record or common sense
        if (empty($options['amount'])) {
            $amount = $this->autocharge_amount;
        } else {
            $amount = isset($options['amount'])      ? $options['amount']   : 0.00;
        }

        if (empty($options['currency'])) {
            $currency = $this->autocharge_currency;
        } else {
            $currency = isset($options['currency'])  ? $options['currency'] : 'USD';
        }

        if (empty($options['description'])) {
            $description = 'Autocharge transaction';
        } else {
            $description = isset($options['description'])  ? $options['description']  : '';
        }

        if (! empty($options['plan_points'])) {
            $top_up_amount = $options['plan_points'];
        } else {
            if (Config::get('billing.autocharge_recharge_points')) {
                $top_up_amount = $this->autocharge_plan_points;
            } else {
                $top_up_amount = $amount;
            }
        }

        // get and check the additional purchase options
        $clientIp     = isset($options['clientIp'])     ? $options['clientIp']     : '127.0.0.1';
        $packageId    = isset($options['package_id'])   ? $options['package_id']   : '';
        $packageName  = isset($options['package_name']) ? $options['package_name'] : '';
        $customerData = isset($options['customerData']) ? $options['customerData'] : [];
        $historyData  = isset($options['historyData'])  ? $options['historyData']  : [];
        $notifyUrl    = isset($options['notifyUrl'])    ? $options['notifyUrl']    : '';

        // Set default purchase options
        $purchase_options = [
            'amount'            => number_format($amount, 2, '.', ''),
            'currency'          => $currency,
            'description'       => $description,
            'clientIp'          => $clientIp,
            'accountId'         => $user_id,
            'packageId'         => $packageId,
            'packageName'       => $packageName,
            'customerData'      => $customerData,
            'historyData'       => $historyData,
            'notifyUrl'         => $notifyUrl,      //this is required for all payment gateways
            'customerReference' => $customer->token // we don't always save the paymenttoken so we might get an accountId + email non-unique error so we should pass this along
        ];

        // If there is a payment token passed in then use that otherwise
        // use the card data in the $options array and create a payment
        // token when done
        if (! empty($options['paymentSchema'])) {
            if ($options['paymentSchema'] == Gateway::GW_PAYMENT_CHANNEL_CODE_MANUAL) {
                $payment_method = Payment::METHOD_MANUAL;
            } else {
                $payment_method = Payment::METHOD_REDIRECT;
            }
            $purchase_options['paymentSchema'] = isset($options['paymentSchema']) ? $options['paymentSchema'] : '';
            $purchase_options['email']         = isset($options['email'])         ? $options['email']         : '';
            $purchase_options['returnUrl']     = isset($options['returnUrl'])     ? $options['returnUrl']     : '';
            $purchase_options['cancelUrl']     = isset($options['cancelUrl'])     ? $options['cancelUrl']     : '';
        } elseif (empty($paymentToken)) {
            $payment_method = Payment::METHOD_CARD;
            $card                     = new CreditCard($options['card_info']);
            $purchase_options['card'] = $card;
        } elseif (isset($paymentToken->token)) {
            $payment_method = Payment::METHOD_CARD_TOKEN;
            $purchase_options['cardReference']      = $paymentToken->token;
            $purchase_options['customerReference']  = $customer->token;
        }

        // Create Payment object here
        $paymenttoken_id                = (! empty($paymentToken->id))   ? $paymentToken->id     : null;
        $url_token                      = (isset($options['url_token'])) ? $options['url_token'] : ''; //url tokens are used as reference for redirect payments
        $payment                        = new Payment();
        $payment->billable_id           = $this->id;
        $payment->chargeable_id         = (! empty($options['chargeable_id'])) ? $options['chargeable_id'] : null;
        $payment->paymenttoken_id       = $paymenttoken_id;
        $payment->url_token             = $url_token;
        $payment->gateway_id            = $customer->gateway_id;
        $payment->amount                = $amount;
        $payment->amount_not_refunded   = $amount;
        $payment->method                = $payment_method;
        $payment->currency              = $currency;
        $payment->transaction_date      = new \DateTime();
        $payment->transaction_details   = $description;
        $payment->status                = Payment::PENDING;
        $payment->extended_attributes   = ['request_data' => $purchase_options];
        $payment->save();

        //add the notifyUrl here
        if (! isset($options['notifyUrl'])) {
            $purchase_options['notifyUrl']= route('billing.callback.payment.post', $payment->id);
        } else {
            $purchase_options['notifyUrl']  = $options['notifyUrl'].'/'.$payment->id;
        }


        /*Log::info(__CLASS__.':'.__TRAIT__.':'.__FILE__.':'.__LINE__.':'.__FUNCTION__.':'.
            'Purchase options', $purchase_options);*/

        // Create the message, send it, and get the response.
        try {
            $response = $gateway->purchase($purchase_options)->send();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        /*Log::info(__CLASS__.':'.__TRAIT__.':'.__FILE__.':'.__LINE__.':'.__FUNCTION__.':'.
            'got response', ['response' => $response]);*/

        if ($response->isSuccessful()) {
            //save token every cardCharge
            if ($payment_method == Payment::METHOD_CARD) {
                $paymentToken = $customer->storeToken($response, $options['card_info']);
            }
            //update payment status
            $txn_ref = $response->getTransactionReference();
            $response_data                   = $response->getData();
            $payment_gateway_id              = (! empty($response_data['payment']['gateway_id'])) ? $response_data['payment']['gateway_id'] : '';
            $gateway_processor_name          = $this->getGatewayProcessorName($payment_gateway_id, $gateway);
            $payment->paymenttoken_id        = (isset($paymentToken->id)) ? $paymentToken->id : null;
            $payment->gateway_processor      = $gateway_processor_name;
            $payment->transaction_reference  = $txn_ref;
            $extended_attributes             = $payment->extended_attributes;
            $extended_attributes['response'] = $response_data;
            $payment->amount_usd             = (! empty($response_data['payment']['product']['price_USD'])) ? $response_data['payment']['product']['price_USD'] : null;
            $payment->transaction_seq        = (! empty($response_data['payment']['transaction_seq'])) ? $response_data['payment']['transaction_seq'] : null;
            $payment->extended_attributes    = $extended_attributes;
            $payment->status                 = Payment::SUCCESS;
            $payment->save();

            /*Log::info(__CLASS__.':'.__TRAIT__.':'.__FILE__.':'.__LINE__.':'.__FUNCTION__.':'.
                'stored payment', ['payment' => $payment]);
*/
            // Fire the charge success event passing in the amount,
            // the payment record and the options passed in to this
            // method.  The event handler for this event can use any
            // of the other options in the $options array (e.g. plan
            // ID, plan description, customer details, etc).
            if ($response->isRedirect()) {
                //set payment status to pending_payment
                $payment->status = 'PENDING_PAYMENT';
                $payment->save();
                return $payment;
            }

            // update model data
            // always check if the site implemented the top-up payments rows below
            if (isset($this->credit_balance)) {
                $this->credit_balance += $top_up_amount;
            }

            if (isset($this->last_autocharge_date)) {
                $this->last_autocharge_date = new \DateTime;
            }

            if (isset($this->autocharge_retries)) {
                $this->autocharge_retries = 0;
            }
            $this->save();

            // Fire the charge success event passing in the amount,
            // the payment record and the options passed in to this
            // method.  The event handler for this event can use any
            // of the other options in the $options array (e.g. plan
            // ID, plan description, customer details, etc).
            $chargeable = $this->toArray();
            Event::fire(new ChargeSuccess($this->id, $customer->id, $payment->id, $options));

            return $payment;
        }

        // Record failure
        /*Log::info(__CLASS__.':'.__TRAIT__.':'.__FILE__.':'.__LINE__.':'.__FUNCTION__.':'.
            'charge attempt failed', ['response_message' => $response->getMessage()]);*/

        $extended_attributes             = $payment->extended_attributes;
        $extended_attributes['response'] = $response->getData();
        $payment->extended_attributes    = $extended_attributes;
        $payment->status                 = Payment::ERROR;
        $payment->save();

        //update billable model
        if (isset($this->autocharge_retries)) {
            $this->autocharge_retries += 1;
            $this->save();
        }

        $response_data   = $response->getData();
        $error           = (isset($response_data['error'])) ? $response_data['error'] : [];
        /*Log::info(__CLASS__.':'.__TRAIT__.':'.__FILE__.':'.__LINE__.':'.__FUNCTION__.':'.
            'response data', ['response' => $response_data]);*/
        $response_message = (!empty($error['message'])) ? $error['message'] : '';
        $response_code    = (!empty($error['code']))    ? $error['code']    : '';

        if (empty($response_message)) {
            $response_message = (! empty($response->getMessage())) ? $response->getMessage() : '';
        }

        Event::fire(new ChargeFailed($this->id, $customer->id, $payment->id, $options));
        throw new \Exception($response_message, $response_code);
    }

    /**
     * Refund a Transaction
     *
     * This does not affect the customer's account balance, so you should
     * adjust this manually in the case of a refund that affects the balance.
     *
     * @param Payment $transaction
     * @param float $amount
     * @return Refund
     * @throws \Exception
     */
    public function refund($transaction, $amount = null)
    {
        // Get the gateway for the payment transaction
        $gateway_model = $transaction->gateway;
        $gateway = Gateway::getGateway($gateway_model);

        // check the amount to be refunded based on amount_not_refunded
        if (empty($amount) || $amount > $transaction->amount_not_refunded) {
            $refund_amount = $transaction->amount_not_refunded;
        } else {
            $refund_amount = $amount;
        }

        // FIXME this does not yet implement Use2Pay.
        // FIXME Use2Pay doesn't yet implement refunds anyway.
        $response = $gateway->refund(array(
            'amount'                   => number_format($refund_amount, 2, '.', ''),
            'transactionReference'     => $transaction->transaction_reference,
        ))->send();

        if ($response->isSuccessful()) {

            // Adjust the purchase transaction to record the non refunded amount
            $remaining_amount = $transaction->amount_not_refunded - $refund_amount;
            $transaction->status = $remaining_amount == 0 ? 'REFUNDED': 'PARTIAL';
            $transaction->amount_not_refunded = $remaining_amount;
            $transaction->save();

            // Save a refund transaction
            $refund = Refund::create([
                'billable_id'           => $this->id,
                'chargeable_id'         => $transaction->chargeable_id,
                'payment_id'            => $transaction->id,
                'paymenttoken_id'       => $transaction->paymenttoken_id,
                'gateway_id'            => $transaction->gateway_id,
                'amount'                => $refund_amount,
                'service'               => $transaction->service,
                'transaction_date'      => new \DateTime,
                'transaction_reference' => $response->getTransactionReference(),
                'transaction_details'   => 'Refund of transaction ID ' . $transaction->transaction_reference,
                'status'                => 'REFUND',
            ]);

            Event::fire(new RefundSuccess($transaction, $amount, $this));

            return $refund;
        }

        Event::fire(new RefundFailed($transaction, $amount, $this));

        $data = $response->getData();
        throw new \Exception($response->getMessage());
    }

    /**
     * Attempt all autocharge payments.
     *
     * This cycles through all customers calling charge() on the customer
     * account if there is an autocharge due. An autocharge is due if:
     *
     * * autocharge on the customer's account is set to 1.
     * * The customer's credit_balance is lower than their minimum_balance
     * * Either there have been 0 previous charge attempts, or
     * * if there has been a previous charge attempt, the number of charge
     *   attempts is fewer than the maximum retry_attempts set in the config
     *   and the number of days since the last charge attempt is more than
     *   the retry_interval set in the config.
     * * The customer has a valid default card set up for charges.
     *
     * @return void
     */
    public static function autochargeAttempt()
    {
        // Static retry intervals
        $retry_intervals = Config::get('billing.retry_interval');
        $retry_attempts = Config::get('billing.retry_attempts');
        $now = new \DateTime;
        /*Log::info(__CLASS__.':'.__TRAIT__.':'.__FILE__.':'.__LINE__.':'.__FUNCTION__.':'.
            'retry attempts = ' . $retry_attempts);*/

        // Database search for users with payment due.
        $payments_due_query = static::where('autocharge', '=', 1)
            ->whereRaw('`credit_balance` < `minimum_balance`')
            ->where('autocharge_retries', '<', $retry_attempts);

        // Loop over payments due
        foreach ($payments_due_query->get() as $user) {

            // Skip if the last attempt was too recent.
            if ($user->autocharge_retries > 0) {
                $interval_req = $retry_intervals[$user->autocharge_retries - 1];
                $interval = $now->diff($user->last_autocharge_date, true)->format('%a');
                if ($interval < $interval_req) {
                    continue;
                }
            }

            // Find a card for the user
            /*Log::info(__CLASS__.':'.__TRAIT__.':'.__FILE__.':'.__LINE__.':'.__FUNCTION__.':'.
                'attempt to charge user', ['user' => $user]);*/
            $card = $user->getDefaultCard();
            if (empty($card)) {
                /*Log::info(__CLASS__.':'.__TRAIT__.':'.__FILE__.':'.__LINE__.':'.__FUNCTION__.':'.
                    'no card found');*/
                break;
            }

            // Attempt to charge that card
           /*Log::info(__CLASS__.':'.__TRAIT__.':'.__FILE__.':'.__LINE__.':'.__FUNCTION__.':'.
                'attempt to charge card', ['card' => $card]);*/
            try {
                $user->charge($card);
            } catch (\Exception $e) {
                // no-op
                // The charge failed event should have fired and so the
                // user can be notified that way.
            }
        }
    }
}
