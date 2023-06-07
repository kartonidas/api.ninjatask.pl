<?php

namespace App\Observers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\UserInvitation;
use App\Mail\Register\InviteMessage;

class UserInvitationObserver
{
    public function creating(UserInvitation $userInvitation): void
    {
        $userInvitation->token = Str::random(20) . ":" . Str::uuid()->toString();
    }
    
    public function created(UserInvitation $userInvitation): void
    {
        $user = User::find($userInvitation->invited_by);
        Mail::to($userInvitation->email)->locale(app()->getLocale())->queue(new InviteMessage($user, $userInvitation->getConfirmationUrl()));
    }
}
