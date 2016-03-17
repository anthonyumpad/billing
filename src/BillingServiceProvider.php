<?php
/**
 * BillingServiceProvider
 *
 * @author     Anthony Umpad
 * @version    1.0
 */

namespace Anthonyumpad\Billing;

use Anthonyumpad\Billing\Models\Gateway as BillingGatewayModel;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Fluent;
use Omnipay\Omnipay;
use Log;

/**
 * BillingServiceProvider
 *
 * Integrates the configuration for the billing plugin.
 */
class BillingServiceProvider extends ServiceProvider
{

    /**
     * Lists all the events that the billing plugin fires.
     *
     */
    protected $listen = [
        'Anthonyumpad\Billing\Events\Charge\Success'       => [],
        'Anthonyumpad\Billing\Events\Charge\Failed'        => [],
        'Anthonyumpad\Billing\Events\Refund\Success'       => [],
        'Anthonyumpad\Billing\Events\Refund\Failed'        => [],
        'Anthonyumpad\Billing\Events\Autocharge\Retry'      => [],
        'Anthonyumpad\Billing\Events\Autocharge\CardExpire' => [],
        'Anthonyumpad\Billing\Events\Autocharge\Defaulted'  => [],
        'Anthonyumpad\Billing\Events\Autocharge\Success'    => [],
        'Anthonyumpad\Billing\Events\Autocharge\Failed'     => [],
        'Anthonyumpad\Billing\Events\Customer\Create'      => [],
        'Anthonyumpad\Billing\Events\Customer\Delete'      => [],
        'Anthonyumpad\Billing\Events\Card\Create'          => [],
        'Anthonyumpad\Billing\Events\Card\Delete'          => [],
    ];

    /**
     * Boot the service provider.
     *
     * This method is called after all other service providers have
     * been registered, meaning you have access to all other services
     * that have been registered by the framework.
     *
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        parent::boot($events);

        // Publish the database migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => $this->app->databasePath() . '/migrations'
        ], 'migrations');

        $this->publishes([
            __DIR__ . '/../database/seeds' => $this->app->databasePath() . '/seeds'
        ], 'seeds');

        $this->publishes([
            __DIR__ . '/../src/config/billing.php' => config_path('billing.php'),
        ], 'config');

    }

    /**
     * Register the service provider.
     *
     * Within the register method, you should only bind things into the
     * service container. You should never attempt to register any event
     * listeners, routes, or any other piece of functionality within the
     * register method.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/billing.php', 'billing'
        );

        // Get the gateways registered
        $this->app['billing.gateways'] = $this->app->share(function ($app) {
            $gateways = [];
            $gates = $app['config']['billing.gateways'];


            foreach ($gates as $gateway_name) {
                // get the config from database
                $model = BillingGatewayModel::where('name', '=', $gateway_name)->first();
                if (empty($model)) {
                    continue;
                }

                switch ($model->gateway_type) {
                    case 'Omnipay':
                        $gateway = Omnipay::create($gateway_name);
                        $gateway->initialize($model->extended_attributes);
                        $gateway->model = $model;
                        break;
                }

                $gateways[$gateway_name] = $gateway;
            }
            return $gateways;
        });

        // Get the default gateway
        $this->app['billing.gateway'] = $this->app->share(function ($app) {
            $gateways = $app['billing.gateways'];
            foreach ($gateways as $gateway) {
                // Return the first gateway where the model has is_default set
                if ($gateway->model->is_default) {
                    return $gateway;
                }
            }
            // If no such gateway was found then return the first gateway.
            return reset($gateways);
        });
    }
}
