<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use DB;
use App\Models\User;
use Validator;

class AuthenticationController extends Controller
{
    private $apiToken;

    public function __construct()
    {
        $this->apiToken = uniqid(base64_encode(Str::random(60)));
    }
  /**
   * Client Login
   */
    public function postLogin(Request $request)
    {
        // Validations
        $rules = [
            'email'=>'required|email',
            'password'=>'required|min:6'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            // Validation failed
            return response()->json([
                'message' => $validator->messages(),
            ]);
        } else {
            $user = User::where('email',$request->email)->first();
            if($user) {
            // Verify the password
                if( password_verify($request->password, $user->password) ) 
                {
                    // Update Token
                    $postArray = ['api_token' => $this->apiToken];
                    $login = User::where('email',$request->email)->update($postArray);
                    if($login) 
                    {
                        return response()->json([
                            'name'         => $user->name,
                            'email'        => $user->email,
                            'access_token' => $this->apiToken,
                        ]);
                    }

                } else {
                    return response()->json([
                        'message' => 'Invalid Password',
                    ]);
                }
            } else {
                return response()->json([
                    'message' => 'User not found',
                ]);
            }
        }
    }

  /**
   * Register
   */
    public function postRegister(Request $request)
    {
        // Validations
        $rules = [
            'name'     => 'required|min:3',
            'email'    => 'required|unique:users,email',
            'password' => 'required|min:8'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            // Validation failed
            return response()->json([
                'message' => $validator->messages(),
            ]);
        } else {
            $postArray = [
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => bcrypt($request->password),
                'api_token' => $this->apiToken
            ];
            $user = User::insert($postArray);
            if($user) 
            {
                return response()->json([
                    'name'         => $request->name,
                    'email'        => $request->email,
                    'access_token' => $this->apiToken,
                ]);
            } else {
                return response()->json([
                    'message' => 'Registration failed, please try again.',
                ]);
            }

        }
    }

  /**
   * Logout
   */
    public function postLogout(Request $request)
    {
        $token = $request->header('Authorization');
        $user = User::where('api_token',$token)->first();
        if($user) 
        {
            $postArray = ['api_token' => null];
            $logout = User::where('id',$user->id)->update($postArray);
            if($logout) 
            {
                return response()->json([
                    'message' => 'User Logged Out',
                ]);
            }
        } else {
            return response()->json([
                'message' => 'User not found',
            ]);
        }
    }
}