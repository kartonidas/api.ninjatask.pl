<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Firm;

class RestoreFirm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:restore-firm {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore soft deleted firm';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $id = intval($this->argument("id"));
        $firm = Firm::withoutGlobalScopes()->withTrashed()->find($id);
        
        if(!$firm)
            $this->output->writeln("<error>Firm does not exists</error>");
        else
        {
            if(!$firm->trashed())
                $this->output->writeln("<error>Currently firm is not deleted</error>");
            else
            {
                $firm->restore();
                $this->info("Firm successfull restored!");        
            }
        }
    }
}
