<?php

namespace App\Exceptions;

use App\Exceptions\ApiBaseException;

class Unauthorized extends ApiBaseException
{
    public $status = 401;
}