<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Numbering extends Model
{
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }

    public $timestamps = false;
    protected $table = 'numbering';
    
    const NUMBERING_MONTH = "month";
    const NUMBERING_YEAR = "year";
    const NUMBERING_CONTINUOUS = "continuation";
    
    public static function getNumberingContinuation()
    {
        return [
            self::NUMBERING_MONTH => __("miesięczna"),
            self::NUMBERING_YEAR => __("roczna"),
            self::NUMBERING_CONTINUOUS => __("ciągła"),
        ];
    }
}
