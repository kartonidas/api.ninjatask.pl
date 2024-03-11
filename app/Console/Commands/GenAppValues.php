<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Libraries\Templates\Rental as TemplateRental;
use App\Libraries\Data;
use App\Models\Dictionary;

class GenAppValues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:gen-values';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate App values (resources/js/data/values.json)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $languages = ["pl", "en"];
        
        $toJson = [];
        foreach($languages as $lang)
        {
            app()->setLocale($lang);
            
            if(!isset($toJson[$lang]))
                $toJson[$lang] = [];
                
            foreach(config("invoice") as $type => $data)
            {
                foreach($data as $key => $value)
                    $toJson[$lang][$type][$key] = $value;
            }
            
            foreach(Dictionary::getAllowedTypes() as $type => $name)
                $toJson[$lang]["dictionaries"][$type] = $name;
        }
        
        $fp = fopen(__DIR__ . "/../../../../app.ninjatask.pl/resources/js/data/values.json", "w");
        fwrite($fp, json_encode($toJson, JSON_PRETTY_PRINT));
        fclose($fp);
    }
}
