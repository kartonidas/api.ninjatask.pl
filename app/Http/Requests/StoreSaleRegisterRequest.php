<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

use Illuminate\Validation\Rule;
use App\Models\SaleRegister;

class StoreSaleRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            "name" => "required|max:100",
            "type" => ["required", Rule::in(array_keys(SaleRegister::getAllowedTypes()))],
            "mask" => "required|max:100",
            "continuation" => ["required", Rule::in(array_keys(SaleRegister::getNumberingContinuation()))],
            "is_default" => "sometimes|nullable|max:20",
        ];
        return $rules;
    }
}
