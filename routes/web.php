<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\CommonSettingController;
use App\Http\Controllers\TwoFactorAuthController;


Route::middleware(['blockIP'])->group(function () {
    Route::get('test', function () {
        return request()->ip();
    });
});


Route::get('cache_clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    Artisan::call('route:clear');
    echo "cache cleared..";
});

Route::middleware(['blockIP'])->group(function () {

    Route::group(['middleware' => ['admin']], function() {
        Route::get('2fa/setup', [TwoFactorAuthController::class, 'show2faForm'])->name('2fa.form');
        Route::post('2fa/setup', [TwoFactorAuthController::class, 'setup2fa'])->name('2fa.setup');
        Route::get('2fa/verify', [TwoFactorAuthController::class, 'showVerifyForm'])->name('2fa.verifyForm');
        Route::post('2fa/verify', [TwoFactorAuthController::class, 'verify2fa'])->name('2fa.verify');
    });


    /* ------ Authentication --------  */
    Route::get('login', [AuthController::class, 'index'])->name('login');
    Route::post('post-login', [AuthController::class, 'postLogin'])->name('login.post');

    Route::middleware(['2fa','session.timeout','admin'])->group(function () {

        Route::get('dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
        Route::get('account_setting', [AuthController::class, 'account_setting'])->name('account_setting');
        Route::post('account_setting_change', [AuthController::class, 'account_setting_change'])->name('post.account_setting');
        Route::get('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('get_setting', [CommonSettingController::class, 'get_setting'])->name('get_setting');
        Route::post('change_setting', [CommonSettingController::class, 'change_setting'])->name('change_setting');
    });
});


Route::get('/', function () {
    return view('landing_page');
});