<?php

namespace App\Mail\Register;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\UserRegisterToken;

class InitMessage extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public User $user, public UserRegisterToken $token, public string $source)
    {
    }
    
    public function getTitle()
    {
        return __('Thank you for registering! Please confirm and complete your information.');
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
        $view = 'emails.' . $this->locale . '.register.init';
        if(!view()->exists($view))
            $view = 'emails.'.config("api.default_language").'.register.init';
            
        return new Content(
            view: $view,
            with: [
                "locale" => $this->locale,
                "title" => $this->getTitle()
            ],
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
