<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;

class ExpireSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire subscriptions';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Subscription::deactivateExpiredPackages();
    }
}
