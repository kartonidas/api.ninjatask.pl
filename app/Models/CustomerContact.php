<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use App\Models\CustomerContact;

class CustomerContact extends Model
{
    public const TYPE_EMAIL = "email";
    public const TYPE_PHONE = "phone";
}