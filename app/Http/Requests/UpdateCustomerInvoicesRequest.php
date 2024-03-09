<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\StoreCustomerInvoicesRequest;
use App\Traits\RequestUpdateRules;

class UpdateCustomerInvoicesRequest extends StoreCustomerInvoicesRequest
{
    use RequestUpdateRules;
    
    public function rules(): array
    {
        $rules = parent::rules();
        
        $rules["items.*.id"] = "sometimes|integer";
        
        unset($rules["type"]);
        unset($rules["sale_register_id"]);
        return $rules;
    }
}
