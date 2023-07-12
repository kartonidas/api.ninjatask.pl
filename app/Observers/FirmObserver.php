<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use App\Jobs\LimitsCalculate;
use App\Models\Firm;
use App\Models\User;
use App\Models\SoftDeletedObject;

class FirmObserver
{
    function restored(Firm $firm): void
    {
        $userToRestored = SoftDeletedObject
            ::where("source", "firm")
            ->where("source_id", $firm->id)
            ->where("object", "user")
            ->get();
            
        if(!$userToRestored->isEmpty())
        {
            foreach($userToRestored as $userToRestore)
            {
                $user = User::withoutGlobalScopes()->onlyTrashed()->where("id", $userToRestore->object_id)->first();
                if($user)
                    $user->restore();
                    
                $userToRestore->delete();
            }
        }
    }
}
