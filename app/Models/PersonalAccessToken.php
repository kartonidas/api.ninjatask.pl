<?php

namespace App\Models;

use DateInterval;
use DateTime;
use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends Model
{
    public static function removeUnusedAccessToken()
    {
        $date = new DateTime();
        $date = $date->sub(new DateInterval("P" . env("DAYS_TO_EXPIRE_ACCESS_TOKEN", 30) . "D"));
        self::where("last_used_at", "<=", $date->format("Y-m-d H:i:s"))->delete();
    }
}