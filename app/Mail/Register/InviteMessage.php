<?php

namespace App\Mail\Register;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class InviteMessage extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public User $user, public string $url)
    {
    }
    
    public function getTitle()
    {
        return __('Invitation to Activate Your Account in Our Application');
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
        $view = 'emails.' . $this->locale . '.register.invite';
        if(!view()->exists($view))
            $view = 'emails.'.config("api.default_language").'.register.invite';
            
        return new Content(
            view: $view,
            with: [
                "title" => $this->getTitle()
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
