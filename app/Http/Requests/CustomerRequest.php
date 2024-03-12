<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Customer;

class CustomerRequest extends ListRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            "search.name" => "nullable|string",
            "search.type" => ["nullable", Rule::in([Customer::TYPE_FIRM, Customer::TYPE_PERSON])],
            "search.nip" => "nullable|string",
            "search.address" => "nullable|string",
        ]);
    }
}