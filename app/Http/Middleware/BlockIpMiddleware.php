<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use DB;

class BlockIpMiddleware
{
    // public $blockIps = ['122.182.131.212'];  # on a LAN or Wi-Fi connection - ipv4

    public function handle(Request $request, Closure $next)
    {
        $ips = DB::table('common_settings')->where('setting_key','admin_ip')->pluck('setting_value')->first();
        $ip_array = explode(',',$ips);

        if (!in_array($request->ip(), $ip_array)) {
            abort(403, "You are restricted to access the site.");
        }
        return $next($request);
    }

    /*public function handle(Request $request, Closure $next)
    {
        // dd($request->ip());

        if (!in_array($request->ip(), $this->blockIps)) {
            abort(403, "You are restricted to access the site.");
        }
        return $next($request);
    }*/
}
