<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use App\Jobs\LimitsCalculate;
use App\Models\File;

class FileObserver
{
    public function created(File $file): void
    {
        LimitsCalculate::dispatch($file->uuid);
    }
    
    public function deleted(File $file): void
    {
        LimitsCalculate::dispatch($file->uuid);
    }
}
