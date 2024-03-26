<?php

namespace App\Observers;

use App\Models\SmsPackage;
use App\Models\SmsPackageHistory;

class SmsPackageObserver
{
    public function created(SmsPackage $row): void
    {
        $history = new SmsPackageHistory;
        $history->sms_package_id = $row->id;
        $history->operation = SmsPackageHistory::OPERATION_ADD;
        $history->quantity = $row->allowed;
        $history->save();
    }
    
    public function updating(SmsPackage $row): void
    {
        if($row->allowed <= 0)
            $row->status = SmsPackage::STATUS_USED;
    }
}