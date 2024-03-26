<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Libraries\SMS\Sms;
use App\Models\SmsPackage;
use App\Models\SmsPackageHistory;

class SmsPackage extends Model
{
    public $table = "sms_packages";
    
	use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public const STATUS_ACTIVE = "active";
    public const STATUS_EXPIRED = "expired";
    public const STATUS_USED = "used";
    
    public static function deposit($uuid, int $allowed, int $expired = null)
    {
        $package = new self;
        $package->uuid = $uuid;
        $package->status = self::STATUS_ACTIVE;
        $package->allowed = $allowed;
        $package->used = 0;
        $package->expired = $expired;
        $package->save();
    }
    
    public static function getPackage($uuid)
    {
        return self
            ::withoutGlobalScope("uuid")
            ->where("uuid", $uuid)
            ->where("status", self::STATUS_ACTIVE)
            ->where("allowed", ">", 0)
            ->orderBy("expired", "ASC")
            ->orderBy("id", "ASC");
    }
    
    public static function sendFromPackage($uuid, $number, $message)
    {
        DB::transaction(function () use($uuid, $number, $message) {
            $package = self::getPackage($uuid)->lockForUpdate()->first();
            
            if($package)
            {
                $status = Sms::send($number, $message);
                if($status)
                {
                    $package->allowed = $package->allowed - 1;
                    $package->used = $package->used + 1;
                    $package->save();
                    
                    $history = new SmsPackageHistory;
                    $history->sms_package_id = $package->id;
                    $history->operation = SmsPackageHistory::OPERATION_SEND;
                    $history->quantity = -1;
                    $history->save();
                }
            }
        });
    }
}