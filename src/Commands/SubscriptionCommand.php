<?php

namespace Anthonyumpad\Billing\Commands;

use Anthonyumpad\Billing\Models\Subscription;
use Illuminate\Console\Command;
use Anthonyumpad\Billing\Traits\SubscriptionPayments;

class SubscriptionCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'billing:subscription';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handles all subscription payment autocharge';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Fire
     *
     * calls the command function
     */
    public function fire()
    {
        SubscriptionPayments::autochargeAttempt();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}
