<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use DB;

class CustomeApiMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token =  $request->header('UserToken');
        $auth = DB::table('users')->where(['remember_token'=> $token])->exists();
        if (!$auth) {
            return response()->json(['success' => false, 'message' => 'Authentication fail'], 401);
        }

        return $next($request);
    }
}
