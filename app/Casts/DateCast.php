<?php
 
namespace App\Casts;
 
use App\ValueObjects\Address as AddressValueObject;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
 
class DateCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        return substr($attributes["created_at"], 0, -3);
    }
 
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return $value;
    }
}