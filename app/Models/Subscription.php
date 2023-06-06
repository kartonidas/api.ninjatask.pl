<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class Subscription extends Model
{
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public static function addPackageFromOrder(Order $order)
    {
        $row = self::withoutGLobalScopes()->where("uuid", $order->uuid)->where("status", "active")->first();
        if(!$row)
        {
            $row = new self;
            $row->uuid = $order->uuid;
            $row->status = "active";
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
    }
    
    private static function getPeriod($period) {
        switch(strtolower($period)) {
            case 1: return "+1 months";
            case 12: return "+12 months";
        }
    }

    private function expire($expired_reason = "admin") {
        if($this->status != "expired") {
            $this->status = "expired";
            $this->expired = time();
            $this->expired_reason = $expired_reason;
            $this->saveQuietly();
        }
    }

    /*
     * Dezaktywowanie aktywnych pakietów dla których minął czas ważności (user_packages.end)
     */
    public static function deactivateExpiredPackages() {
        $currentTime = time();

        $activePackages = self::withoutGLobalScopes()->where("status", "active")->where("end", "<=", $currentTime)->get();

        if(!$activePackages->isEmpty()) {
            foreach($activePackages as $row)
                $row->expire("auto_expire");
        }
    }
}