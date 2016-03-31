## Introduction

The functionality of the billing plugin is to provide billing functions against a Billable or the customer object
and create your own subscriptions and manage your own auto charge for the subscriptions.


## How To Integrate This To Your Project

Add this to your composer.json file:

```
    "require" : {
        "omnipay/stripe": "~2.0",
        "anthonyumpad/billing": "dev-master"
    },
    "repositories"  : [
        {
            "type": "git",
            "url": "https://github.com/anthonyumpad/billing.git"
        }
    ],
```

Once that is done, run the composer update command:

```
    composer update
```

### Register Service Provider

After composer update completes, add these lines to your config/app.php file in the 'providers' array:

```
    'anthonyumpad\billing\BillingServiceProvider'
```

### Publish billing Migrations and Config

Run the command below to publish the migrations and config for the billing service.

```
    php artisan vendor:publish
    php artisan migrate
```


### Gateway seeder configuration for Stripe for integration

Stripe requires a apiKey for authenticating transactions.
Update the billing configuration file for Stripe apiKey at billing.php and run the database seeder

```
    php artisan db:seed
```

Add the following entries to your app/Console/Kernel file in the array "$commands":

```
    'anthonyumpad\billing\Commands\SubscriptionAutoChargeCommand'
```

In app/Handlers/Events you may want to add some handlers for the specific events fired by the billing service.

Below are the events that the billing service fires:

```
    'anthonyumpad\billing\Events\Charge\Success'
    'anthonyumpad\billing\Events\Charge\Failed'
    'anthonyumpad\billing\Events\Refund\Success'
    'anthonyumpad\billing\Events\Refund\Failed'
    'anthonyumpad\billing\Events\Autocharge\Retry'
    'anthonyumpad\billing\Events\Autocharge\CardExpire'
    'anthonyumpad\billing\Events\Autocharge\Defaulted'
    'anthonyumpad\billing\Events\Autocharge\Success'
    'anthonyumpad\billing\Events\Autocharge\Failed'
    'anthonyumpad\billing\Events\Customer\Create'
    'anthonyumpad\billing\Events\Customer\Delete'
    'anthonyumpad\billing\Events\Card\Create'
    'anthonyumpad\billing\Events\Card\Delete'
```

### Example Usage:

Billable is referred as the primary actor or the customer to your site.

## Using the Billing Facade
```
    use Anthonyumpad\Billing\Facades\Billing;
```
## Customer Functions

```
    // creating a customer object
    try {
        $customer = Billing::createCustomer($billableId, [
           'email'       => 'test@billing.com',
           'description' => 'Test customer'
        ]);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    
    // fetching a customer object
    try {
        $customer = Billing::getCustomer($billableId);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    
    // deleting a customer object
    // Note: this will also delete the card objects attached to the customer
    try {
        $result = Billing::deleteCustomer($billableId);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
```

## Credit Card Functions


    // creating a card object
    try {
        $card = Billing::createCard($billalbleId, [
            'firstName'             => 'Anthony',
            'lastName'              => 'Umpad',
            'number'                => '4242424242424242',
            'expiryMonth'           => '12',
            'expiryYear'            => '2025',
            'cvv'                   => '123',
            'email'                 => 'anthony@test.com',
            'billingAddress1'       => 'Singapore',
            'billingCountry'        => 'Singapore',
            'billingCity'           => 'Singapore',
            'billingPostcode'       => '124',
            'billingState'          => 'Singapore',
            'billingPhone'          => '1234567',
        ]);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    
    // set card as the default card
    // the first created card is also set as default
    try {
        $result = Billing::setDefaultCard($billableId, 'card_12345xxxx');
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    
    // get default card
    try {
        $card = Billing::getDefaultCard($billableId);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    
    // get all stored cards attached to the Billalble object
    try {
        $cards = Billing::getCards($billableId);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    
    // delete a card
    try {
        $result = Billing::deleteCard($billableId, 'card_12345xxxx');
    } catch (Exception $e) {
        echo $e->getMessage();
    }
 
    
## Purchase, Refund and Subscription Functions
    
   
    // credit card purchase
    try {
       Billing::purchase($billableId, [
           'card' => [
               'firstName'             => 'Anthony',
               'lastName'              => 'Umpad',
               'number'                => '4242424242424242',
               'expiryMonth'           => '12',
               'expiryYear'            => '2025',
               'cvv'                   => '123',
               'email'                 => 'anthony@test.com',
               'billingAddress1'       => 'Singapore',
               'billingCountry'        => 'Singapore',
               'billingCity'           => 'Singapore',
               'billingPostcode'       => '6300',
               'billingState'          => 'Singapore',
               'billingPhone'          => '1234567',
           ],
           'description'  => 'Test payment',
           'package_id'   => 1,
           'package_name' => 'TestName',
           'amount'       => '10.00',
           'currency'     => 'USD'
       ]);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    
    // saved card token purchase
    try {
        $payment = Billing::purchase($billableId,[
            'cardReference' => 'card_1234xxx',
            'description'   => 'Test card payment',
            'package_id'    => 2,
            'package_name'  => 'TestName',
            'amount'        => '11.00',
            'currency'      => 'USD'
        ]);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    
    // refund a purchase
    try {
     $refund = Billing::refund('ch_1234xxx');
    } catch (Exception $e) {
        echo $e->getMessage();
    }
 
    // create a subscription for a Billable
    // Interval types:
    // - DAY
    // - WEEK
    // - MONTH
    // - YEAR
    try {
        $subscription = Billing::subscribe($billableId,[
            'interval'      => 1,
            'intervalType'  => 'MONTH',
            'nextAttempt'   => '2016-04-30 00:00:00'
            'data'     => [
                'amount'        =>  10.00,
                'currency'      => 'USD',
                'package_name'  => 'Test subscription package',
                'package_id'    => 2,
                'description'   => 'Subscription package',
            ]
        ]);
     } catch (Exception $e) {
        echo $e->getMessage();
     }
    
    // cancel a subscription
    try {
        $result = Billing::unsubscribe($billableId);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    
## Subscription Auto charge command
    
The Auto charge command can be run through artisan once you've added the command in your Kernel.
This command will perform all auto charge for the subscriptions that are due.
You will need to create a CRON job for this command to be executed with your ideal time interval.
       
    php artisan billing:subscription-autocharge