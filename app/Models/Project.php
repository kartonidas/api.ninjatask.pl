<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Customer;
use App\Models\SoftDeletedObject;
use App\Models\Task;
use App\Traits\DbTimestamp;

class Project extends Model
{
    use DbTimestamp, SoftDeletes;
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    protected $hidden = ["uuid"];
    
    public static $sortable = ["name", "created_at"];
    public static $defaultSortable = ["created_at", "desc"];
    
    public function delete()
    {
        $tasks = Task::where("project_id", $this->id)->get();
        if(!$tasks->isEmpty())
        {
            foreach($tasks as $task)
            {
                $task->delete();
                $sdo = new SoftDeletedObject;
                $sdo->source = "project";
                $sdo->source_id = $this->id;
                $sdo->object = "task";
                $sdo->object_id = $task->id;
                $sdo->save();
            }
        }
        return parent::delete();
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "name", "location", "description", "owner", "address", "lat", "lon", "customer_id", "created_at");
    }
    
    public function getTaskCount()
    {
        $cntTotal = Task::where("project_id", $this->id)->count();
        $cntOpened = Task::where("project_id", $this->id)->where("state", "!=", Task::STATE_CLOSED)->count();
        return [
            "total" => $cntTotal,
            "opened" => $cntOpened,
            "me" => 3
        ];
    }
    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, "customer_id");
    }
}