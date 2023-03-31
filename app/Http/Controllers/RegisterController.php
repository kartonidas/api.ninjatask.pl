<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use App\Exceptions\ObjectNotExist;
use App\Models\User;
use App\Models\UserRegisterToken;

class RegisterController extends Controller
{
    /**
    * Register new account
    *
    * Create new user account. After registration, a confirmation link is sent to the provided e-mail address.
    * @bodyParam email string required The email address. Example: john@doe.com
    * @response 200 true
    * @response 422 {"error":true,"message":"The provided email is already registered.","errors":{"email":["The provided email is already registered."]}}
    * @group User registation
    */
    public function register(Request $request)
    {
        $request->validate([
            "email" => "required|email",
        ]);
        
        $user = User::where("email", $request->email)->where("activated", 1)->where("owner", 1)->first();
        if($user)
        {
            throw ValidationException::withMessages([
                "email" => [__("The provided email is already registered.")],
            ]);
        }
        
        $user = User::where("email", $request->email)->where("activated", 0)->where("owner", 1)->first();
        if(!$user)
        {
            $user = new User;
            $user->email = $request->email;
            $user->password = "";
            $user->activated = 0;
            $user->owner = 1;
            $user->superuser = 1;
            $user->save();
        }
        
        $token = $user->generateRegisterToken();
        $user->sendInitMessage($token);
        
        return true;
    }
    
    /**
    * Validate register token
    *
    * Check validate register token from confirmation message.
    * @urlParam token string required Register token.
    * @response 200 {'email': 'john@doe.com'}
    * @response 422 {"error":true,"message":"The provided email is already registered.","errors":{"email":["The provided email is already registered."]}}
    * @group User registation
    */
    public function get(Request $request, $token)
    {
        $userRegisterToken = UserRegisterToken::where("token", $token)->first();
        if(!$userRegisterToken)
        {
            throw ValidationException::withMessages([
                "email" => [__("Invalid register token.")],
            ]);
        }
        
        $user = User::select("email")->find($userRegisterToken->user_id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist."));
        
        return $user;
    }
    
    /**
    * Confirm register
    *
    * Confirm register and finish registration.
    * @urlParam token string required Register token.
    * @bodyParam firstname string required User first name.
    * @bodyParam lastname string required User last name.
    * @bodyParam password string required User password (min 8 characters, lowercase and uppercase letters, number, special characters).
    * @bodyParam firm_identifier string required User firm name.
    * @bodyParam phone string required User phone number.
    * @responseField status boolean Status
    * @response 422 {"error":true,"message":"Invalid register token.","errors":{"token":["Invalid register token."]}}
    * @group User registation
    */
    public function confirm(Request $request, $token)
    {
        $userRegisterToken = UserRegisterToken::where("token", $token)->first();
        if(!$userRegisterToken)
        {
            throw ValidationException::withMessages([
                "token" => [__("Invalid register token.")],
            ]);
        }
        
        $user = User::find($userRegisterToken->user_id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist."));
        
        $request->validate([
            "firstname" => "required|max:100",
            "lastname" => "required|max:100",
            "phone" => "required|max:30",
            "password" => ["required", Password::min(8)->letters()->mixedCase()->numbers()->symbols(), "confirmed"],
            "firm_identifier" => "required|max:200"
        ]);
        
        DB::transaction(function () use($user, $request) {
            $user->firstname = $request->input("firstname");
            $user->lastname = $request->input("lastname");
            $user->phone = $request->input("phone");
            $user->password = Hash::make($request->input("password"));
            $user->confirm();
            
            $user->ensureFirm($request->input("firm_identifier"));
            $user->prepareAccount();
        });
        
        return true;
    }
}