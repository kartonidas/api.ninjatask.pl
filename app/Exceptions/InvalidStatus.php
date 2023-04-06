<?php

namespace App\Exceptions;

use App\Exceptions\ApiBaseException;

class InvalidStatus extends ApiBaseException
{
    public $status = 404;
}