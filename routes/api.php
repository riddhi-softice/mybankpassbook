<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

Route::get('test', function () {
    return 'API is working';
});

Route::post('user_login', [ApiController::class, 'user_login']);
Route::post('verify_purchase', [ApiController::class, 'validateInAppPurchase']);


Route::group(['middleware' => ['throttle:60,1'], 'as' => 'api.'], function () {
// Route::middleware('custome.api')->group( function () {

    Route::post('get_article', [ApiController::class, 'get_article']);
    Route::post('get_common_setting', [ApiController::class, 'get_common_setting']);
    Route::post('get_bank_holiday', [ApiController::class, 'get_bank_holiday']);
    Route::post('get_state', [ApiController::class, 'get_state']);
// });
});

Route::get('BankHolidayStore', [ApiController::class, 'BankHolidayStore']);
Route::get('BankHolidayStoreState', [ApiController::class, 'BankHolidayStoreState']);
Route::get('holidays', [ApiController::class, 'holidays']);
Route::get('holiday_notification', [ApiController::class, 'holiday_notification']);

Route::get('noti_test', [ApiController::class, 'noti_test']);

