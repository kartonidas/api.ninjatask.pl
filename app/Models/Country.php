<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    public static function getAllowedCodes()
    {
        $out = [];
        $rows = self::select("code")->get();
        foreach($rows as $row)
            $out[] = $row->code;
        return $out;
    }
}