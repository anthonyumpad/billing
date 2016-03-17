<?php
/**
 * Customer model
 *
 * @author     Anthony Umpad
 * @version    1.0
 */

namespace Anthonyumpad\Billing\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Billing Facade
 *
 * Facade class for the Billing plugin.
 * This provides a static access to all Billing related functions that includes
 * the billable (user) actions, Subscription and Topup payments.
 *
 * ### Example
 *
 * <code>
 *  use Anthonyumpad\Billing\Facades\Billing;
 *
 * Billing::createCustomer
 * </code>
 * @package Anthonyumpad\Billing\Facades
 */
class Billing extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'Billing';
    }
}