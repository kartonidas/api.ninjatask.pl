<?php

namespace App\Exceptions;

use App\Exceptions\ApiBaseException;

class ModuleNotAllowed extends ApiBaseException
{
    public $status = 403;
}
