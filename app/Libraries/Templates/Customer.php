<?php

namespace App\Libraries\Templates;

use Illuminate\Support\Facades\Auth;
use App\Interfaces\Template;
use App\Libraries\Helper;
use App\Libraries\Render;
use App\Libraries\TemplateManager;
use App\Traits\TemplateVariablesTrait;

class Customer extends TemplateManager implements Template
{
    private static $customVariables = [];
    use TemplateVariablesTrait;

    public static function getType()
    {
        return "customer";
    }

    public static function getName()
    {
        return "Dokument dla klienta";
    }

    public static function getClassObject()
    {
        return \App\Models\Customer::class;
    }

    public function getFilename()
    {
        return "customer.pdf";
    }

    public function getTitle()
    {
        return "Kliet: " . $this->getObject()->full_number;
    }

    public static function getAvailableVars($array = false, $global = true)
    {
        $variables = [
            "fields" => [
                "klient_nazwa" => ["Nazwa zleceniodawcy", "name"],
                "klient_adres" => ["Adres zleceniodawcy", "address"],
                "klient_nip" => ["NIP zleceniodawcy", "nip"],
                "firma_nazwa" => ["Nazwa zleceniobiorcy", "firm_name"],
                "firma_adres" => ["Adres zleceniobiorcy", "firm_address"],
                "firma_nip" => ["NIP zleceniobiorcy", "firm_nip"],
                "data" => ["Data zawarcia umowy", "date"],
            ]
        ];
        
        if(!empty(static::$customVariables))
        {
            foreach(static::$customVariables as $fieldName => $tmp)
                $variables["fields"][$fieldName] = ["", $fieldName];
        }

        if($array)
            return self::availableVarsToArray($variables);

        return $variables;
    }

    public function getData()
    {
        $out = [];
        $object = $this->getObject();
        $out = $object->toArray();
        $out["address"] = Helper::generateAddress($object, ", ");
        
        $firmData = Auth::user()->getFirm();
        if($firmData)
        {
            $out["firm_name"] = $firmData->name ? $firmData->name : ($firmData->firstname . " " . $firmData->lastname);
            $out["firm_address"] = Helper::generateAddress($firmData, ", ");
            $out["firm_nip"] = $firmData->nip;
        }
        
        $out["date"] = date("Y-m-d");
        
        $out = array_merge($out, static::$customVariables);
        
        return $out;
    }
    
    public function setCustomVariables(array $variables)
    {
        static::$customVariables = $variables;
    }
}
