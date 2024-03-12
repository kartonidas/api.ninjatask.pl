<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ContactPhone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $allowedPhoneCodes = [];
        if(file_exists(resource_path("js/data/phone_codes.json")))
        {
            $json = file_get_contents(resource_path("js/data/phone_codes.json"));
            $phoneCodes = json_decode($json);
            foreach($phoneCodes as $phoneCode)
                $allowedPhoneCodes[] = $phoneCode->code;
        }
        
        foreach($value as $contact)
        {
            if(!empty($contact["val"]))
            {
                if(empty($contact["prefix"]))
                    $fail(__("Area code is required"));
                else
                {
                    if(!in_array($contact["prefix"], $allowedPhoneCodes))
                        $fail(__("Invalid area code"));
                    else
                    {
                        if(!preg_match('/^[0-9]{3}-[0-9]{3}-[0-9]{3}([0-9]{1,6})?$/', $contact["val"]))
                            $fail(__("Invalid phone number"));
                    }
                }
            }
        }
    }
    
}
