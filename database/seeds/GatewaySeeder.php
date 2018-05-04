<?php
/**
 * Class GatewaySeeder
 *
 * @author anthony
 */

use Illuminate\Database\Seeder;
use Anthonyumpad\Billing\Models\Gateway;

/**
 * Class GatewaySeeder
 */
class GatewaySeeder extends Seeder
{
    public function run()
    {
        $env       =  env('APP_ENV');
        $api_key   =  Config::get('billing.site_key');
        $api_url   =  Config::get('billing.test_gateway_url');
        $test_mode =  true;
        switch($env) {
            case 'test':
                $api_url	= Config::get('billing.test_gateway_url');
                break;
            case 'production':
                $api_url 	= Config::get('billing.prod_gateway_url');
                $test_mode 	= false;
                break;
            default:
                break;
        }

        $extended_attrs = [
            'apiKey'	=>	$api_key,
            'apiUrl'	=>  $api_url,
            'testMode'	=>  $test_mode,
        ];

        Gateway::create([
            'name'                   => 'Stripe',
            'is_default'             => true,
            'gateway_type'           => 'Omnipay',
            'description'            => 'Stripe Gateway',
            'extended_attributes'    => $extended_attrs
        ]);
    }
}
