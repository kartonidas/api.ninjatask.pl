<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\Http\Requests\StoreCustomerInvoicesRequest;

class StoreCustomerInvoicesFromProformaRequest extends StoreCustomerInvoicesRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules["type"]);
        return $rules;
    }
}