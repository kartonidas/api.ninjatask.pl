<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InvoiceData extends Model
{
    protected $table = "invoice_data";
    
    public static function getActiveData()
    {
        return self::where("active", 1)->orderBy("created_at", "DESC")->first();
    }
}