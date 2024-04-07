<?php

namespace App\Jobs;

use DomDocument;
use DomXpath;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\MessageHistory;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;

class TaskLoggedMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user, public Task $task, public string $locale, public Mailable $mail)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->user->email, $this->user->email)->locale($this->locale)->send($this->mail);
        
        $messageHistory = new MessageHistory;
        $messageHistory->uuid = $this->user->getUuid();
        $messageHistory->type = MessageHistory::TYPE_EMAIL;
        $messageHistory->object = MessageHistory::OBJECT_TASK;
        $messageHistory->object_id = $this->task->id;
        $messageHistory->recipient = $this->user->email;
        $messageHistory->title = $this->mail->getTitle();
        $messageHistory->content = $this->getMailBody($this->mail->render());
        $messageHistory->save();
        
        $history = new TaskHistory;
        $history->task_id = $this->task->id;
        $history->object_id = $messageHistory->id;
        $history->operation = TaskHistory::OPERATION_SEND_EMAIL;
        $history->user_id = 0;
        $history->save();
    }
    
    private function getMailBody($content)
    {
        $dom = new DomDocument();
        $dom->loadHtml($content);
        $xpath = new \DomXpath($dom);
        $div = $xpath->query('//*[@id="mail-body"]')->item(0);
        return $dom->saveXML($div);
    }
}
