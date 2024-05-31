<?php

namespace App\Http\Requests;

use App\Http\Requests\TaskStoreRequest;

class TaskUpdateRequest extends TaskStoreRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules["project"] = "nullable|array";
        $rules["project_id"] = "nullable|integer";
        return $rules;
    }
}
