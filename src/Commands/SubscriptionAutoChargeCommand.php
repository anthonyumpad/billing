<?php

namespace Anthonyumpad\Billing\Commands;

use Illuminate\Console\Command;
use Anthonyumpad\Billing\Facades\Billing;

class SubscriptionAutoChargeCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'billing:subscription-autocharge';

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
        Billing::autocharge();
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
