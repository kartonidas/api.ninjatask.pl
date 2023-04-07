<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\DbTimestamp;
use App\Traits\File;

class TaskComment extends Model
{
    use DbTimestamp, File;
    
    public function delete()
    {
        $attachments = $this->getAttachments();
        foreach($attachments as $attachment)
            $attachment->delete();
        
        return parent::delete();
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "comment", "user_id", "created_at");
    }
}