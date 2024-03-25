<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use App\Exceptions\Exception;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\TaskAssignedUser;
use App\Models\TaskComment;
use App\Models\TaskHistoryField;

class TaskHistory extends Model
{
    const UPDATED_AT = null;
    public $table = "task_history";
    
    public const OPERATION_CREATE = "create";
    public const OPERATION_UPDATE = "update";
    public const OPERATION_DELETE = "delete";
    public const OPERATION_SEND_SMS = "sms";
    public const OPERATION_SEND_EMAIL = "email";
    public const OPERATION_ASSIGN_USER = "assign_user";
    public const OPERATION_DEASSIGN_USER = "deassign_user";
    public const OPERATION_COMMENT_ADD = "comment_add";
    public const OPERATION_COMMENT_UPDATE = "comment_update";
    public const OPERATION_COMMENT_DELETE = "comment_delete";
    
    public static function getOperations()
    {
        return [
            self::OPERATION_CREATE => __("Task create"),
            self::OPERATION_UPDATE => __("Task update"),
            self::OPERATION_DELETE => __("Task delete"),
            self::OPERATION_SEND_SMS => __("Send SMS"),
            self::OPERATION_SEND_EMAIL => __("Task email"),
            self::OPERATION_ASSIGN_USER => __("Task assign user"),
            self::OPERATION_DEASSIGN_USER => __("Task deassign user"),
            self::OPERATION_COMMENT_ADD => __("Add comment"),
            self::OPERATION_COMMENT_UPDATE => __("Update comment"),
            self::OPERATION_COMMENT_DELETE => __("Delete comment"),
        ];
    }
    
    public static function log(string $operation, $object)
    {
        if(!in_array(get_class($object), [Task::class, TaskAssignedUser::class, TaskComment::class]))
            throw new Exception(__("Invalid object"));
        
        if(!in_array($operation, array_keys(self::getOperations())))
            throw new Exception(__("Invalid operation"));
        
        switch($operation)
        {
            case self::OPERATION_UPDATE:
                $changedData = self::getChangesObject($object);
                if(!empty($changedData))
                {
                    $history = self::saveHistory($object, $operation);
                    
                    foreach($changedData as $field => $data)
                    {
                        $historyField = new TaskHistoryField;
                        $historyField->task_history_id = $history->id;
                        $historyField->field = $field;
                        $historyField->value = $data["value"];
                        $historyField->old_value = $data["old_value"];
                        $historyField->save();
                    }
                }
            break;
        
            case self::OPERATION_COMMENT_UPDATE:
                $changedData = self::getChangesObject($object);
                if(!empty($changedData))
                {
                    $history = self::saveHistory($object, $operation);
                    
                    foreach($changedData as $field => $data)
                    {
                        $historyField = new TaskHistoryField;
                        $historyField->task_history_id = $history->id;
                        $historyField->field = $field;
                        $historyField->value = $data["value"];
                        $historyField->old_value = $data["old_value"];
                        $historyField->save();
                    }
                }
            break;
        
            default:
                $history = self::saveHistory($object, $operation);
        }
    }
    
    private static function saveHistory($object, $operation)
    {
        $history = new self;
        $history->task_id = self::getObjectId($object);
        $history->operation = $operation;
        $history->user_id = Auth::user()->id ?? 0;
        $history->extra = self::getExtra($object, $operation);
        $history->save();
        
        return $history;
    }
    
    private static function getChangesObject($object)
    {
        $out = [];
        $fields = self::getFields($object);
        if(!empty($fields))
        {
            foreach($fields as $field => $label)
            {
                if($object->isDirty($field))
                {
                    $newValue = $object->{$field};
                    $oldValue = $object->getOriginal($field);
                    if($object instanceof Task)
                    {
                        switch($field)
                        {
                            case "project_id":
                                $new = Project::withoutGlobalScope("uuid")->find($newValue);
                                $newValue = $new ? sprintf("%s [ID: %d]", $new->name, $new->id) : $newValue;
                                
                                $old = Project::withoutGlobalScope("uuid")->find($oldValue);
                                $oldValue = $old ? sprintf("%s [ID: %d]", $old->name, $old->id) : $oldValue;
                            break;
                        
                            case "status_id":
                                $new = Status::withoutGlobalScope("uuid")->find($newValue);
                                $newValue = $new ? sprintf("%s [ID: %d]", $new->name, $new->id) : $newValue;
                                
                                $old = Status::withoutGlobalScope("uuid")->find($oldValue);
                                $oldValue = $old ? sprintf("%s [ID: %d]", $old->name, $old->id) : $oldValue;
                            break;
                        
                            case "priority":
                                $priority = Task::getAllowedPriorities();
                                $newValue = !empty($priority[$newValue]) ? sprintf("%s [ID: %d]", $priority[$newValue], $newValue) : $newValue;
                                $oldValue = !empty($priority[$oldValue]) ? sprintf("%s [ID: %d]", $priority[$oldValue], $oldValue) : $oldValue;
                            break;
                        
                            case "description":
                                $newValue = strip_tags($newValue);
                                $oldValue = strip_tags($oldValue);
                            break;
                        }
                    }
                    
                    if($object instanceof TaskComment)
                    {
                        switch($field)
                        {
                            case "comment":
                                $newValue = strip_tags($newValue);
                                $oldValue = strip_tags($oldValue);
                            break;
                        }
                    }
                    
                    $out[$field] = [
                        "value" => $newValue,
                        "old_value" => $oldValue,
                    ];
                }
            }
        }
        return $out;
    }
    
    private static function getFields($object)
    {
        if($object instanceof Task)
        {
            return [
                "name" => __("Name"),
                "description" => __("Description"),
                "project_id" => __("Place"),
                "status_id" => __("Status"),
                "priority" => __("Priority"),
                "start_date" => __("Start date"),
                "start_date_time" => __("Start time"),
                "end_date" => __("End data"),
                "end_date_time" => __("End time"),
            ];
        }
        
        if($object instanceof TaskComment)
        {
            return [
                "comment" => __("Comment")
            ];
        }
        
        return [];
    }
    
    private static function getExtra($object, $operation)
    {
        if($object instanceof TaskAssignedUser)
        {
            $user = User::find($object->user_id);
            return $user ? ($user->firstname . " " . $user->lastname) : $object->user_id;
        }
        
        if($object instanceof TaskComment)
        {
            switch($operation)
            {
                case self::OPERATION_COMMENT_ADD:
                case self::OPERATION_COMMENT_DELETE:
                    return strip_tags($object->comment);
                break;
            }
        }
        
        return null;
    }
    
    private static function getObjectId($object)
    {
        if($object instanceof TaskAssignedUser)
            return $object->task_id;
        
        if($object instanceof TaskComment)
            return $object->task_id;
        
        return $object->id;
    }
}
