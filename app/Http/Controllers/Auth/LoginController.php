<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Handle an authentication attempt.
     *
     * @return Response
     */
    public function login(Request $request)
    {
        $validator = $this->validate($request, [
            'email' => 'required',
            'password' => 'required'
        ]);

        if($validator->fails()) {
            return $validator->errors()->all();
        }

        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::where('email', $email)->first();

        if($user == null){
          return 0;
        }

        if(Hash::check($password, $user->password)){   // checks password
                                                       // against hashed pw

            // Authentication passed...
            do{

                $api_token = str_random(60);

                $dup_token_user = User::where('api_token', $api_token)
                    ->first();

            } while( $dup_token_user != null );

            //found unique api token

            $user->api_token = $api_token;
            $user->update();

            return json_encode(['api_token' => $api_token]);

        }

            return 0;

        }

}
