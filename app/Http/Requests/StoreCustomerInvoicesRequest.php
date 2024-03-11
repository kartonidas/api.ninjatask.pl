<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\Dictionary;
use App\Models\User;

class StoreCustomerInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [];
        $customerIds = Customer::pluck("id")->all();
        $userIds = User::pluck("id")->all();
        $paymentTypeIds = Dictionary::where("type", "payment_types")->pluck("id")->all();
        
        $rules["type"] = ["required", Rule::in(array_keys(config("invoice.sale_document_types")))];
        $rules["created_user_id"] = ["required", Rule::in($userIds)];
        
        $rules["customer_id"] = ["sometimes", Rule::in($customerIds)];
        $rules["customer_type"] = ["required", Rule::in(CustomerInvoice::TYPE_PERSON, CustomerInvoice::TYPE_FIRM)];
        $rules["customer_name"] = "required|max:100";
        $rules["customer_street"] = "required|max:80";
        $rules["customer_house_no"] = "sometimes|max:20";
        $rules["customer_apartment_no"] = "sometimes|max:20";
        $rules["customer_city"] = "required|max:120";
        $rules["customer_zip"] = "required|max:10";
        $rules["customer_country"] = ["required", Rule::in(Country::getAllowedCodes())];
        if(($this->customer_type ?? "") == CustomerInvoice::TYPE_FIRM)
            $rules["customer_nip"] = ["required", "max:20", new \App\Rules\Nip];
            
        $rules["comment"] = ["nullable", "max:5000"];
        $rules["document_date"] = ["required", "date_format:Y-m-d"];
        $rules["sell_date"] = ["required", "date_format:Y-m-d"];
        $rules["payment_date"] = ["required", "date_format:Y-m-d"];
        $rules["payment_type_id"] = ["required", Rule::in($paymentTypeIds)];
        $rules["account_number"] = "sometimes|string|max:60";
        $rules["swift_number"] = "sometimes|string|max:60";
        $rules["language"] = ["required", Rule::in(config("invoice.languages"))];
        $rules["currency"] = ["required", Rule::in(config("invoice.currencies"))];
        
        $rules["items"] = "required|array";
        $rules["items.*.gtu"] = "sometimes|max:10";
        $rules["items.*.name"] = "required|max:200";
        $rules["items.*.quantity"] = "required|numeric|gt:0";
        $rules["items.*.unit_type"] = "required|string|max:50";
        $rules["items.*.net_amount"] = "required|numeric|gt:0";
        $rules["items.*.vat_value"] = ["required", Rule::in(array_keys(config("invoice.vat")))];
        $rules["items.*.discount"] = "sometimes|numeric|gte:0";
        return $rules;
    }
}
