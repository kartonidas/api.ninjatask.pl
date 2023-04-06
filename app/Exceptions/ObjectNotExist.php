<?php

namespace App\Exceptions;

use App\Exceptions\ApiBaseException;

class ObjectNotExist extends ApiBaseException
{
    public $status = 404;
}