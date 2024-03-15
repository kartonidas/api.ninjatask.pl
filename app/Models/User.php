<?php

namespace App\Models;

use DateTime;
use DateInterval;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use App\Exceptions\AccessDenied;
use App\Exceptions\Exception;
use App\Exceptions\ObjectNotExist;
use App\Exceptions\Unauthorized;
use App\Mail\Register\InitMessage;
use App\Mail\Register\WelcomeMessage;
use App\Mail\User\ForgotPasswordMessage;
use App\Models\Firm;
use App\Models\PasswordResetToken;
use App\Models\Status;
use App\Models\TaskAssignedUser;
use App\Models\UserPermission;
use App\Models\UserRegisterToken;
use App\Models\UserSetting;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

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
    
    public function delete($force = false)
    {
        if(!$force)
            $this->canDelete(true);
            
        return parent::delete();
    }
    
    public function userForgotenPassword()
    {
        $key = config("app.key");
        if(str_starts_with($key, "base64:"))
            $key = base64_decode(substr($key, 7));
        $token = hash_hmac("sha256", Str::random(40), $key);
        
        PasswordResetToken::where("firm_id", $this->firm_id)->where("email", $this->email)->delete();
        
        $tokenRow = new PasswordResetToken;
        $tokenRow->firm_id = $this->firm_id;
        $tokenRow->email = $this->email;
        $tokenRow->token = $token;
        $tokenRow->save();
        
        $url = env("FRONTEND_URL") . app()->getLocale() . "/reset-password?token=" . $token . "&email=" . $this->email;
        Mail::to($this->email)->locale(app()->getLocale())->queue(new ForgotPasswordMessage($url));
    }
    
    public static function userTokenResetPassword($data)
    {
        $resetToken = PasswordResetToken::where("email", $data["email"])->where("token", $data["token"])->first();
        if(!$resetToken)
            throw new Exception(__("Invalid reset token"));
        
        $user = self::where("email", $data["email"])->where("firm_id", $resetToken->firm_id)->active()->first();
        if(!$user)
            throw new ObjectNotExist(__("User not exist"));
        
        $user->password = Hash::make($data["password"]);
        $user->save();
        
        PasswordResetToken::where("firm_id", $resetToken->firm_id)->where("email", $resetToken->email)->delete();
        return true;
    }
    
    public function scopeActive(Builder $query): void
    {
        $query->where("activated", 1)->where("deleted", 0);
    }
    
    public function scopeByFirm(Builder $query): void
    {
        $firm = Auth::user()->getFirm();
        $query->where("firm_id", $firm->id);
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "firstname", "lastname", "phone", "email", "activated", "owner", "superuser", "user_permission_id", "default_locale", "avatar");
    }
    
    public function scopeNoDelete(Builder $query): void
    {
        $query->where("deleted", 0);
    }
    
    public function generateRegisterToken()
    {
        $date = new DateTime();
        $date->add(new DateInterval("PT1H"));
        
        $token = new UserRegisterToken;
        $token->user_id = $this->id;
        $token->token = Str::random(20) . ":" . Str::uuid()->toString();
        $token->code = self::generateUniqueCode();
        $token->code_expired_at = $date->format("Y-m-d H:i:s");
        $token->save();
        
        return $token;
    }
    
    private static function generateUniqueCode()
    {
        $code = strtoupper(Str::random(6));
        if(UserRegisterToken::where("code", $code)->count())
            return self::generateUniqueCode();
        return $code;
    }
    
    public function sendInitMessage($token, $source = "www")
    {
        Mail::to($this->email)->locale(app()->getLocale())->queue(new InitMessage($this, $token, $source));
    }
    
    public function sendWelcomeMessage()
    {
        Mail::to($this->email)->locale(app()->getLocale())->queue(new WelcomeMessage($this));
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
                $firm->firstname = $this->firstname;
                $firm->lastname = $this->lastname;
                $firm->email = $this->email;
                $firm->phone = $this->phone;
                $firm->name = $identifier;
                $firm->save();
                
                $this->firm_id = $firm->id;
                $this->save();
            }
        }
    }
    
    public function getFirm()
    {
        $firm = Firm::find($this->firm_id);
        if(!$firm)
            throw new Unauthorized("Unauthorized.");
        
        return $firm;
    }
    
    public function getUuid()
    {
        $firm = $this->getFirm();
        return $firm->uuid;
    }
    
    public function getUserPermissions()
    {
        if($this->owner || $this->superuser)
            return "all";
        
        $permission = UserPermission::find($this->user_permission_id);
        if(!$permission)
            throw new ObjectNotExist(__("Invalid user permission."));
        
        return $permission->getPermission();
    }
    
    public static function checkAccess($perm, $eception = true)
	{
        $permission = Auth::user()->getUserPermissions();
        if($permission == "all")
            return true;
        
        list($object, $action) = explode(":", $perm);
        
        if(isset($permission[$object]) && in_array($action, $permission[$object]))
            return true;
            
		if($eception)
			throw new AccessDenied(__("Access denied"));

		return false;
	}
    
    public function prepareAccount()
    {
        $permissions = [];
        foreach(config("permissions.permission") as $object => $row)
            $permissions[] = $object . ":list";
            
        $defaultPermissions = new UserPermission;
        $defaultPermissions->withoutGlobalScopes();
        $defaultPermissions->uuid = $this->getUuid();
        $defaultPermissions->name = "Read only";
        $defaultPermissions->is_default = 1;
        $defaultPermissions->permissions = implode(";", $permissions);
        $defaultPermissions->saveQuietly();
        
        Status::createDefaultStatuses($this->getUuid());
        $this->ensureAccountSettings();
    }
    
    public function getAllUserPermissions($uuid = null, $appReady = false)
    {
        $out = [];
        if($this->owner || $this->superuser)
        {
            foreach(config("permissions.permission") as $module => $permission)
                $out[$module] = $permission["operation"];
                
            if($this->owner)
                $out["invite"] = ["create"];
        }
        else
        {
            if($uuid)
                $permission = UserPermission::where("uuid", $uuid)->withoutGlobalScopes()->find($this->user_permission_id);
            else
                $permission = UserPermission::find($this->user_permission_id);
            if($permission)
                $out = $permission->getPermission();
        }
        
        if($appReady)
        {
            $outApp = [];
            foreach($out as $module => $permissions)
                $outApp[$module] = $permissions;
            return $outApp;
        }
        
        return ["permission" => $out];
    }
    
    public function ensureAccountSettings()
    {
        $row = UserSetting::where("user_id", $this->id)->first();
        if(!$row)
        {
            $row = new UserSetting;
            $row->user_id = $this->id;
            $row->locale = $this->default_locale;
            $row->notifications = implode(",", config("api.notifications_default"));
            $row->mobile_notifications = implode(",", config("api.mobile_notifications_default"));
            $row->save();
        }
        
        return $row;
    }
    
    public function getAccountSettings()
    {
        $settings = UserSetting::apiFields()->where("user_id", $this->id)->first();
        
        if(!$settings)
        {
            $settings = UserSetting::getDafaultValues();
            $settings->locale = $this->default_locale;
        }
            
        $settings->notifications = explode(",", $settings->notifications);
        $settings->mobile_notifications = explode(",", $settings->mobile_notifications);
            
        return $settings;
    }
    
    public function getLocale()
    {
        $settings = $this->getAccountSettings();
        return !empty($settings->locale) ? $settings->locale : config("api.default_language");
    }
    
    public function getUserAvatar($uuid = null)
    {
        if($this->avatar)
        {
            $avatar = File::getUploadDirectory("avatar", true, $uuid) . "/" . $this->avatar;
            if(file_exists($avatar))
            {
                $type = pathinfo($avatar, PATHINFO_EXTENSION);
                $data = file_get_contents($avatar);
                return "data:image/" . $type . ";base64," . base64_encode($data);
            }
        }

        return false;
    }
    
    /*
     * 1. usunięcie tokenów z tabeli personal_access_tokens
     * 2. jeśli właściciel usunięcie wszystkich powiązanych kont + tokenów z personal_access_tokens
     * 3. jeśli konto (owner=1) zostanie usunięte jako oznaczone, po roku czasu można skasować wszystkie powiązane dane
     */
    public function removeAccount()
    {
        if($this->owner)
        {
            $firm = $this->getFirm();
            if($firm)
            {
                $users = User::where("firm_id", $firm->id)->get();
                if(!$users->isEmpty())
                {
                    foreach($users as $user)
                    {
                        $user->delete(true);
                        
                        $sdo = new SoftDeletedObject;
                        $sdo->source = "firm";
                        $sdo->source_id = $firm->id;
                        $sdo->object = "user";
                        $sdo->object_id = $user->id;
                        $sdo->save();
                        
                        PersonalAccessToken::where("tokenable_id", $user->id)->delete();
                    }
                }
                $firm->delete();
            }
        }
        else
        {
            $this->delete();
            PersonalAccessToken::where("tokenable_id", $this->id)->delete();
        }
    }
    
    public function getAssignedTaskIds()
    {
        return TaskAssignedUser::where("user_id", $this->id)->pluck("task_id")->all();
    }
}
