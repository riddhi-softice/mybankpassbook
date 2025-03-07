<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

Route::get('test', function () {
    return 'API is working';
});


Route::post('user_login', [ApiController::class, 'user_login']);


Route::group(['middleware' => ['throttle:60,1'], 'as' => 'api.'], function () {
// Route::middleware('custome.api')->group( function () {

    Route::post('get_article', [ApiController::class, 'get_article']);
    Route::post('get_common_setting', [ApiController::class, 'get_common_setting']);
// });
});

Route::get('noti_test', [ApiController::class, 'noti_test']);


Route::post('verify_purchase', [ApiController::class, 'validateInAppPurchase']);
