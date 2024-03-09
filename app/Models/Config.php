<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Config extends Model
{
	use \App\Traits\UuidTrait {
        boot as traitBoot;
    }

    protected $table = "config";
    public $timestamps = false;

	public static function saveConfig($group, $key, $val, $uuid = null)
	{
        if(!$uuid)
            $row = self::where("group", $group)->where("key", $key)->first();
        else
            $row = self::withoutGlobalScope("uuid")->where("uuid", $uuid)->where("group", $group)->where("key", $key)->first();
        if(!$row)
        {
            $row = new self;
            $row->group = $group;
            $row->key = $key;
            
            if($uuid)
                $row->uuid = $uuid;
        }
        $row->content = $val;
        $row->save();
    }

    public static function getConfig($group, $uuid = null)
    {
        $out = [];
        
        if($uuid !== null)
            $rows = self::where("uuid", $uuid)->where("group", $group)->withoutGlobalScopes()->get();
        else
            $rows = self::where("group", $group)->get();
            
        if(!$rows->isEmpty())
        {
            foreach($rows as $row)
                $out[$row->key] = $row->content;
        }

        return $out;
    }
    
    public static function createDefaultConfiguration(User $user)
    {
        self::saveConfig("basic", "rental_numbering_mask", config("params.default_mask.rental.mask"), $user->getUuid());
        self::saveConfig("basic", "rental_numbering_continuation", config("params.default_mask.rental.continuation"), $user->getUuid());
    }
}
