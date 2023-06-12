<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\File;
use App\Models\Task;
use App\Models\TaskComment;

class Limit extends Model
{
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public static function calculate($uuid)
    {
        $size = 0;
        $taskRows = DB::select("SELECT id FROM tasks WHERE uuid = ? AND deleted_at IS NULL", [$uuid]);
        $taskIds = [-1];
        foreach($taskRows as $row)
            $taskIds[] = $row->id;
        $size += File::withoutGlobalScopes()->where("uuid", $uuid)->where("type", "tasks")->whereIn("object_id", $taskIds)->sum("size");
        
        $bindingsString = trim( str_repeat("?,", count($taskIds)), ",");
        $bindingsString = implode(",", array_fill(0, count($taskIds), "?"));
        $taskCommentRows = DB::select("SELECT id FROM task_comments WHERE task_id IN($bindingsString)", $taskIds);
        $taskCommentIds = [-1];
        foreach($taskCommentRows as $row)
            $taskCommentIds[] = $row->id;
        $size += File::withoutGlobalScopes()->where("uuid", $uuid)->where("type", "task_comments")->whereIn("object_id", $taskCommentIds)->sum("size");
            
        $projectRows = DB::select("SELECT id FROM projects WHERE uuid = ? AND deleted_at IS NULL", [$uuid]);
        $projectIds = [-1];
        foreach($projectRows as $row)
            $projectIds[] = $row->id;
        $size += File::withoutGlobalScopes()->where("uuid", $uuid)->where("type", "task_comments")->whereIn("object_id", $projectIds)->sum("size");
        
        $limit = self::withoutGlobalScopes()->where("uuid", $uuid)->first();
        if(!$limit)
        {
            $limit = new self;
            $limit->withoutGlobalScopes();
            $limit->uuid = $uuid;
        }
        $limit->tasks = count($taskRows);
        $limit->projects = count($projectRows);
        $limit->space = $size;
        $limit->saveQuietly();
    }
}