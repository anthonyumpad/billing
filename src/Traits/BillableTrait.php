<?php
/**
 * Billable Trait
 *
 * @author     Anthony Umpad
 * @version    1.0
 */

namespace Anthonyumpad\Billing\Traits;
use Anthonyumpad\Billing\Models\PaymentToken;

/**
 * Class BillableTrait
 *
 * This contains all the Billable related function.
 *
 * @package Anthonyumpad\Billing\Traits
 */
trait BillableTrait
{

    /**
     * @var Anthonyumpad/Billing/Repositories/BillingRepository
     */
    protected $billingRepository;

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
        try {
            $customer = $this->billingRepository->createCustomer($billableId, $customerData, $gateway);
        } catch (\Exception $e) {
            throw new \Exception ($e->getMessage(), $e->getCode());
        }

        return $customer;
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
        try {
            $customer = $this->billingRepository->getCustomer($billableId, $gateway);
        } catch (\Exception $e) {
            throw new \Exception ($e->getMessage(), $e->getCode());
        }
        return $customer;
    }

    /**
     * Delete the Customer
     *
     * If a null gateway is provided then this function will
     * delete the customer from all gateways.
     *
     * @param int $billableId
     * @param AbstractGateway|Fluent|string|null $gateway
     * @return bool
     *
     * @throws \Exception
     */
    public function deleteCustomer($billableId, $gateway = null)
    {
        try {
            $result = $this->billingRepository->deleteCustomer($billableId, $gateway);
        } catch (\Exception $e) {
            throw new \Exception ($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     * Create the CreditCard object
     *
     * Returns a PaymentToken object if the card is successfully registered
     * against the gateway.  If the gateway does not support card registration
     * then the return value will be null.
     *
     * If gateway is null, this will register to the default gateway
     *
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
        try {
            $paymenttoken = $this->billingRepository->createCard($billableId, $cardInfo, $gateway);
        } catch (\Exception $e) {
            throw new \Exception ($e->getMessage(), $e->getCode());
        }

        return $paymenttoken;
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
        try {
            $paymenttoken = $this->billingRepository->getDefaultCard($billableId);
        } catch (\Exception $e) {
            throw new \Exception ($e->getMessage(), $e->getCode());
        }
        return $paymenttoken;
    }

    /**
     * setDefaultCard
     *
     * This sets the default card for the given billableId
     *
     * @param int $billableId
     * @param text $cardReference
     * @return bool
     *
     * @throws \Exception
     */
    public function setDefaultCard($billableId, $cardReference)
    {
        try {
            $result = $this->billingRepository->setDefaultCard($billableId, $cardReference);
        } catch (\Exception $e) {
            throw new \Exception ($e->getMessage(), $e->getCode());
        }
        return $result;
    }

    /**
     * Get all Cards
     *
     * @param int $billableId
     * @return PaymentToken Collection|null
     *
     * @throws \Exception
     */
    public function getCards($billableId)
    {
        try {
            $result = $this->billingRepository->getCards($billableId);
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
        try {
            $result = $this->billingRepository->deleteCard($billableId, $cardReference);
        } catch (\Exception $e) {
            throw new \Exception ($e->getMessage(), $e->getCode());
        }
        return $result;
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
        try {
            $result = $this->billingRepository->fetchTransaction($transactionReference, $gateway);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     * purchase
     *
     * This sends the puchase request to the gateway.
     *
     * @param $billabeId
     * @param $puchaseDetails
     * @param null $gateway
     * @return Payment $payment
     *
     * @throws \Exception
     */
    public function purchase($billableId, $purchaseDetails, $gateway = null)
    {
        try {
            $payment = $this->billingRepository->purchase($billableId, $purchaseDetails, $gateway);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $payment;
    }

    /**
     * purchase
     *
     * This sends the refund request to the gateway.
     *
     * @param $transactionReference
     * @param $amount
     * @return Payment $payment
     *
     * @throws \Exception
     */
    public function refund($transactionReference, $amount, $gateway)
    {
        try {
            $refund = $this->billingRepository->refund($transactionReference, $amount, $gateway);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $refund;
    }
}
