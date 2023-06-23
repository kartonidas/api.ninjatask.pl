<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Models\Invoice;

class GenerateInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-invoice {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate invoice';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $id = intval($this->argument("id"));
        $invoice = Invoice::withoutGlobalScopes()->find($id);
        if(!$invoice)
            throw new Exception("Invoice not exists!");
        
        $invoice->generateInvoice(true, true);
        $this->info("Invoice generate successfull!");        
    }
}
