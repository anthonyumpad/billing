<?php

namespace Anthonyumpad\Billing\Commands;

use Illuminate\Console\Command;
use Anthonympad\Billing\Traits\TopupPayments;

class TopupCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'billing:topup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handles all top-up autocharge payments';

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
     *
     */
    public function fire()
    {
        TopupPayments::autochargeAttempt();
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
