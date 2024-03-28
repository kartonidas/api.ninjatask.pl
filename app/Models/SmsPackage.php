<?php

namespace App\Models;

use DateTime;
use DateInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Jobs\SmsSend;
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
        // Sprawdzamy czy wysyłka miesci się w dostepnych do wysyłki godzinach
        // Jeśli nie kolejkujemy wysyłkę po dostępnej godzinie
        $hours = Sms::getServiceAllowedHours($uuid);
        if($hours != null)
        {
            $currentHour = intval(date("H"));
            if(!($currentHour >= $hours[0] && $currentHour < $hours[1]))
            {
                $date = new DateTime();
                if($currentHour < $hours[0])
                {
                    $date->setTime($hours[0], 0);
                }
                elseif($currentHour >= $hours[1])
                {
                    $date->add(new DateInterval("P1D"));
                    $date->setTime($hours[0], 0);
                }
                
                SmsSend::dispatch($uuid, $number, $message)->delay($date);
                return;
            }
        }
        
        DB::transaction(function () use($uuid, $number, $message) {
            $package = self::getPackage($uuid)->lockForUpdate()->first();
            
            if($package)
            {
                $status = Sms::send($uuid, $number, $message);
                if(!empty($status["status"]))
                {
                    $package->allowed = $package->allowed - ($status["used"] ?? 1);
                    $package->used = $package->used + ($status["used"] ?? 1);
                    $package->save();
                    
                    $history = new SmsPackageHistory;
                    $history->sms_package_id = $package->id;
                    $history->operation = SmsPackageHistory::OPERATION_SEND;
                    $history->quantity = -($status["used"] ?? 1);
                    $history->save();
                }
            }
        });
    }
}