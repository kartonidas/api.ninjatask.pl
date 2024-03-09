<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

use App\Exceptions\Exception;
use App\Models\Numbering;
use App\Models\NumberingLock;

trait NumberingTrait
{
    public function setNumber()
    {
        if($this->number)
            return;

        $type = "";
        switch(get_class($this))
        {
            case \App\Models\Rental::class:
                $type = "rental";
            break;
        
            case \App\Models\CustomerInvoice::class:
                $type = "customer_invoice";
            break;
        }

        if(!$type)
            throw new Exception(__("Invalid source class type"));
        
        DB::transaction(function () use($type) {
            $numberingLock = NumberingLock::withoutGlobalScopes()->where("uuid", $this->uuid)->where("type", $type)->lockForUpdate()->first();
            if(!$numberingLock)
            {
                $numberingLock = new NumberingLock;
                $numberingLock->uuid = $this->uuid;
                $numberingLock->type = $type;
            }
            $numberingLock->val = ($numberingLock->val ?? 0) + 1;
            $numberingLock->save();
            
            $currentYear = date("Y");
            $currentMonth = date("m");

            $maskConfig = $this->getMaskNumber();
            $fullNumber = $maskConfig["mask"];

            $lastNumberQuery = Numbering::withoutGlobalScopes()->where("uuid", $this->uuid)->where("type", $type);
            if($type == "customer_invoice")
                $lastNumberQuery->where("sale_register_id", $this->sale_register_id);

            switch($maskConfig["continuation"])
            {
                case "month":
                    $lastNumberQuery->where("date", $currentYear . "-" . $currentMonth);
                break;
                case "year":
                    $lastNumberQuery->whereRaw("SUBSTRING(date, 1, 4) = ?", $currentYear);
                break;
            }

            $number = $lastNumberQuery->max("number") + 1;

            preg_match("/@N([1-9]+)?/i", $maskConfig["mask"], $matches);
            if($matches)
                $fullNumber = str_replace($matches[0], !empty($matches[1]) ? str_pad($number, $matches[1], "0", STR_PAD_LEFT) : $number, $fullNumber);

            $fullNumber = str_ireplace("@M", $currentMonth, $fullNumber);
            $fullNumber = str_ireplace("@Y", $currentYear, $fullNumber);

            $this->number = $number;
            $this->full_number = $fullNumber;
            $this->saveQuietly();

            $numb = new Numbering;
            $numb->uuid = $this->uuid;
            $numb->type = $type;
            $numb->number = $number;
            $numb->full_number = $fullNumber;
            $numb->date = $currentYear . "-" . $currentMonth;
            $numb->sale_register_id = $type == "customer_invoice" ? $this->sale_register_id : null;
            $numb->object_id = $this->id;
            $numb->save();
        });
    }
}
