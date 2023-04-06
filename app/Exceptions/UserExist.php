<?php

namespace App\Exceptions;

use App\Exceptions\ApiBaseException;

class UserExist extends ApiBaseException
{
    public $status = 409;
}