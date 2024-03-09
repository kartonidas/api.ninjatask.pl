<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\Models\User;

class UpdateCustomerCorrectionRequest extends FormRequest
{
    public function rules(): array
    {
        $userIds = User::pluck("id")->all();
        
        $rules = [];
        $rules["created_user_id"] = ["required", Rule::in($userIds)];
        $rules["comment"] = ["nullable", "max:5000"];
        $rules["document_date"] = ["required", "date_format:Y-m-d"];
        $rules["sell_date"] = ["required", "date_format:Y-m-d"];
        $rules["payment_date"] = ["required", "date_format:Y-m-d"];
        $rules["account_number"] = "sometimes|string|max:60";
        
        $rules["items"] = "required|array";
        $rules["items.*.gtu"] = "sometimes|max:10";
        $rules["items.*.name"] = "required|max:200";
        $rules["items.*.quantity"] = "required|numeric|gt:0";
        $rules["items.*.unit_type"] = "required|string|max:50";
        $rules["items.*.net_amount"] = "required|numeric|gt:0";
        $rules["items.*.vat_value"] = ["required", Rule::in(array_keys(config("invoice.vat")))];
        $rules["items.*.discount"] = "sometimes|numeric|gt:0";
        return $rules;
    }
}

