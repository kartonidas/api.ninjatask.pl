<?php

namespace App\Console\Commands;

use Collator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Console\Command;
use App\Models\Languages;
use App\Models\Localization;

class GetText extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page:get-text {lang}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get text to translations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $texts = $this->getTextToTranslate();
        $this->saveJSON($texts);
        
        return Command::SUCCESS;
    }
    
    private function saveJSON($texts)
    {
        $lang = $this->argument("lang");
        $jsonFile = app_path() . "/../lang/" . $lang . ".json";
        
        $jsonArray = [];
        if(file_exists($jsonFile))
            $jsonArray = json_decode(file_get_contents($jsonFile), true);
            
        foreach($texts as $text)
        {
            if(!array_key_exists($text, $jsonArray))
                $jsonArray[$text] = $text;
        }
        
        $fp = fopen($jsonFile, "w");
        fwrite($fp, json_encode($jsonArray, JSON_PRETTY_PRINT));
        fclose($fp);
    }
    
    
    private function getTextToTranslate()
    {
        $extensions = ["php", "js"];
        $directiories = [
            base_path() . "/app/Http/Controllers",
            base_path() . "/resources/views",
            base_path() . "/app/Models",
        ];
        
        $allTexts = [];
        foreach($directiories as $path)
        {
            foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS )) as $file)
            {
                if($file->isFile())
                {
                    if(in_array($file->getExtension(), $extensions))
                    {
                        $blade = false;
                        if(substr($file->getPathname(), -10) == ".blade.php")
                            $blade = true;
                            
                        if($file = fopen($file->getPathname(), "r"))
                        {
                            
                            while(!feof($file)) {
                                $line = fgets($file);
                                if($blade)
                                {
                                    preg_match_all('/{{([^}}]*)}}/i', $line, $matches);
                                    if(!empty($matches[1]))
                                    {
                                        foreach($matches[1] as $match)
                                        {
                                            if(strpos($match, "__") !== false)
                                            {
                                                preg_match_all('/__\([(\s+?)?]*[\'"](.*)[\'"][(\s+?)?]*\)/i', trim($match), $texts);
                                                if(isset($texts[1]))
                                                    $allTexts[] = $texts[1][0];
                                            }
                                        }
                                    }
                                }
                                else
                                {
                                    preg_match_all('/__\([(\s+?)?]*[\'"](.*)[\'"][(\s+?)?]*\)/i', $line, $texts);
                                    if(isset($texts[1][0]))
                                        $allTexts[] = $texts[1][0];
                                }
                            }
                            fclose($file);
                        }
                    }
                }
            }
        }
        
        $allTexts = array_unique($allTexts);
        //$collator = new Collator("pl");
        //$collator->sort($allTexts);
        return $allTexts;
    }
}
