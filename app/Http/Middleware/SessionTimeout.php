<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SessionTimeout
{
    public function handle($request, Closure $next)
    {
        $timeout = config('session.lifetime') * 60; // session lifetime in seconds
        $lastActivity = session('lastActivityTime');
        if (is_null($lastActivity)) {
            $lastActivity = time();
            session(['lastActivityTime' => $lastActivity]);
        }
        // dd(time(), $lastActivity,$timeout);
        if (time() - $lastActivity > $timeout) {
            // Auth::logout();
            $request->session()->flush();
            $request->session()->regenerate();
            return redirect('/login')->with('error', 'Your session has expired due to inactivity.');
        }
        session(['lastActivityTime' => time()]);
        return $next($request);
    }
}
