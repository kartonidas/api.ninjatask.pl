<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "name", "description");
    }
}