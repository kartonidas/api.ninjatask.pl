<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Messaging\AndroidConfig;
use App\Mail\Subscription\Activated;
use App\Mail\Subscription\Expiration;
use App\Mail\Subscription\Expired;
use App\Mail\Subscription\Renewed;
use App\Mail\Task\AssignedMessage;
use App\Mail\Task\ChangeStatusAssigned;
use App\Mail\Task\ChangeStatusOwner;
use App\Mail\Task\NewCommentAssigned;
use App\Mail\Task\NewCommentOwner;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Traits\DbTimestamp;

class Notification extends Model
{
    use DbTimestamp, SoftDeletes;
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "added_user_id", "object_id", "type", "object_name", "read", "read_time", "created_at", "extra_object_id");
    }
    
    public static function notify($user_id, $added_user_id, $object_id, $type, $extra_object_id = null)
    {
        $row = new self;
        $row->user_id = $user_id;
        $row->added_user_id = $added_user_id;
        $row->object_id = $object_id;
        $row->type = $type;
        $row->object_name = $row->getObjectName();
        $row->extra_object_id = $extra_object_id;
        $row->save();
        
        $user = User::find($row->user_id);
        $addedUser = User::find($row->added_user_id);
        if($user)
        {
            $settings = $user->getAccountSettings();
            $locale = $settings->locale;
            switch($type)
            {
                case "task:assign":
                case "task:change_status_owner":
                case "task:change_status_assigned":
                    $task = Task::find($row->object_id);
                    if($task)
                    {
                        if(in_array($type, $settings->notifications))
                        {
                            $url = env("FRONTEND_URL") . "task/" . $row->object_id;
                            if($type == "task:assign")
                                Mail::to($user->email)->locale($locale)->queue(new AssignedMessage($url, $task));
                            if($type == "task:change_status_owner")
                                Mail::to($user->email)->locale($locale)->queue(new ChangeStatusOwner($url, $task));
                            if($type == "task:change_status_assigned")
                                Mail::to($user->email)->locale($locale)->queue(new ChangeStatusAssigned($url, $task));
                        }
                        
                        if(in_array($type, $settings->mobile_notifications))
                            self::mobileTaskNotify($user_id, $addedUser, $task, $type, $locale);
                    }
                break;
            
                case "task:new_comment_owner":
                case "task:new_comment_assigned":
                    $comment = TaskComment::find($row->extra_object_id);
                    if($comment)
                    {
                        $task = Task::find($comment->task_id);
                        if($task)
                        {
                            if(in_array($type, $settings->notifications))
                            {
                                $url = env("FRONTEND_URL") . "task/" . $task->id;
                                if($type == "task:new_comment_owner")
                                    Mail::to($user->email)->locale($locale)->queue(new NewCommentOwner($url, $comment, $task));
                                if($type == "task:new_comment_assigned")
                                    Mail::to($user->email)->locale($locale)->queue(new NewCommentAssigned($url, $comment, $task));
                            }
                            
                            if(in_array($type, $settings->mobile_notifications))
                                self::mobileTaskNotify($user_id, $addedUser, $task, $type, $locale, $comment);
                        }
                    }
                break;
            
                case "subscription:activated":
                case "subscription:renewed":
                case "subscription:expiration3":
                case "subscription:expired":
                    $subscription = Subscription::withoutGlobalScopes()->find($row->object_id);
                    if($subscription)
                    {
                        if($type == "subscription:expired")
                            Mail::to($user->email)->locale($locale)->queue(new Expired($subscription));
                        if($type == "subscription:expiration3")    
                            Mail::to($user->email)->locale($locale)->queue(new Expiration($subscription, 3));
                        if($type == "subscription:activated")
                            Mail::to($user->email)->locale($locale)->queue(new Activated($subscription));
                        if($type == "subscription:renewed")
                            Mail::to($user->email)->locale($locale)->queue(new Renewed($subscription));
                            
                        $message = self::generateMessage($locale, $type, $subscription);
                        if($message)
                        {
                            $row->message = $message;
                            $row->save();
                        }
                    }
                break;
            }
        }
    }
    
    public function getObjectName()
    {
        $out = [];
        switch($this->type)
        {
            case "task:assign":
            case "task:change_status_owner":
            case "task:change_status_assigned":
            case "task:new_comment_owner":
            case "task:new_comment_assigned":
                $task = Task::withoutGlobalScopes()->find($this->object_id);
                if($task)
                    return $task->name;
            break;
            case "invoice:generated":
                $invoice = Invoice::withoutGlobalScopes()->find($this->object_id);
                if($invoice)
                    return $invoice->full_number;
            break;
        }
        return "";
    }
    
    private static function generateMessage($locale, $type, $object)
    {
        if(in_array($type, ["subscription:expired", "subscription:expiration3", "subscription:activated", "subscription:renewed"]) && $object instanceof Subscription)
        {
            $view = "messages." . $locale . "." . $type;
            if(!view()->exists($view))
                $view = "messages." . config("api.default_language") . "." . $type;
            
            if(view()->exists($view))
                return View::make($view, ["row" => $object])->render();
        }
        return null;
    }
    
    private static function mobileTaskNotify(int $user_id, User $addedUser, Task $task, string $type, $locale, TaskComment $comment = null)
    {
        $origLocale = app()->getLocale();
        app()->setLocale($locale);
        
        $content = null;
        switch($type)
        {
            case "task:assign":
                $content = __("New task assigned to your account");
            break;
            case "task:new_comment_assigned":
                if($addedUser)
                    $content = $addedUser->firstname . " " . __("added a new comment: ");
                else
                    $content = __("User added comment: ");
                
                $content .= Str::limit(strip_tags($comment->comment), 150);
            break;
        }
        if(!$content)
            return;
        
        $messaging = Firebase::messaging();
        $message = CloudMessage::withTarget("topic", "task." . $user_id)
            ->withNotification(FirebaseNotification::create($task->name, $content))
            ->withData([
                "_id" => $task->id,
                "_name" => $task->name,
                "_type" => "task:attach",
                "_module" => "task"
            ])
            ->withAndroidConfig(["ttl" => "120s"]);
        $messaging->send($message);
        
        app()->setLocale($origLocale);
    }
}