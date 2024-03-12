<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Customer;
use App\Models\Country;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            "type" => ["required", Rule::in(Customer::TYPE_PERSON, Customer::TYPE_FIRM)],
            "name" => "required|max:100",
            "street" => "sometimes|max:80",
            "house_no" => "sometimes|max:20",
            "apartment_no" => "sometimes|max:20",
            "city" => "sometimes|max:120",
            "zip" => "sometimes|max:10",
            "country" => ["sometimes", Rule::in(Country::getAllowedCodes())],
            "comments" => "sometimes|max:5000",
            "send_sms" => "sometimes|boolean",
            "send_email" => "sometimes|boolean",
        ];
        
        if(($this->type ?? "") == Customer::TYPE_FIRM)
        {
            $rules["nip"] = ["sometimes", "max:20", new \App\Rules\Nip];
            $rules["regon"] = ["sometimes", "max:15", new \App\Rules\Regon];
        }
            
        $rules["contacts.email"] = ["sometimes", "array", new \App\Rules\ContactEmail];
        $rules["contacts.phone"] = ["sometimes", "array", new \App\Rules\ContactPhone];
            
        return $rules;
    }
}
