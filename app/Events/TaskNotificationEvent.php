<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Task;

class TaskNotificationEvent implements ShouldBroadcast
{
    public $channelName;
    public $message;

    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($channelName, Task $task)
    {
        $this->channelName = $channelName;
        $this->message = [
            "id" => $task->id,
            "name" => $task->name,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     * https://laravel.com/docs/broadcasting#model-broadcasting-channel-conventions
     * 
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return [new Channel($this->channelName)];
    }
}