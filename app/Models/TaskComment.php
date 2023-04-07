<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\DbTimestamp;

class TaskComment extends Model
{
    use DbTimestamp;
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "comment", "user_id", "created_at");
    }
}