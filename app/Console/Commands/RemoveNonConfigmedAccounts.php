<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DateTime;
use DateInterval;
use App\Models\User;

class RemoveNonConfigmedAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remove-non-confirmet-accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove not confirmed accounts';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $date = (new DateTime())->sub(new DateInterval("P7D"));
        User::whereDate("created_at", "<", $date->format("Y-m-d"))->withTrashed()->where("activated", 0)->forceDelete();
        echo "Done";
    }
}
