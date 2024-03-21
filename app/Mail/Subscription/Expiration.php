<?php

namespace App\Mail\Subscription;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use App\Libraries\Helper;
use App\Models\Subscription;

class Expiration extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Subscription $subscription, public Int $days)
    {
    }
    
    public function getTitle()
    {
        $days = app()->getLocale() == "pl" ? Helper::plurals($this->days, "dzień", "dni", "dni") : Helper::plurals($this->days, "day", "days", "days");
        return sprintf(__('ninjaTask. - your subscription will expire in %d %s'), $this->days, $days);
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
        $view = 'emails.' . $this->locale . '.subscription.expiration';
        if(!view()->exists($view))
            $view = 'emails.'.config("api.default_language").'.subscription.expiration';
            
        return new Content(
            view: $view,
            with: [
                "locale" => $this->locale,
                "subscription" => $this->subscription,
                "days" => $this->days,
                "title" => $this->getTitle(),
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
