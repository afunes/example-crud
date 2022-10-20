<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            if ($e instanceof TokenInvalidException) return response()->json(['isError' => true, 'message' => 'Token is Invalid']);
            if ($e instanceof TokenExpiredException) return response()->json(['isError' => true, 'message' => 'Token is Expired']);

            return response()->json(['isError' => true, 'message' => 'Authorization Token not found']);
        }
        return $next($request);
    }
}
