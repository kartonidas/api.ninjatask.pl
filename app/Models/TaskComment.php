<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "comment", "user_id");
    }
}