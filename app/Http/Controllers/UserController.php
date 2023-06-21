<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as RulePassword;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;
use App\Exceptions\AccessDenied;
use App\Exceptions\Exception;
use App\Exceptions\ObjectNotExist;
use App\Exceptions\UserExist;
use App\Models\Firm;
use App\Models\File;
use App\Models\PasswordResetToken;
use App\Models\Task;
use App\Models\TaskTime;
use App\Models\User;
use App\Models\UserInvitation;
use App\Models\UserPermission;
use App\Rules\Notifications as NotificationsRule;

class UserController extends Controller
{
    /**
    * Get token
    *
    * Return auth bearer Token.
    * @bodyParam email string required Account e-mail address
    * @bodyParam password string required Account password
    * @bodyParam device_name string required Device name
    * @bodyParam firm_id integer Firm identifier (required if e-mail address is register on two or more firms)
    * @response 200 [{"token": "xxxxxxxx", "firstname": "John", "lastname": "Doe", "locale": "pl", "owner": 0}]
    * @response 422 {"error":true,"message":"The provided credentials are incorrect.","errors":{"email":["The provided credentials are incorrect."]}}
    * @group User registation
    */
    public function login(Request $request)
    {
        $rule = [
            "email" => "required|email",
            "password" => "required",
            "device_name" => "required",
        ];
        $requiredFirm = false;
        if(User::where("email", $request->email)->active()->count() > 1)
        {
            $rule["firm_id"] = "required";
            $requiredFirm = true;
        }
        $request->validate($rule);
        
        $user = User::where("email", $request->email);
        if($requiredFirm)
            $user->where("firm_id", $request->input("firm_id"));
        $user = $user->active()->first();
 
        if(!$user || !Hash::check($request->password, $user->password))
        {
            throw ValidationException::withMessages([
                "email" => [__("The provided credentials are incorrect.")],
            ]);
        }
        
        $settings = $user->getAccountSettings();
        
        $out = [
            "token" => $user->createToken($request->device_name)->plainTextToken,
            "firstname" => $user->firstname,
            "lastname" => $user->lastname,
            "locale" => $settings->locale,
            "owner" => $user->owner,
            "avatar" => $user->getUserAvatar($user->getUuid()),
        ];
        
        return response()->json($out);
    }
    
    /**
    * Get email firm ids
    *
    * Get user email assigned firm identifiers
    * Because e-mail addresses may be repeated within other companies, if the e-mail address exists more than once,
    * you must enter the company ID when logging in / remembering the password.
    * @queryParam email string required Account e-mail address
    * @response 200 [{"id": 1, "name": "Example Firm"}]
    * @response 404 {"error":true,"message":"User does not exist"}
    * @group User registation
    */
    public function getUserFirmIds(Request $request)
    {
        $request->validate([
            "email" => "required|email",
        ]);
        
        $users = User::where("email", $request->email)->active()->get();
        if($users->isEmpty())
            throw new ObjectNotExist(__("User does not exist."));
        
        $ids = [];
        foreach($users as $user)
        {
            $firm = Firm::find($user->firm_id);
            $ids[] = [
                "id" => $user->firm_id,
                "name" => $firm ? $firm->identifier : "",
            ];
        }
            
        return response()->json($ids);
    }
    
    /**
    * Logout
    *
    * Logout.
    * @group User registation
    */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
    }
    
    /**
    * Forgot password
    *
    * Send password reset link
    * @bodyParam email string required Account e-mail address
    * @bodyParam firm_id integer Firm identifier (if e-mail address exist in more than once firms)
    * @responseField status boolean Status
    * @response 404 {"error":true,"message":"User does not exist"}
    * @group User registation
    */
    public function forgotPassword(Request $request)
    {
        $rule = [
            "email" => "required|email",
        ];
        
        $requiredFirm = false;
        if(User::where("email", $request->email)->active()->count() > 1)
        {
            $rule["firm_id"] = "required";
            $requiredFirm = true;
        }
        
        $request->validate($rule);
        
        $user = User::where("email", $request->email);
        if($requiredFirm)
            $user->where("firm_id", $request->input("firm_id"));
        $user = $user->active()->first();
        
        if(!$user)
            throw new ObjectNotExist(__("User does not exist."));
        
        $user->userForgotenPassword();
        return true;
    }
    
    /**
    * Reset password
    *
    * Send password reset link
    * @bodyParam token string required forgot password token
    * @bodyParam email string required Account e-mail address
    * @bodyParam password string required User password (min 8 characters, lowercase and uppercase letters, number, special characters).
    * @responseField status boolean Status
    * @group User registation
    */
    public function resetPassword(Request $request)
    {
        $request->validate([
            "token" => "required",
            "email" => "required|email",
            "password" => "required|min:8",
        ]);
        
        return User::userTokenResetPassword($request->only("email", "password", "token"));
    }
    
    /**
    * Validate token reset password
    *
    * Validate token reset password
    * @bodyParam token string required forgot password token
    * @bodyParam email string required Account e-mail address
    * @responseField status boolean Status
    * @group User registation
    */
    public function resetPasswordGet(Request $request)
    {
        $request->validate([
            "token" => "required",
            "email" => "required|email",
        ]);
        
        $resetToken = PasswordResetToken::where("email", $request->input("email"))->where("token", $request->input("token"))->first();
        if(!$resetToken)
            throw new Exception(__("Invalid reset token"));
        
        return true;
    }
    
    /**
    * Get users list
    *
    * Return users account list.
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": 1, "firstname": "John", "lastname": "Doe", "phone": 123456789, "email": "john@doe.com", "activated": 1, "owner": 0, "superuser": 0, "can_delete": 1, "user_permission_id": 1, "user_permission_name": "Permission name"}]}
    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function list(Request $request)
    {
        User::checkAccess("user:list");
        
        $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
            "lastname" => "nullable|max:200",
            "email" => "nullable|max:200",
            "phone" => "nullable|max:200",
            "permission" => ["nullable", Rule::in(array_merge(UserPermission::getIds(), ["owner", "superuser"]))],
        ]);
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        
        $searchLastname = $request->input("lastname", null);
        $searchEmail = $request->input("email", null);
        $searchPhone = $request->input("phone", null);
        $searchPermission = $request->input("permission", null);
        
        $users = User
            ::apiFields()
            ->byFirm();
            
        if($searchLastname)
            $users->where("lastname", "LIKE", "%" . $searchLastname . "%");
        if($searchEmail)
            $users->where("email", "LIKE", "%" . $searchEmail . "%");
        if($searchPhone)
            $users->where("phone", "LIKE", "%" . $searchPhone . "%");
        if($searchPermission)
        {
            if($searchPermission == "owner")
                $users->where("owner", 1);
            elseif($searchPermission == "superuser")
                $users->where("superuser", 1);
            else
                $users->where("user_permission_id", intval($searchPermission));
        }
        $total = $users->count();
            
        $users = $users->take($size)
            ->skip(($page-1)*$size)
            ->orderBy("owner", "DESC")
            ->orderBy("superuser", "DESC")
            ->orderBy("lastname", "ASC")
            ->orderBy("firstname", "ASC")
            ->get();
            
        foreach($users as $k => $user)
        {
            $users[$k]->activated = $user->activated == 1;
            $users[$k]->owner = $user->owner == 1;
            $users[$k]->superuser = $user->superuser == 1;
            $users[$k]->can_delete = $user->canDelete();
            
            $users[$k]->user_permission_name = "";
            if(!$user->superuser && $user->user_permission_id > 0) {
                $userPermission = UserPermission::find($user->user_permission_id);
                if($userPermission)
                    $users[$k]->user_permission_name = $userPermission->name;
            }
            
        }
            
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => ceil($total / $size) > $page,
            "data" => $users,
        ];
            
        return $out;
    }
    
    /**
    * Create user account
    *
    * Create user account. After create account is ready to use.
    * @bodyParam firstname string required User first name.
    * @bodyParam lastname string required User last name.
    * @bodyParam email string required User e-mail address.
    * @bodyParam password string required User password (min 8 characters, lowercase and uppercase letters, number, special characters).
    * @bodyParam phone string User phone number.
    * @bodyParam user_permission_id integer Permission group identifier (if not set default permission will be used).
    * @bodyParam superuser boolean If set true user have full access regardless of permissions.
    * @responseField id integer The id of the newly created user
    * @response 409 {"error":true,"message":"The given e-mail address is already registered"}
    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function create(Request $request)
    {
        User::checkAccess("user:create");
        
        $request->validate([
            "firstname" => "required|max:100",
            "lastname" => "required|max:100",
            "email" => "required|email",
            "password" => ["required", RulePassword::min(8)->letters()->mixedCase()->numbers()->symbols()],
            "phone" => "nullable|max:30",
            "user_permission_id" => ["nullable", Rule::in(UserPermission::getIds())],
            "superuser" => "nullable|boolean",
        ]);
        
        $userByEmail = User::where("firm_id", Auth::user()->getFirm()->id)
            ->where("email", $request->input("email"))
            ->count();
        
        $permissionId = $request->input("user_permission_id", null);
        if(!$request->has("user_permission_id"))
        {
            $defaultPermissionId = UserPermission::getDefault();
            if($defaultPermissionId)
                $permissionId = $defaultPermissionId;
        }
        
        if($userByEmail)
            throw new UserExist(__("The given e-mail address is already registered"));
        
        $user = new User;
        $user->firm_id = Auth::user()->getFirm()->id;
        $user->firstname = $request->input("firstname");
        $user->lastname = $request->input("lastname");
        $user->email = $request->input("email");
        $user->password = Hash::make($request->input("password"));
        $user->phone = $request->input("phone", "");
        $user->owner = 0;
        $user->activated = 1;
        $user->user_permission_id = $permissionId;
        $user->superuser = $request->input("superuser", 0);
        $user->default_locale = app()->getLocale();
        $user->save();
        
        return $user->id;
    }
    
    /**
    * Invite user
    *
    * Send invitation to the email address provided.
    * @bodyParam email string required User e-mail address.
    * @bodyParam user_permission_id integer Permission group identifier (if not set default permission will be used).
    * @responseField status boolean Status
    * @response 409 {"error":true,"message":"The given e-mail address is already registered"}
    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function invite(Request $request)
    {
        if(!Auth::user()->owner)
            throw new Exception(__("Only account owner can send invitations"));
        
        $request->validate([
            "email" => "required|email",
            "user_permission_id" => ["nullable", Rule::in(UserPermission::getIds())],
        ]);
        
        $userByEmail = User::where("firm_id", Auth::user()->getFirm()->id)
            ->where("email", $request->input("email"))
            ->count();
            
        if($userByEmail)
            throw new UserExist(__("The given e-mail address is already registered"));
        
        $permissionId = $request->input("user_permission_id", null);
        if(!$request->has("user_permission_id"))
        {
            $defaultPermissionId = UserPermission::getDefault();
            if($defaultPermissionId)
                $permissionId = $defaultPermissionId;
        }
        
        $invitation = new UserInvitation;
        $invitation->firm_id = Auth::user()->getFirm()->id;
        $invitation->invited_by = Auth::user()->id;
        $invitation->email = $request->input("email");
        $invitation->default_locale = app()->getLocale();
        $invitation->user_permission_id = $permissionId;
        $invitation->save();
        
        return true;
    }
    
    /**
    * Validate invite token
    *
    * Check validate invite token.
    * @urlParam token string required Invite token from invitation message.
    * @response 200 {'email': 'john@doe.com'}
    * @response 404 {"error":true,"message":"The given token is invalid"}
    * @group User management
    */
    public function inviteGet(Request $request, $token)
    {
        $token = UserInvitation::select("email")->where("token", $token)->first();
        if(!$token)
            throw new UserExist(__("The given token is invalid"));
        
        return $token;
    }
    
    /**
    * Confirm invitation
    *
    * Confirm invitation and create new user account.
    * @urlParam token string required Invite token.
    * @bodyParam firstname string required User first name.
    * @bodyParam lastname string required User last name.
    * @bodyParam password string required User password (min 8 characters, lowercase and uppercase letters, number, special characters).
    * @bodyParam phone string User phone number.
    * @responseField id integer The id of the newly created user
    * @response 404 {"error":true,"message":"The given token is invalid"}
    * @group User management
    */
    public function inviteConfirm(Request $request, $token)
    {
        $token = UserInvitation::where("token", $token)->first();
        if(!$token)
            throw new UserExist(__("The given token is invalid"));
        
        $request->validate([
            "firstname" => "required|max:100",
            "lastname" => "required|max:100",
            "password" => ["required", RulePassword::min(8)->letters()->mixedCase()->numbers()->symbols()],
            "phone" => "nullable|max:30",
        ]);
        
        $user = new User;
        $user->firm_id = $token->firm_id;
        $user->firstname = $request->input("firstname");
        $user->lastname = $request->input("lastname");
        $user->email = $token->email;
        $user->password = Hash::make($request->input("password"));
        $user->phone = $request->input("phone", "");
        $user->owner = 0;
        $user->activated = 1;
        $user->user_permission_id = $token->user_permission_id;
        $user->email_verified_at = date("Y-m-d H:i:s");
        $user->default_locale = app()->getLocale();
        $user->save();
        $user->sendWelcomeMessage();
        
        UserInvitation::where("firm_id", $user->firm_id)->where("email", $user->email)->delete();
        
        return $user->id;
    }
    
    /**
    * Get user account data
    *
    * Return user account data.
    * @urlParam id integer required User identifier.
    * @response 200 {"id": 1, "firstname": "John", "lastname": "Doe", "phone": 123456789, "email": "john@doe.com", "activated": 1, "owner": 0, "superuser": 0, "can_delete": 1, "user_permission_id": 1, "user_permission_name": "Permission name"}
    * @response 404 {"error":true,"message":"User does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function get(Request $request, $id)
    {
        User::checkAccess("user:list");
        
        $user = User::byFirm()->apiFields()->find($id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
        
        
        $user->activated = $user->activated == 1;
        $user->owner = $user->owner == 1;
        $user->superuser = $user->superuser == 1;
        $user->can_delete = $user->canDelete();
        
        $user->user_permission_name = "";
        if(!$user->superuser && $user->user_permission_id > 0) {
            $userPermission = UserPermission::find($user->user_permission_id);
            if($userPermission)
                $user->user_permission_name = $userPermission->name;
        }
        
        return $user;
    }
    
    /**
    * Update user account data
    *
    * Update user account data.
    * @urlParam id integer required User identifier.
    * @bodyParam firstname string User first name.
    * @bodyParam lastname string User last name.
    * @bodyParam email string User e-mail address.
    * @bodyParam password string User password (min 8 characters, lowercase and uppercase letters, number, special characters).
    * @bodyParam phone string User phone number.
    * @bodyParam user_permission_id integer Permission group identifier.
    * @bodyParam superuser boolean If set true user have full access regardless of permissions.
    * @responseField status boolean Update status
    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function update(Request $request, $id)
    {
        User::checkAccess("user:update");
        
        $user = User::byFirm()->find($id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
        
        if($request->has("email"))
        {
            $userByEmail = User::where("firm_id", $user->firm_id)
                ->where("email", $request->input("email"))
                ->where("id", "!=", $user->id)
                ->count();
                
            if($userByEmail)
                throw new UserExist(__("The given e-mail address is already registered"));
        }
        
        $rules = [
            "firstname" => "required|max:100",
            "lastname" => "required|max:100",
            "email" => "required|email",
            "password" => ["required", RulePassword::min(8)->letters()->mixedCase()->numbers()->symbols()],
            "phone" => "nullable|max:30",
            "superuser" => "nullable|boolean",
            "user_permission_id" => ["nullable", Rule::in(UserPermission::getIds())],
        ];
        
        $validate = [];
        $updateFields = ["firstname", "lastname", "email", "phone", "password", "superuser", "user_permission_id"];
        foreach($updateFields as $field)
        {
            if($request->has($field))
            {
                if(!empty($rules[$field]))
                    $validate[$field] = $rules[$field];
            }
        }
        
        if(!empty($validate))
            $request->validate($validate);
        
        foreach($updateFields as $field)
        {
            if($request->has($field))
                $user->{$field} = $request->input($field);
        }
        $user->save();
            
        return true;
    }
    
    /**
    * Delete user account
    *
    * Delete user account.
    * @urlParam id integer required User identifier.
    * @responseField status boolean Delete status
    * @response 404 {"error":true,"message":"User does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function delete(Request $request, $id)
    {
        User::checkAccess("user:delete");
        
        $user = User::byFirm()->find($id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
        
        $user->delete();
        
        return true;
    }
    
    /**
    * User profile
    *
    * User profile.
    * @response 200 {"id": 1, "firstname": "John", "lastname": "Doe", "phone": 123456789, "email": "john@doe.com", "activated": 1, "owner": 0, "superuser": 0, "user_permission_id": 1, "user_permission_name": "Permission name"}
    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function profile(Request $request)
    {
        $user = User::byFirm()->apiFields()->find(Auth::user()->id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
        
        $user->activated = $user->activated == 1;
        $user->owner = $user->owner == 1;
        $user->superuser = $user->superuser == 1;
        $user->avatar = $user->getUserAvatar();
        
        $user->user_permission_name = "";
        if(!$user->superuser && $user->user_permission_id > 0) {
            $userPermission = UserPermission::find($user->user_permission_id);
            if($userPermission)
                $user->user_permission_name = $userPermission->name;
        }
        
        return $user;
    }
    
    /**
    * Update user profile
    *
    * User profile.
    * @bodyParam firstname string User first name.
    * @bodyParam lastname string User last name.
    * @bodyParam email string User e-mail address.
    * @bodyParam password string User password (min 8 characters, lowercase and uppercase letters, number, special characters).
    * @bodyParam phone string User phone number.
    * @responseField status boolean Update status

    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function profileUpdate(Request $request)
    {
        $user = User::byFirm()->apiFields()->find(Auth::user()->id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
        
        if($request->has("email"))
        {
            $userByEmail = User::where("firm_id", $user->firm_id)
                ->where("email", $request->input("email"))
                ->where("id", "!=", $user->id)
                ->count();
                
            if($userByEmail)
                throw new UserExist(__("The given e-mail address is already registered"));
        }
        
        $rules = [
            "firstname" => "required|max:100",
            "lastname" => "required|max:100",
            "email" => "required|email",
            "password" => ["required", RulePassword::min(8)->letters()->mixedCase()->numbers()->symbols()],
            "phone" => "nullable|max:30",
        ];
        
        $validate = [];
        $updateFields = ["firstname", "lastname", "email", "phone", "password"];
        foreach($updateFields as $field)
        {
            if($request->has($field))
            {
                if(!empty($rules[$field]))
                    $validate[$field] = $rules[$field];
            }
        }
        
        if(!empty($validate))
            $request->validate($validate);
        
        foreach($updateFields as $field)
        {
            if($request->has($field))
                $user->{$field} = $field == "password" ? Hash::make($request->input($field)) : $request->input($field);
        }
        $user->save();
            
        return true;
    }
    
    /**
    * Update user profile avatar
    *
    * User profile avatar.
    * @bodyParam file file avatar file.
    * @responseField status boolean Update status

    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function profileAvatarUpdate(Request $request)
    {
        $user = User::byFirm()->apiFields()->find(Auth::user()->id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
        
        $request->validate([
            "avatar" => "nullable|file|mimes:jpg,png|max:100"
        ]);
        
        if($request->has("avatar"))
        {
            if($user->avatar && file_exists(File::getUploadDirectory("avatar") . "/" . $user->avatar))
                unlink(File::getUploadDirectory("avatar") . "/" . $user->avatar);
                
            $relativeDirectory = File::getUploadDirectory("avatar", false);
            $extension = $request->file("avatar")->getClientOriginalExtension();
            $filename = bin2hex(openssl_random_pseudo_bytes(16)) . "." . $extension;
            $path = $request->file("avatar")->storeAs($relativeDirectory, $filename, "upload");
            
            
            if(file_exists(File::getUploadDirectory("avatar") . "/" . $filename))
            {
                $img = Image::make(File::getUploadDirectory("avatar") . "/" . $filename);
                $img->resize(100, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $img->save(File::getUploadDirectory("avatar") . "/" . $filename);
            }
            
            $user->avatar = $filename;
            $user->save();
        }
        else
        {
            if($user->avatar && file_exists(File::getUploadDirectory("avatar") . "/" . $user->avatar))
                unlink(File::getUploadDirectory("avatar") . "/" . $user->avatar);
                
            $user->avatar = null;
            $user->save();
        }
        
        return [
            "avatar" => $user->getUserAvatar(),
        ];
    }
    
    /**
    * User settings
    *
    * User settings.
    * @response 200 {"locale": "pl"}
    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function settings(Request $request)
    {
        $user = User::byFirm()->apiFields()->find(Auth::user()->id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
        
        return $user->getAccountSettings();
    }
    
    /**
    * Update user settings
    *
    * User settings.
    * @bodyParam locale string Locale ex. pl.
    * @bodyParam notifications string User e-mail notifications (separated by a comma)
    * @responseField status boolean Update status

    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function settingsUpdate(Request $request)
    {
        $user = User::byFirm()->apiFields()->find(Auth::user()->id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
        
        $rules = [
            "locale" => ["required", Rule::in(config("api.allowed_languages"))],
            "notifications" => ["nullable", new NotificationsRule],
        ];
        
        $validate = [];
        $updateFields = ["locale", "notifications"];
        foreach($updateFields as $field)
        {
            if($request->has($field))
            {
                if(!empty($rules[$field]))
                    $validate[$field] = $rules[$field];
            }
        }
        
        if(!empty($validate))
            $request->validate($validate);
        
        $settings = $user->ensureAccountSettings();
        foreach($updateFields as $field)
        {
            if($request->has($field))
                $settings->{$field} = $request->input($field);
        }
        $settings->save();
    }
    
    /**
    * Get active timer
    *
    * Get user active timer list.
    * @responseField status boolean Update status
    * @response 200 {"total": 100, "data": [{"id": 1, "task_id": 12, "status": "active", "total": 1, "task": "Task name"}]}
    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function getActiveTimer(Request $request)
    {
        $timer = TaskTime::where("user_id", Auth::user()->id)->whereIn("status", [TaskTime::ACTIVE, TaskTime::PAUSED])->orderBy("created_at", "DESC")->get();
        
        $out = [];
        if(!$timer->isEmpty())
        {
            $data = [];
            foreach($timer as $time)
            {
                $task = Task::find($time->task_id);
                if(!$task)
                    continue;
                
                $total = $time->total;
                if($time->status == TaskTime::ACTIVE)
                    $total = $time->total + (time() - $time->timer_started);
                
                $data[] = [
                    "id" => $time->id,
                    "task_id" => $time->task_id,
                    "status" => $time->status,
                    "total" => $total,
                    "task" => $task->name,
                ];
            }
            $out["total"] = count($data);
            $out["data"] = $data;
        }
        
        return $out;
    }
    
    /**
    * Get login user firm identifier
    *
    * Get login user firm identifier.
    * @responseField status integer Firm identifier
    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function getFirmId()
    {
        return Auth::user()->firm_id;
    }
    
    /**
    * Get login user identifier
    *
    * Get login user identifier.
    * @responseField status integer User identifier
    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function getId()
    {
        return Auth::user()->id;
    }
    
    /**
    * Get logged user permissions
    *
    * Get logged user permissions
    * @header Authorization: Bearer {TOKEN}
    * @response 200 {"permission":{"project":["list","create","update","delete"],"task":["list","create","update","delete"],"user":["list","create","update","delete"],"permission":["list","create","update","delete"]}}
    * @group User management
    */
    public function getPermissions()
    {
        return Auth::user()->getAllUserPermissions();
    }
    
    /**
    * Get firm data
    *
    * Get firm data
    * @header Authorization: Bearer {TOKEN}
    * @response 200 {"identifier": "Firm ID", "firstname": "John", "lastname": "Doe", "email": "example@com.pl", "nip": "0123456789", "name": "Firm name", "street": "Street name", "house_no": "12", "apartment_no": "1A", "city": "London", "zip": "91-000", "country_id": 123, "phone": "888777666"}
    * @group User management
    */
    public function getFirmData()
    {
        return Firm::apiFields()->where("id", Auth::user()->firm_id)->first();
    }
    
    /**
    * Update firm data
    *
    * Update firm data
    * @bodyParam firstname string Owner first name.
    * @bodyParam lastname string Owner last name.
    * @bodyParam email string Firm e-mail address.
    * @bodyParam nip string NIP.
    * @bodyParam name string Firm name.
    * @bodyParam street string Firm street.
    * @bodyParam house_no string Firm house no.
    * @bodyParam apartment_no string Firm apartment no.
    * @bodyParam city string Firm city.
    * @bodyParam zip string Firm zip.
    * @bodyParam country_id integer Firm country identifier.
    * @bodyParam phone string Firm phone.
    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function firmDataUpdate(Request $request)
    {
        if(!Auth::user()->owner)
            throw new AccessDenied(__("Access denied"));
        
        $firm = Auth::user()->getFirm();
        
        $rules = [
            "firstname" => "required|max:100",
            "lastname" => "required|max:100",
            "email" => "required|email",
            "nip" => ["nullable", new \App\Rules\Nip],
            "name" => "nullable|max:200",
            "street" => "required|max:80",
            "house_no" => "required|max:20",
            "apartment_no" => "nullable|max:20",
            "city" => "required|max:120",
            "zip" => "required|max:10",
            "country_id" => "nullable|integer",
            "phone" => "nullable|max:50",
        ];
        
        $validate = [];
        $updateFields = ["firstname", "lastname", "email", "nip", "name", "street", "house_no", "apartment_no", "city", "zip", "country_id", "phone"];
        foreach($updateFields as $field)
        {
            if($request->has($field))
            {
                if(!empty($rules[$field]))
                    $validate[$field] = $rules[$field];
            }
        }
        
        if(!empty($validate))
            $request->validate($validate);
        
        foreach($updateFields as $field)
        {
            if($request->has($field))
                $firm->{$field} = $request->input($field);
        }
        $firm->save();
            
        return true;
    }
    
    /**
    * Check user is login
    *
    * Check user is login
    * @response 200 [{"firstname": "John", "lastname": "Doe", "locale": "pl", "owner": 0}]
    * @response 422 {"error":true,"message":"The provided credentials are incorrect.","errors":{"email":["The provided credentials are incorrect."]}}
    *
    * @group User registation
    */
    public function isLogin(Request $request)
    {
        $settings = Auth::user()->getAccountSettings();
        
        $out = [
            "firstname" => Auth::user()->firstname,
            "lastname" => Auth::user()->lastname,
            "locale" => $settings->locale,
            "owner" => Auth::user()->owner,
            "avatar" => Auth::user()->getUserAvatar(),
        ];
        return $out;
    }
}