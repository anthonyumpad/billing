<?php
/**
 * Gateway model
 *
 * @author     Anthony Umpad
 * @version    1.0
 */

namespace Anthonyumpad\Billing\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Omnipay\Common\GatewayInterface;
use Omnipay\Common\AbstractGateway;

/**
 * Gateway model
 *
 * Stores the necessary parameters to boostrap a payment gateway in the Omnipay system.
 */
class Gateway extends \Illuminate\Database\Eloquent\Model
{
    use SoftDelete;

    protected $fillable = [
        'name',
        'is_default',
        'gateway_type',
        'description',
        'extended_attributes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'extended_attributes' => 'array'
    ];

    /**
     * 1:Many relationship with Customer
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function customers()
    {
        return $this->hasMany('Anthonyumpad\Billing\Models\Customer');
    }

    /**
     * Get the default gateway
     *
     * @return Gateway
     */
    public static function getDefault()
    {
        // Start by searching for the default gateway in the database
        $gateway = static::where('is_default', '=', true)->first();

        // If we did not find that then look for the first gateway
        // in the config gateway list.
        $app = app();
        if (empty($gateway)) {
            $gateway_list = $app['billing.gateways'];
            $gateway      = static::where('name', '=', $gateway_list[0])->first();
        }

        return $gateway;
    }

    /**
     * Reform an object into an Omnipay Gateway
     *
     * Reforms a gateway string (gateway short name), an AbstractGateway
     * object (from Omnipay), a Fluent object, or a Gateway model object
     * into an Omnipay Gateway object from the app billing.gateways
     * registry as set up in BillingServiceProvider.
     *
     * @param AbstractGateway|Fluent|Gateway|string $gateway
     * @return GatewayInterface
     *
     * @see BillingServiceProvider
     * @throws \Exception
     */
    public static function getGateway($gateway = null)
    {
        $app = app();
        if (empty($gateway)) {
            return $app['billing.gateway'];
        }

        if ($gateway instanceof AbstractGateway) {
            $short_name = $gateway->getShortName();
        } elseif ($gateway instanceof Gateway) {
            $short_name = $gateway->name;
        } elseif ($gateway instanceof Fluent) {
            $short_name = $gateway->shortname;
        } else {
            $short_name = $gateway;
        }

        if (array_key_exists($short_name, $app['billing.gateways'])) {
            return $app['billing.gateways'][$short_name];
        }

        throw new \Exception('The gateway `' . $short_name . '` is not registered');
    }
}
