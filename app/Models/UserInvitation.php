<?php

namespace App\Models;

use DateInterval;
use DateTime;
use Illuminate\Database\Eloquent\Model;

class UserInvitation extends Model
{
    public function getConfirmationUrl()
    {
        return env("FRONTEND_URL") . "?invitation=" . $this->token;
    }
    
    public static function removeExpiredInvitationsToken()
    {
        $date = new DateTime();
        $date = $date->sub(new DateInterval("P" . env("DAYS_TO_EXPIRE_INVITATION_TOKEN", 7) . "D"));
        self::where("created_at", "<=", $date->format("Y-m-d H:i:s"))->delete();
    }
}