<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
            "first" => "nullable|integer|gte:0",
            "sort" => "nullable",
            "order" => "nullable|integer",
        ];
    }
}