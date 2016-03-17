<?php
/**
 * BillingInterface
 *
 * @author     Anthony Umpad
 * @version    1.0
 */

namespace Anthonyumpad\Billing;

/**
 * Class BillingInterface
 *
 * This defines all functions that are implemented for the Billing plugin
 *
 * @package Anthonyumpad\Billing
 */
class BillingInterface
{

    public function createCustomer($billableId, $customerData, $gateway);

    public function getCustomer($billableId, $gateway);

    public function deleteCustomer($billableId, $gateway);

    public function createCard($billableId, $cardInfo, $gateway);

    public function getDefaultCard($billableId);

    public function setDefaultCard($billableId, $cardReference);

    public function getCards($billableId);

    public function deleteCard($billableId, $cardReference);

    public function fetchTransaction($transactionReference, $gateway);

    public function purchase($billableId, $purchaseDetails,$gateway);

    public function subscribe($billableId, $subscriptionData);

    public function unsubscribe($billableId);

    public function refund($transactionReference, $amount, $gateway);

}