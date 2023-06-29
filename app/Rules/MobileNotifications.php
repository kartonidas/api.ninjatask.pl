<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MobileNotifications implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $allowedNotifications = config("api.mobile_notifications");
        
        $notifications = explode(",", $value);
        if($notifications)
        {
            foreach($notifications as $notification)
            {
                if(!in_array($notification, $allowedNotifications))
                {
                    $fail(sprintf(__("Notification '%s' not exist."), $notification));
                    continue;
                }
            }
        }
    }
}
