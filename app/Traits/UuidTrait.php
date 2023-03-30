<?php

namespace App\Traits;
use Illuminate\Support\Facades\Auth;

trait UuidTrait
{
    public static function boot()
    {
        parent::boot();

        self::creating(function($row) {
            $row->uuid = Auth::user()->getUuid();
        });

        static::addGlobalScope(function ($query) {
            $query->where((new static)->getTable() . ".uuid", Auth::user()->getUuid());
        });
    }
}