<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SmsHistoryRequest extends ListRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            "search.number" => "nullable|string",
            "search.date_from" => "nullable|date_format:Y-m-d",
            "search.date_to" => "nullable|date_format:Y-m-d",
        ]);
    }
}