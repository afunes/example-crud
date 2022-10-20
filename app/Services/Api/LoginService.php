<?php

namespace App\Services\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use Carbon\Carbon;

class LoginService
{

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email'     => 'required|email',
            'password'  => 'required|string',
        ]);

        if ($validator->fails()) return response()->json(['isError' => true, 'errors' => $validator->messages()], 406);


        try {
            $user = User::where('email', $request->email)->first();
            if (empty($user)) return response()->json(['isError' => true, 'message' => "Registro no encontrado"], 406);


            if (!($token = JWTAuth::attempt($credentials))) {
                return response()->json(['isError' => false, 'message' => 'Login credentials are invalid.'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['isError' => true, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['isError' => false, 'access_token' => $token, 'token_type' => 'bearer']);
    }

    public function logout(Request $request) {
        //valid credential
        $validator = Validator::make($request->only('token'), [
            'token' => 'required',
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 406);
        }

        //Request is validated, do logout
        try {
            JWTAuth::invalidate($request->token);

            return response()->json([
                'success' => true,
                'message' => 'User has been logged out',
            ]);
        } catch (JWTException $exception) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Sorry, user cannot be logged out',
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

}
