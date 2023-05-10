<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
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
    
    public function canDelete() {
        return Auth::user()->owner || (Auth::user()->id == $this->user_id);
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "comment", "user_id", "created_at");
    }
    
    static $users = [];
    public function getUserName()
    {
        if(empty(static::$users[$this->user_id]))
        {
            $user = User::where("id", $this->user_id)->withTrashed()->first();
            if($user)
                static::$users[$this->user_id] = $user->firstname . " " . $user->lastname;
        }
        
        if(empty(static::$users[$this->user_id]))
            static::$users[$this->user_id] = $this->user_id;
            
        return static::$users[$this->user_id];
    }
}