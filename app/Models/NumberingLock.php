<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumberingLock extends Model 
{
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public $timestamps = false;
    public $table = "numbering_lock";
}