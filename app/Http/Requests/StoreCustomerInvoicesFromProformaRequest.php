<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\Http\Requests\StoreCustomerInvoicesRequest;
use App\Models\SaleRegister;

class StoreCustomerInvoicesFromProformaRequest extends StoreCustomerInvoicesRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        
        unset($rules["type"]);
        $saleRegisteires = SaleRegister::where("type", SaleRegister::TYPE_INVOICE)->pluck("id")->all();
        $rules["sale_register_id"] = ["required", Rule::in($saleRegisteires)];
        
        return $rules;
    }
}