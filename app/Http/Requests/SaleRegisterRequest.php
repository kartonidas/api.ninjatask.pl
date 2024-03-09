<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\SaleRegister;

class SaleRegisterRequest extends ListRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            "search.type" => ["nullable", "string", Rule::in(array_keys(SaleRegister::getAllowedTypes(true)))],
        ]);
    }
}
