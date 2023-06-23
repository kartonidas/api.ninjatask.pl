<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Firm extends Model
{
    use SoftDeletes;
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("identifier", "firstname", "lastname", "email", "nip", "name", "street", "house_no", "apartment_no", "city", "zip", "country", "phone");
    }
    
    public function getOwner()
    {
        return User::where("firm_id", $this->id)->where("owner", 1)->first();
    }
    
    public static function getOwnerByUuid($uuid)
    {
        $firm = Firm::where("uuid", $uuid)->first();
        if($firm)
        {
            $user = User::where("firm_id", $firm->id)->where("owner", 1)->first();
            if($user)
                return $user;
        }
    }
}