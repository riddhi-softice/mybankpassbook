<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use Illuminate\Support\Facades\Session;


class TwoFactorAuth
{
    public function handle($request, Closure $next)
    {
        if (Session::has('admin_id') && !Session::has('2fa_verified')) {
            return redirect()->route('2fa.verifyForm');
        }
        return $next($request);
    }
}
