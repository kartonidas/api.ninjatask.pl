<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ContactEmail implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach($value as $contact)
        {
            if(!empty($contact["val"]))
            {
                if(mb_strlen($contact["val"]) > 50)
                    $fail(__("E-mail max :max characters", ["max" => 50]));
                else
                    if(!filter_var($contact["val"], FILTER_VALIDATE_EMAIL))
                        $fail(__("Invalid e-mail address"));
            }
        }
    }
    
}
