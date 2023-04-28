<?php

namespace App\Exceptions;

use App\Exceptions\ApiBaseException;

class AccessDenied extends ApiBaseException
{
    public $status = 403;
}