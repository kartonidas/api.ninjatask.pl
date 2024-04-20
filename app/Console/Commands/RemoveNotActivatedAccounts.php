<?php

namespace App\Console\Commands;

use DateTime;
use DateInterval;

use Illuminate\Console\Command;
use App\Models\User;

class RemoveNotActivatedAccounts extends Command
{
    protected $signature = 'app:clear-not-activated-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove not activated users';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
	$time = new DateTime();
	$time->sub(new DateInterval("P7D"));

	$users = User::where("activated", 0)->where("created_at", "<", $time->format("Y-m-d H:i:s"))->get();

	foreach($users as $user)
	{
	    $user->forceDeleted();
	}
    }
}
