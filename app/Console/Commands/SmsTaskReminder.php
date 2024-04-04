<?php

namespace App\Console\Commands;

use DateInterval;
use DateTime;
use Exception;
use Illuminate\Console\Command;
use App\Models\SmsNotification;
use App\Models\SmsPackage;
use App\Models\SmsTaskReminder as SmsTaskReminderModel;
use App\Models\Task;
use App\Models\User;

class SmsTaskReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sms-task-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SMS task reminder';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $rows = SmsNotification
            ::withoutGlobalScope("uuid")
            ->where("type", SmsNotification::TYPE_TASK_REMINDER)
            ->where("send", 1)
            ->get();
            
        foreach($rows as $row)
        {
            $package = SmsPackage::getPackage($row->uuid)->first();
            if($package && $package->allowed > 0)
            {
                $date = (new DateTime())->add(new DateInterval("P" . $row->days . "D"));
                
                $tasks = Task
                    ::withoutGlobalScope("uuid")
                    ->where("uuid", $row->uuid)
                    ->where("start_date", "=", $date->format("Y-m-d"))
                    ->get();
                    
                foreach($tasks as $task)
                {
                    $send = false;
                    if($task->start_date_time)
                    {
                        $time = strtotime(date("Y-m-d") . " " . $task->start_date_time);
                        if(time() >= $time)
                            $send = true;
                    }
                    else
                        $send = true;
                    
                    if($send)
                    {
                        $users = User::whereIn("id", $task->getAssignedUserIds())->get();
                        foreach($users as $user)
                        {
                            $cnt = SmsTaskReminderModel::where("task_id", $task->id)->where("user_id", $user->id)->where("days", $row->days)->count();
                            if(!$cnt)
                            {
                                SmsNotification::taskMessage(SmsNotification::TYPE_TASK_REMINDER, $task, $user);
                                
                                $taskReminder = new SmsTaskReminderModel;
                                $taskReminder->task_id = $task->id;
                                $taskReminder->user_id = $user->id;
                                $taskReminder->days = $row->days;
                                $taskReminder->save();
                            }
                        }
                    }
                }
            }
        }
    }
}
