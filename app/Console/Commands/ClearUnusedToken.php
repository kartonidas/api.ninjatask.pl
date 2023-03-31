<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PersonalAccessToken;

class ClearUnusedToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-unused-access-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear unused access token';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        PersonalAccessToken::removeUnusedAccessToken();
    }
}
