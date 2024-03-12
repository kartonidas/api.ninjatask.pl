<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Regon implements Rule
{
    public function __construct()
    {
        //
    }

    public function passes($attribute, $value)
    {
        $regon = trim($value);
        if(preg_match("/^[0-9]{9}$/", $regon)==false)
            return false;

        $digits = str_split($regon);
        $checksum = (8*intval($digits[0]) + 9*intval($digits[1]) + 2*intval($digits[2]) + 3*intval($digits[3]) + 4*intval($digits[4]) + 5*intval($digits[5]) + 6*intval($digits[6]) + 7*intval($digits[7]))%11;
        if($checksum == 10)
            $checksum = 0;

        if(intval($digits[8]) != $checksum)
            return false;
        
        return true;
    }

    public function message()
    {
        return __("Invalid REGON number");
    }
}
