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
        //\App\Models\SmsPackage::deposit("14d26775-0f60-40be-a4de-949daafe7e4c", 25);
        //die;
        
        //for($i = 0; $i < 90; $i++)
            \App\Jobs\SmsSend::dispatch("14d26775-0f60-40be-a4de-949daafe7e4c", 723310782, "Test!");
            
        die;
        
        $status = Sms::send("723310782", "Wiadomosc testowa API");
        print_r($status);
    }
}
