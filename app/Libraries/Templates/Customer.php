<?php

namespace App\Libraries\Templates;

use App\Interfaces\Template;
use App\Libraries\Helper;
use App\Libraries\Render;
use App\Libraries\TemplateManager;
use App\Traits\TemplateVariablesTrait;

class Customer extends TemplateManager implements Template
{
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
                "klient_nazwa" => ["Nazwa najemcy", "name"],
                "klient_adres" => ["Adres najemcy", "address"],
            ]
        ];

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
        
        return $out;
    }
}
