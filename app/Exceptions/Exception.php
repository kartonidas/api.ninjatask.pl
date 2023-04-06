<?php

namespace App\Exceptions;

use App\Exceptions\ApiBaseException;

class Exception extends ApiBaseException
{
    public $status = 422;
}