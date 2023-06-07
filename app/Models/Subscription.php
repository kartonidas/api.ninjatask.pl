<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\Subscription\Expired;
use App\Models\ExpirationNotify;
use App\Models\Order;

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
        }
        else
        {
            $row->end = strtotime(self::getPeriod($order->months), $row->end);
            $row->saveQuietly();
        }

        $order->subscription_id = $row->id;
        $order->saveQuietly();
        
        ExpirationNotify::where("subscription_id", $row->id)->delete();
        
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
            $firm = Firm::where("uuid", $this->uuid)->first();
            if($firm)
            {
                $user = User::where("firm_id", $firm->id)->where("owner", 1)->first();
                if($user)
                {
                    $settings = $user->getAccountSettings();
                    $locale = !empty($settings->locale) ? $settings->locale : config("api.default_language");
                    
                    Mail::to($user->email)->locale($locale)->queue(new Expired($this));
                }
            }
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
}