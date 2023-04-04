<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Firm;
use App\Models\User;

class FirmIdentifier implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $firmWithSpecifiedId = Firm::select("id")->where("identifier", $value)->get();
        $firmIds = [];
        if(!$firmWithSpecifiedId->isEmpty())
        {
            foreach($firmWithSpecifiedId as $row)
                $firmIds[] = $row->id;
                
            if(User::whereIn("firm_id", $firmIds)->where("owner", 1)->where("deleted", 0)->count())
                $fail(sprintf(__("The specified company ID is already taken.")));
        }
    }
}
