<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\UserInvitation;

use App\Mail\Register\InitMessage;
use App\Mail\Register\InviteMessage;
use App\Mail\Register\WelcomeMessage;
use App\Mail\Subscription\Activated;
use App\Mail\Subscription\Expiration;
use App\Mail\Subscription\Expired;
use App\Mail\Subscription\Renewed;
use App\Mail\Task\AssignedMessage;
use App\Mail\Task\ChangeStatusAssigned;
use App\Mail\Task\ChangeStatusOwner;
use App\Mail\Task\NewCommentAssigned;
use App\Mail\Task\NewCommentOwner;
use App\Mail\User\ForgotPasswordMessage;

use App\Models\Subscription;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\UserRegisterToken;

class TestEmailMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-email-messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'test all email messages';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        app()->setLocale("en");
        $user = User::find(1);
        if(!$user)
            throw new Exception("Brak użytkownika o podanym ID");
        
        $this->userMessages($user);
        $this->subscriptionMessages($user);
        $this->taskMessages($user);
    }
    
    private function userMessages(User $user)
    {
        $token = UserRegisterToken::find(20);
        if(!$token)
            throw new Exception("UserRegisterToken nie istnieje");
        
        $url = "https::/ninjatask.pl";
        
        Mail::to($user->email, $user->email)->locale(app()->getLocale())->send(new InitMessage($user, $token, "www"));
        echo "InitMessage\n";

        Mail::to($user->email, $user->email)->locale(app()->getLocale())->send(new InitMessage($user, $token, "app"));
        echo "InitMessage:app\n";

        Mail::to($user->email, $user->email)->locale(app()->getLocale())->send(new WelcomeMessage($user));
        echo "WelcomeMessage\n";

        Mail::to($user->email, $user->email)->locale(app()->getLocale())->send(new ForgotPasswordMessage($url));
        echo "ForgotPasswordMessage\n";
        
        Mail::to($user->email, $user->email)->locale(app()->getLocale())->send(new InviteMessage($user, $url));
        echo "InviteMessage\n";
    }
    
    private function subscriptionMessages(User $user)
    {
        $uuid = $user->getUuid();
        $subscription = Subscription::withoutGlobalScope("uuid")->where("uuid", $uuid)->where("status", Subscription::STATUS_ACTIVE)->first();
        if(!$subscription)
            throw new Exception("Brak aktywnej subskrypcji dla podanego użytkownika");
        
        Mail::to($user->email, $user->email)->locale(app()->getLocale())->send(new Activated($subscription));
        echo "Activated\n";
        
        Mail::to($user->email, $user->email)->locale(app()->getLocale())->send(new Expiration($subscription, 1));
        echo "Expiration\n";
        
        Mail::to($user->email, $user->email)->locale(app()->getLocale())->send(new Expired($subscription));
        echo "Expired\n";
        
        Mail::to($user->email, $user->email)->locale(app()->getLocale())->send(new Renewed($subscription));
        echo "Renewed\n";
    }
    
    private function taskMessages(User $user)
    {
        $uuid = $user->getUuid();
        $task = Task::withoutGlobalScope("uuid")->where("uuid", $uuid)->orderBy("id", "DESC")->first();
        if(!$task)
            throw new Exception("Brak zadania");
        
        $comment = TaskComment::where("task_id", $task->id)->orderBy("id", "DESC")->first();
        if(!$comment)
            throw new Exception("Brak komentarza");
        
        $url = "https::/ninjatask.pl";
        
        Mail::to($user->email, $user->email)->locale(app()->getLocale())->send(new AssignedMessage($url, $task));
        echo "AssignedMessage\n";
        
        Mail::to($user->email, $user->email)->locale(app()->getLocale())->send(new ChangeStatusOwner($url, $task));
        echo "ChangeStatusOwner\n";
        
        Mail::to($user->email, $user->email)->locale(app()->getLocale())->send(new ChangeStatusAssigned($url, $task));
        echo "ChangeStatusAssigned\n";
        
        Mail::to($user->email, $user->email)->locale(app()->getLocale())->send(new NewCommentOwner($url, $comment, $task));
        echo "NewCommentOwner\n";
        
        Mail::to($user->email, $user->email)->locale(app()->getLocale())->send(new NewCommentAssigned($url, $comment, $task));
        echo "NewCommentAssigned\n";
    }
}
