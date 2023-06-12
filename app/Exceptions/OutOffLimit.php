<?php

namespace App\Exceptions;

use App\Exceptions\ApiBaseException;

class OutOffLimit extends ApiBaseException
{
    public $status = 403;
}