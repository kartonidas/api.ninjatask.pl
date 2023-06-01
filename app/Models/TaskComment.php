<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\DbTimestamp;
use App\Traits\File;
use App\Traits\User;

class TaskComment extends Model
{
    use DbTimestamp, File, User;
    
    public function delete($withoutUuidScope = false)
    {
        $attachments = $this->getAttachments(null, $withoutUuidScope);
        foreach($attachments as $attachment)
            $attachment->delete();
        
        return parent::delete();
    }
    
    public function canDelete() {
        return Auth::user()->owner || (Auth::user()->id == $this->user_id);
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "comment", "user_id", "created_at");
    }
}