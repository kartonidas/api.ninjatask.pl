<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\ModuleNotAllowed;
use App\Exceptions\OutOffLimit;
use App\Exceptions\SubscriptionRequired;
use App\Models\ExpirationNotify;
use App\Models\Firm;
use App\Models\Limit;
use App\Models\Order;
use App\Models\Project;
use App\Models\Task;

class Subscription extends Model
{
    const STATUS_ACTIVE = "active";
    const STATUS_NEW = "new";
    const STATUS_EXPIRED = "expired";
    
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public static function addPackageFromOrder(Order $order)
    {
        $row = self::withoutGLobalScopes()->where("uuid", $order->uuid)->where("status", self::STATUS_ACTIVE)->first();
        if(!$row)
        {
            $row = new self;
            $row->uuid = $order->uuid;
            $row->status = self::STATUS_ACTIVE;
            $row->start = time();
            $row->end = strtotime(self::getPeriod($order->months), strtotime(date("Y-m-d") . " 23:59:59"));
            $row->saveQuietly();
            
            $notificationType = "subscription:activated";
        }
        else
        {
            $row->end = strtotime(self::getPeriod($order->months), $row->end);
            $row->saveQuietly();
            
            $notificationType = "subscription:renewed";
        }

        $order->subscription_id = $row->id;
        $order->saveQuietly();
        
        ExpirationNotify::where("subscription_id", $row->id)->delete();
        
        $owner = Firm::getOwnerByUuid($row->uuid);
        if($owner && !empty($notificationType))
            Notification::notify($owner->id, -1, $row->id, $notificationType);
        
        return $row;
    }
    
    private static function getPeriod($period) {
        switch(strtolower($period)) {
            case 1: return "+1 months";
            case 12: return "+12 months";
        }
    }

    private function expire($expired_reason = "admin") {
        if($this->status != self::STATUS_EXPIRED) {
            $this->status = self::STATUS_EXPIRED;
            $this->expired = time();
            $this->expired_reason = $expired_reason;
            $this->saveQuietly();
            
            // wysłanie powiadomienia
            
            $owner = Firm::getOwnerByUuid($this->uuid);
            if($owner)
                Notification::notify($owner->id, -1, $this->id, "subscription:expired");
        }
    }

    /*
     * Dezaktywowanie aktywnych pakietów dla których minął czas ważności (user_packages.end)
     */
    public static function deactivateExpiredPackages() {
        $currentTime = time();

        $activePackages = self::withoutGLobalScopes()->where("status", self::STATUS_ACTIVE)->where("end", "<=", $currentTime)->get();

        if(!$activePackages->isEmpty()) {
            foreach($activePackages as $row)
                $row->expire("auto_expire");
        }
    }
    
    public static function checkPackage($type, $exception = true)
    {
        $subscription = Subscription::where("status", Subscription::STATUS_ACTIVE)->first();
        
        if($subscription)
            throw new SubscriptionRequired(__("Subscription required"));
        
        $current = Limit::first();
        $limits = $subscription->free ? config("packages.free") : config("packages.paid");
        switch($type)
        {
            case "space":
                if($current && $current->space >= $limits["space"])
                {
                    if($exception)
                        throw new OutOffLimit(__("Maximum file size exceeded"));
                    return false;
                }
            break;
        }
    }
}