<?php

namespace App\Console\Commands;

use DateTime;
use DateInterval;

use Illuminate\Console\Command;
use App\Models\ExpirationNotify;
use App\Models\Firm;
use App\Models\Notification;
use App\Models\Subscription;
use App\Models\User;

class ExpirationSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expiration-subscription';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send subscription expires notify';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $days = 3;
        $date = new DateTime();
        $date->add(new DateInterval("P" . $days . "D"));
        
        $expiration = Subscription
            ::withoutGlobalScopes()
            ->where("status", Subscription::STATUS_ACTIVE)
            ->where("end", "<=", $date->getTimestamp())
            ->get();
        
        
        if(!$expiration->isEmpty())
        {
            foreach($expiration as $row)
            {
                if(ExpirationNotify::where("subscription_id", $row->id)->count())
                    continue;
                
                $owner = Firm::getOwnerByUuid($row->uuid);
                if($owner)
                {
                    Notification::notify($owner->id, -1, $row->id, "subscription:expiration3");
                                            
                    $eNotify = new ExpirationNotify;
                    $eNotify->subscription_id = $row->id;
                    $eNotify->save();
                }
            }
        }
    }
}
