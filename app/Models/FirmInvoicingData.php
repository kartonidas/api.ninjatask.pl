<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Country;

class FirmInvoicingData extends Model
{
    use SoftDeletes;
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    protected $table = "firm_invoicing_data";
    
    public static function validateInvoicingData()
    {
        $row = self::first();
        if(!$row)
            return false;
        
        $required = [
            "street", "house_no", "city", "zip", "country"
        ];
        if($row->type == "invoice")
        {
            $required[] = "nip";
            $required[] = "name";
        }
        elseif($row->type == "receipt")
        {
            $required[] = "firstname";
            $required[] = "lastname";
        }
        else
            return false;
        
        foreach($required as $field)
        {
            if(empty(trim($row->{$field})))
                return false;
        }
        
        if(!in_array($row->country, Country::getAllowedCodes()))
            return false;
        
        return true;
    }
}