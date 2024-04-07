<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Models\UserInvitation;
use App\Observers\UserInvitationObserver;
use App\Models\TaskTime;
use App\Observers\TaskTimeObserver;
use App\Models\Task;
use App\Observers\TaskObserver;
use App\Models\TaskAssignedUser;
use App\Observers\TaskAssignedUserObserver;
use App\Models\TaskComment;
use App\Observers\TaskCommentObserver;
use App\Models\Project;
use App\Observers\ProjectObserver;
use App\Models\File;
use App\Observers\FileObserver;
use App\Models\Firm;
use App\Observers\FirmObserver;
use App\Models\CustomerInvoice;
use App\Observers\CustomerInvoiceObserver;
use App\Models\SmsPackage;
use App\Observers\SmsPackageObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        UserInvitation::observe(UserInvitationObserver::class);
        TaskTime::observe(TaskTimeObserver::class);
        Task::observe(TaskObserver::class);
        TaskAssignedUser::observe(TaskAssignedUserObserver::class);
        TaskComment::observe(TaskCommentObserver::class);
        Project::observe(ProjectObserver::class);
        File::observe(FileObserver::class);
        Firm::observe(FirmObserver::class);
        CustomerInvoice::observe(CustomerInvoiceObserver::class);
        SmsPackage::observe(SmsPackageObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
