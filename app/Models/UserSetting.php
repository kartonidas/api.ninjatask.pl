<?php

namespace App\Models;

use stdClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class UserSetting extends Model
{
    public static function getDafaultValues()
    {
        $obj = new stdClass();
        $obj->locale = config("api.default_language");
        return $obj;
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("locale");
    }
}