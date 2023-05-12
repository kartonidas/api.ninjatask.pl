<?php

namespace App\Traits;

use Exception;
use Throwable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User as UserModel;

trait User
{
    static $users = [];
    public function getUserName()
    {
        if(empty(static::$users[$this->user_id]))
        {
            $user = UserModel::where("id", $this->user_id)->withTrashed()->first();
            if($user)
                static::$users[$this->user_id] = $user->firstname . " " . $user->lastname;
        }
        
        if(empty(static::$users[$this->user_id]))
            static::$users[$this->user_id] = $this->user_id;
            
        return static::$users[$this->user_id];
    }
}