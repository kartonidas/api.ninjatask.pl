<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserRegisterToken;

class ClearRegistersToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-registers-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear expired registers token';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        UserRegisterToken::removeExpiredRegisterToken();
    }
}
