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
    
    const OBJECT_INVOICE = "invoice";
    const OBJECT_CUSTOMER_INVOICE = "customer_invoice";
    
    protected $table = "firm_invoicing_data";
    
    protected $hidden = ["uuid"];
    
    public static function validateInvoicingData()
    {
        $row = self::first();
        if(!$row)
            return false;
        
        $required = [
            "street", "house_no", "city", "zip", "country"
        ];
        if($row->type == "firm")
        {
            $required[] = "nip";
            $required[] = "name";
        }
        elseif($row->type == "person")
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
    
    public function scopeInvoice(Builder $query): void
    {
        $query->where("object", self::OBJECT_INVOICE);
    }
    
    public function scopeCustomerInvoice(Builder $query): void
    {
        $query->where("object", self::OBJECT_CUSTOMER_INVOICE);
    }
}