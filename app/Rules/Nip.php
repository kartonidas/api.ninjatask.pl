<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Libraries\ValidatorExt;

class Nip implements Rule
{
    public function passes($attribute, $value)
    {
        if(empty($value))
            return true;

        $value = preg_replace("/-|\s/", "", $value);

        if(strlen($value) != 10 || preg_match("/^[0-9]{10}$/", $value) == false)
            return false;

        $arrSteps = array(6, 5, 7, 2, 3, 4, 5, 6, 7);
        $intSum=0;
        for ($i = 0; $i < 9; $i++)
            $intSum += $arrSteps[$i] * $value[$i];
        $int = $intSum % 11;
        $intControlNr = ($int == 10) ? 0 : $int;
        if ($intControlNr !== (int)$value[9])
            return false;

        return true;
    }
    
    public function message()
    {
        return __("Invalid tax identification number");
    }
}