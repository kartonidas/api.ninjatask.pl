<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CustomerInvoice;

class SaleRegister extends Model
{
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }

    protected $table = "sale_register";
    
    const TYPE_PROFORMA = "proforma";
    const TYPE_INVOICE = "invoice";
    const TYPE_CORRECTION = "correction";
    
    const NUMBERING_MONTH = "month";
    const NUMBERING_YEAR = "year";
    const NUMBERING_CONTINUOUS = "continuation";
    
    protected $hidden = ["uuid"];
    
    public static function getNumberingContinuation()
    {
        return [
            self::NUMBERING_MONTH => __("monthly"),
            self::NUMBERING_YEAR => __("yearly"),
            self::NUMBERING_CONTINUOUS => __("continuous"),
        ];
    }
    
    public static function getAllowedTypes($withoutCorrection = false)
    {
        $types = [
            self::TYPE_PROFORMA => __("proforma"),
            self::TYPE_INVOICE => __("invoice"),
        ];
        
        if(!$withoutCorrection)
            $types[self::TYPE_CORRECTION] = __("correction");
        
        return $types;
    }

    public function canDelete()
    {
        if(CustomerInvoice::where("sale_register_id", $this->id)->count() > 0)
            return false;

        return true;
    }

    public function setIsDefault($flag)
    {
        if($flag)
        {
            $currentDefaultValues = self::where("type", $this->type)->where("id", "!=", $this->id)->where("is_default", 1)->get();
            foreach($currentDefaultValues as $currentDefaultValue)
            {
                $currentDefaultValue->is_default = 0;
                $currentDefaultValue->save();
            }
        }

        $this->is_default = $flag;
    }

    public static function getDefaultValue($type)
    {
        $row = self::where("type", $type)->where("is_default", "1")->first();
        if($row)
            return $row->id;

        return false;
    }
}
