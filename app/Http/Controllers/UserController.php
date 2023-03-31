<?php

namespace App\Http\Controllers;

use Exception;
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
use App\Exceptions\ObjectNotExist;
use App\Exceptions\UserExist;
use App\Models\User;
use App\Models\UserInvitation;
use App\Models\UserPermission;

class UserController extends Controller
{
    /**
    * Get token
    *
    * Return auth bearer Token.
    * @bodyParam email string required Account e-mail address
    * @bodyParam password string required Account password
    * @bodyParam device_name string required Device name
    * @responseField token string Auth token
    * @response 422 {"error":true,"message":"The provided credentials are incorrect.","errors":{"email":["The provided credentials are incorrect."]}}
    * @group User registation
    */
    public function login(Request $request)
    {
        $request->validate([
            "email" => "required|email",
            "password" => "required",
            "device_name" => "required",
        ]);
        
        $user = User::where("email", $request->email)->active()->first();
 
        if(!$user || !Hash::check($request->password, $user->password))
        {
            throw ValidationException::withMessages([
                "email" => [__("The provided credentials are incorrect.")],
            ]);
        }
        
        return $user->createToken($request->device_name)->plainTextToken;
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
    * @responseField status boolean Status
    * @response 404 {"error":true,"message":"User does not exist"}
    * @group User registation
    */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            "email" => "required|email",
        ]);
        
        $user = User::where("email", $request->email)->active()->first();
        if(!$user)
            throw new ObjectNotExist(__("User does not exist."));
        
        $status = Password::sendResetLink(
            $request->only("email")
        );
        
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
            "password" => "required|min:8|confirmed",
        ]);
        
        $status = Password::reset(
            $request->only("email", "password", "password_confirmation", "token"),
            function (User $user, string $password) {
                $user->forceFill([
                    "password" => Hash::make($password)
                ])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );
        
        if($status === Password::PASSWORD_RESET)
            return true;
        
        throw ValidationException::withMessages([
            "email" => [__($status)],
        ]);
    }
    
    /**
    * Get users list
    *
    * Return users account list.
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": 1, "firstname": "John", "lastname": "Doe", "phone": 123456789, "email": "john@doe.com", "activated": 1, "owner": 0, "superuser": 0, "user_permission_id": 1}]}
    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function list(Request $request)
    {
        User::checkAccess("user:list");
        
        $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
        ]);
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        
        $firm = Auth::user()->getFirm();
        $users = User
            ::apiFields()
            ->where("firm_id", $firm->id)
            ->noDelete()
            ->take($size)
            ->skip(($page-1)*$size)
            ->orderBy("lastname", "ASC")
            ->orderBy("firstname", "ASC")
            ->get();
            
        $total = User::where("firm_id", $firm->id)->noDelete()->count();
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
    * @bodyParam permission_id integer Permission group identifier (if not set default permission will be used).
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
            "password" => ["required", RulePassword::min(8)->letters()->mixedCase()->numbers()->symbols(), "confirmed"],
            "phone" => "nullable|max:30",
            "permission_id" => ["nullable", Rule::in(UserPermission::getIds())],
            "superuser" => "nullable|boolean",
        ]);
        
        $userByEmail = User::where("firm_id", Auth::user()->getFirm()->id)
            ->where("email", $request->input("email"))
            ->noDelete()
            ->count();
        
        $permissionId = $request->input("permission_id", null);
        if(!$request->has("permission_id"))
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
        $user->save();
        
        return $user->id;
    }
    
    /**
    * Invite user
    *
    * Send invitation to the email address provided.
    * @bodyParam email string required User e-mail address.
    * @bodyParam permission_id integer Permission group identifier (if not set default permission will be used).
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
            "permission_id" => ["nullable", Rule::in(UserPermission::getIds())],
        ]);
        
        $userByEmail = User::where("firm_id", Auth::user()->getFirm()->id)
            ->where("email", $request->input("email"))
            ->noDelete()
            ->count();
            
        if($userByEmail)
            throw new UserExist(__("The given e-mail address is already registered"));
        
        $permissionId = $request->input("permission_id", null);
        if(!$request->has("permission_id"))
        {
            $defaultPermissionId = UserPermission::getDefault();
            if($defaultPermissionId)
                $permissionId = $defaultPermissionId;
        }
        
        $invitation = new UserInvitation;
        $invitation->firm_id = Auth::user()->getFirm()->id;
        $invitation->invited_by = Auth::user()->id;
        $invitation->email = $request->input("email");
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
            "password" => ["required", RulePassword::min(8)->letters()->mixedCase()->numbers()->symbols(), "confirmed"],
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
    * @response 200 {"id": 1, "firstname": "John", "lastname": "Doe", "phone": 123456789, "email": "john@doe.com", "activated": 1, "owner": 0, "superuser": 0, "user_permission_id": 1}
    * @response 404 {"error":true,"message":"User does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function get(Request $request, $id)
    {
        User::checkAccess("user:list");
        
        $user = User::noDelete()->apiFields()->find($id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
        
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
    * @bodyParam permission_id integer Permission group identifier.
    * @bodyParam superuser boolean If set true user have full access regardless of permissions.
    * @responseField status boolean Update status
    * @header Authorization: Bearer {TOKEN}
    * @group User management
    */
    public function update(Request $request, $id)
    {
        User::checkAccess("user:update");
        
        $user = User::noDelete()->find($id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
        
        if($request->has("email"))
        {
            $userByEmail = User::where("firm_id", $user->firm_id)
                ->where("email", $request->input("email"))
                ->where("id", "!=", $user->id)
                ->noDelete()
                ->count();
                
            if($userByEmail)
                throw new UserExist(__("The given e-mail address is already registered"));
        }
        
        $rules = [
            "firstname" => "required|max:100",
            "lastname" => "required|max:100",
            "email" => "required|email",
            "password" => ["required", RulePassword::min(8)->letters()->mixedCase()->numbers()->symbols(), "confirmed"],
            "phone" => "nullable|max:30",
            "superuser" => "nullable|boolean",
            "permission_id" => ["nullable", Rule::in(UserPermission::getIds())],
        ];
        
        $validate = [];
        $updateFields = ["firstname", "lastname", "email", "phone", "password", "superuser", "permission_id"];
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
        
        $user = User::find($id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
        
        $user->delete();
        
        return true;
    }
}