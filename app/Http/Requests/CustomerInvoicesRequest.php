<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\Models\SaleRegister;

class CustomerInvoicesRequest extends ListRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            "search.type" => ["nullable", "string", Rule::in(array_keys(SaleRegister::getAllowedTypes()))],
            "search.number" => "nullable|string|max:100",
            "search.customer_id" => "nullable|integer",
            "search.customer_name" => "nullable|string",
            "search.customer_nip" => "nullable|string",
            "search.date_from" => "nullable|date_format:Y-m-d",
            "search.date_to" => "nullable|date_format:Y-m-d",
            "search.sale_register_id" => "nullable|integer",
            "search.created_user_id" => "nullable|integer",
        ]);
    }
}
