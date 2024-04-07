<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            "package" => ["required", Rule::in(array_keys(config("packages.allowed")))],
        ];
        
        if(!empty($this->package) && $this->package == "sms")
            $rules["quantity"] = ["required", "integer", Rule::in(config("packages.allowed")["sms"]["limit"])];
        
        return $rules;
    }
}