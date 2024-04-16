<?php

namespace App\Traits;
use App\Libraries\Helper;

trait TemplateVariablesTrait
{
    public function prepareVariables()
    {
        $out = [];
        $data = $this->getData();
        $variables = self::getAvailableVars(true);

        if(!empty($variables["fields"]))
        {
            foreach($variables["fields"] as $field => $fieldProperties)
            {
                $out[$field] = $data[$fieldProperties[1]] ?? "_______________";
            }
        }

        if(!empty($variables["tab"]))
        {
            foreach($variables["tab"] as $table => $fields)
            {
                if(!isset($out[$table]))
                    $out[$table] = [];

                if(!empty($data[$table]))
                {
                    foreach($data[$table] as $dataRow)
                    {
                        $dataRowVariables = [];
                        foreach($fields as $field => $fieldProperties)
                        {
                            $dataRowVariables[$field] = $dataRow[$fieldProperties[1]] ?? "";
                        }
                        $out[$table][] = $dataRowVariables;
                    }
                }
            }
        }

        return $out;
    }

    public function addPrefixToArrayKeys($prefix, $array)
    {
        return array_combine(array_map(function($key) use($prefix){ return $prefix . "." . $key; }, array_keys($array)), $array);
    }

    public static function availableVarsToArray($variables)
    {
        $out = [];
        foreach($variables as $type => $vars)
        {
            $outArray = [];
            foreach($vars as $key => $value)
                Helper::makeArray($key, $outArray, $value);
            $out[$type] = $outArray;
        }

        return $out;
    }
}
