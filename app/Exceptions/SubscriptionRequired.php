<?php

namespace App\Exceptions;

use App\Exceptions\ApiBaseException;

class SubscriptionRequired extends ApiBaseException
{
    public $status = 402;
}
