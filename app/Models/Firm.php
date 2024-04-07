<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SmsPackage;
use App\Models\User;
use App\Models\SmsNotification;

class Firm extends Model
{
    use SoftDeletes;
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("identifier", "firstname", "lastname", "email", "nip", "name", "street", "house_no", "apartment_no", "city", "zip", "country", "phone");
    }
    
    public function getOwner()
    {
        return User::where("firm_id", $this->id)->where("owner", 1)->first();
    }
    
    public static function getOwnerByUuid($uuid)
    {
        $firm = Firm::where("uuid", $uuid)->first();
        if($firm)
        {
            $user = User::where("firm_id", $firm->id)->where("owner", 1)->first();
            if($user)
                return $user;
        }
    }
    
    public function getSmsConfig()
    {
        $packages = SmsPackage
            ::withoutGlobalScope("uuid")
            ->where("uuid", $this->uuid)
            ->where("status", SmsPackage::STATUS_ACTIVE)
            ->where(function($q) {
                $q->whereNull("expired")->orWhere("expired", ">", time());
            })
            ->get();
        
        $out = ["allowed" => 0, "used" => 0];
        foreach($packages as $package)
        {
            $out["allowed"] += $package->allowed;
            $out["used"] += $package->used;
        }
        
        $out = array_merge($out, SmsNotification::getNotifications($this->uuid));
        return $out;
    }
}