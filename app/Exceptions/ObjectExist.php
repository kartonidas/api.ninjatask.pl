<?php

namespace App\Exceptions;

use App\Exceptions\ApiBaseException;

class ObjectExist extends ApiBaseException
{
    public $status = 409;
}