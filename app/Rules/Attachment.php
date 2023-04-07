<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Attachment implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $allowedMimeTypes = config("api.upload.allowed_mime_types");
        foreach($value as $attachment)
        {
            $attachment = json_decode($attachment);
            if(empty($attachment->name) || empty($attachment->base64))
            {
                if(empty($attachment->name))
                    $fail(__("The attachment name field is required"));
                if(empty($attachment->base64))
                    $fail(__("The attachment base64 field is required"));
            }
            else
            {
                $f = finfo_open();
                $mime = finfo_buffer($f, base64_decode($attachment->base64), FILEINFO_MIME_TYPE);
                if(!$mime || !isset($allowedMimeTypes[$mime]))
                    $fail(sprintf(__("Unsupported file type for file '%s'"), $attachment->name));
                
            }
        }
    }
}
