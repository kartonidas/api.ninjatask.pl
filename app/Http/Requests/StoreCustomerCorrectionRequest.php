<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\Http\Requests\StoreCustomerInvoicesRequest;
use App\Models\SaleRegister;

class StoreCustomerCorrectionRequest extends StoreCustomerInvoicesRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        
        unset($rules["type"]);
        unset($rules["customer_id"]);
        unset($rules["customer_type"]);
        unset($rules["customer_name"]);
        unset($rules["customer_street"]);
        unset($rules["customer_house_no"]);
        unset($rules["customer_apartment_no"]);
        unset($rules["customer_city"]);
        unset($rules["customer_zip"]);
        unset($rules["customer_country"]);
        unset($rules["customer_nip"]);
        unset($rules["payment_type_id"]);
        unset($rules["language"]);
        unset($rules["currency"]);
        
        $saleRegisteires = SaleRegister::where("type", SaleRegister::TYPE_CORRECTION)->pluck("id")->all();
        $rules["sale_register_id"] = ["required", Rule::in($saleRegisteires)];
        
        return $rules;
    }
}
