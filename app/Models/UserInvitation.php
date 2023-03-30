<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInvitation extends Model
{
    public function getConfirmationUrl()
    {
        return env("FRONTEND_URL") . "?invitation=" . $this->token;
    }
}