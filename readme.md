## Introduction

The functionality of the billing service is to provide several drop-in traits for billing against both
customer (user) records and subscription records.


## How To Integrate This To Your Project

Add this to your composer.json file:

## Installation

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
    'anthonyumpad\billing\Commands\SubscriptionCommand'
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