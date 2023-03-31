<?php

namespace App\Models;

use Exception;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use App\Exceptions\Unauthorized;
use App\Mail\Register\InitMessage;
use App\Mail\Register\WelcomeMessage;
use App\Mail\User\ForgotPasswordMessage;
use App\Models\Firm;
use App\Models\UserRegisterToken;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    public function canDelete($exception = false)
    {
        if($this->owner)
        {
            if($exception)
                throw new Exception(__("Cannot deleted owner account"));
            return false;
        }
        
        return true;
    }
    
    public function delete()
    {
        $this->canDelete(true);
        
        $this->deleted = 1;
        $this->save();
    }
    
    public function sendPasswordResetNotification($token): void
    {
        $url = 'https://example.com/reset-password?token=' . $token;
        Mail::to($this->email)->send(new ForgotPasswordMessage($url));
    }
    
    private static $firm = null;
    
    public function scopeActive(Builder $query): void
    {
        $query->where("activated", 1)->where("deleted", 0);
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "firstname", "lastname", "phone", "email", "activated", "owner");
    }
    
    public function scopeNoDelete(Builder $query): void
    {
        $query->where("deleted", 0);
    }
    
    public function generateRegisterToken()
    {
        $token = new UserRegisterToken;
        $token->user_id = $this->id;
        $token->token = Str::random(20) . ":" . Str::uuid()->toString();
        $token->save();
        
        return $token->token;
    }
    
    public function sendInitMessage($token)
    {
        Mail::to($this->email)->send(new InitMessage($this, $token));
    }
    
    public function sendWelcomeMessage()
    {
        Mail::to($this->email)->send(new WelcomeMessage($this));
    }
    
    public function confirm()
    {
        $this->activated = 1;
        $this->email_verified_at = date("Y-m-d H:i:s");
        $this->save();
        
        UserRegisterToken::where("user_id", $this->id)->delete();
        $this->sendWelcomeMessage();
    }
    
    public function ensureFirm($identifier)
    {
        if($this->owner)
        {
            if(!Firm::where("uuid", $this->firm_uuid)->count())
            {
                $firm = new Firm;
                $firm->uuid = Str::uuid()->toString();
                $firm->identifier = $identifier;
                $firm->save();
                
                $this->firm_id = $firm->id;
                $this->save();
            }
        }
    }
    
    public function getFirm()
    {
        if(!static::$firm)
        {
            $firm = Firm::find($this->firm_id);
            if($firm)
                static::$firm = $firm;
        }
        
        if(!static::$firm)
            throw new Unauthorized("Unauthorized.");
        
        return static::$firm;
    }
    
    public function getUuid()
    {
        $firm = $this->getFirm();
        return $firm->uuid;
    }
}
