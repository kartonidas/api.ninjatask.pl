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

class ChangeStatusAssigned extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public string $url, public Task $task)
    {
    }
    
    public function getTitle()
    {
        return "ninjaTask. - " . $this->task->name .  " [" . mb_strtolower(__('Change status')) . "]";
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
        $project = Project::withoutGlobalScopes()->find($this->task->project_id);
        
        $view = 'emails.' . $this->locale . '.task.change-status-assigned';
        if(!view()->exists($view))
            $view = 'emails.'.config("api.default_language").'.task.update-status-assigned';
        
        return new Content(
            view: $view,
            with: [
                "title" => $this->getTitle(),
                "task" => $this->task,
                "project" => $project,
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
