<?php

namespace App\Mail\Task;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class AssignedMessage extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public string $url, public Task $task)
    {
    }
    
    public function getTitle($mailSubject = true)
    {
        if($mailSubject)
        {
            $project = Project::withoutGlobalScopes()->find($this->task->project_id);
            if($project)
                return "[" . $project->name . "] " . $this->task->name .  " (" . __('New task') . ")";
        }
        
        return __('New task');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->getTitle(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = 'emails.' . $this->locale . '.task.assigned';
        if(!view()->exists($view))
            $view = 'emails.'.config("api.default_language").'.task.assigned';
        
        return new Content(
            view: $view,
            with: [
                "title" => $this->getTitle(false),
                "task" => $this->task,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
