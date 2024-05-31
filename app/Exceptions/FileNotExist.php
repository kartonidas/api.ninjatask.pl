<?php

namespace App\Exceptions;

use App\Exceptions\ApiBaseException;

class FileNotExist extends ApiBaseException
{
    public $status = 404;
}