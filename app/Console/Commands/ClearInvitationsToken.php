<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserInvitation;

class ClearInvitationsToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-invitations-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear expired invitations token';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        UserInvitation::removeExpiredInvitationsToken();
    }
}
