<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

use App\Libraries\SMS\Sms;

class TestSmsMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-sms-message';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send testing sms message';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Sms::send("723310782", "Wiadomosc testowa API");
    }
}
