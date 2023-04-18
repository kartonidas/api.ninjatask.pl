<?php

namespace App\Models;

use DateInterval;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use App\Exceptions\Exception;
use App\Models\User;

class UserRegisterToken extends Model
{
    public static function removeExpiredRegisterToken()
    {
        $date = new DateTime();
        $date = $date->sub(new DateInterval("P" . env("DAYS_TO_EXPIRE_REGISTER_TOKEN", 7) . "D"));
        $rows = self::where("created_at", "<=", $date->format("Y-m-d H:i:s"))->get();
        foreach($rows as $row)
        {
            $user = User::find($row->user_id);
            if($user && !$user->activated)
                $user->delete();
            $row->delete();
        }
    }
    
    public function checkExpiration()
    {
        if(time() > strtotime($this->code_expired_at))
            throw new Exception(__("Register token expired"));
    }
}