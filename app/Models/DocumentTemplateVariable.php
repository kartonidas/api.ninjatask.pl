<?php

namespace App\Models;
use App\Exceptions\Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplateVariable extends Model
{
    public const FIELD_CHECKBOX = "checkbox";
    public const FIELD_SELECT = "select";
    public const FIELD_TEXT = "text";
    public const FIELD_TEXTAREA = "textarea";
    
    public static function getAllowedTypes()
    {
        return [
            self::FIELD_TEXT => __("Text box"),
            self::FIELD_TEXTAREA => __("Large text box"),
            self::FIELD_CHECKBOX => __("Field yes/no"),
            self::FIELD_SELECT => __("Select list"),
        ];
    }
    
    public static function checkUniqueVariableNames($variables)
    {
        if(!empty($variables))
        {
            $allVariables = [];
            foreach($variables as $variable)
                $allVariables[] = $variable["variable"];
                
            $allUniqueVariables = array_unique(array_map("strtolower", $allVariables));
            if(count($allVariables) != count($allUniqueVariables))
                throw new Exception(__("Variable name must be unique"));
        }
    }
}