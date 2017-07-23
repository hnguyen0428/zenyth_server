<?php

namespace App\Http\Middleware;

use App;
use App\Exceptions\Exceptions;
use App\Exceptions\ResponseHandler as Response;
use App\Http\Controllers\Auth\AuthenticationTrait;
use App\Http\Requests\DataValidator;
use Closure;
use Illuminate\Http\Request;

class OauthMiddleware {
    use AuthenticationTrait;

    public function handle(Request $request, Closure $next)
    {

        $validator = DataValidator::validateOauthLogin($request);
        if($validator->fails())
            return Response::validatorErrorResponse($validator);

        $token = $request->header('Authorization');
        if($token != null) {
            $json = $this->oauthValidate($request);

            if($json == null)
                return Response::errorResponse(Exceptions::unauthenticatedException());

            // If we don't have access to email
            if(!isset($json['email']))
                return Response::dataResponse(true, [
                    'email_access' => false
                ], 'no access to email');

            // If token is invalid or if email sent in the request is not the
            // same as one found from the token
            else if(isset($json['error']) || isset($json['error_description'])
                || $request['email'] != $json['email']) {
                return Response::errorResponse(Exceptions::unauthenticatedException());
            }
            else {
                $request->merge(['json' => $json]);
                return $next($request);
            }

        } else {
            return Response::errorResponse(Exceptions::unauthenticatedException());
        }

    }

}