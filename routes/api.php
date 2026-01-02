<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');



Route::prefix('v1')->group(function () {

    #auth routes
    Route::post('auth/otp/request', [AuthController::class, 'loginWithOtp'])->name('auth.otp.request');
    Route::post('auth/otp/verify', [AuthController::class, 'loginWithOtp'])->name('auth.otp.verify');

    #user  -  get and update profile
    Route::middleware(['token.exists', 'auth:sanctum'])->group(function () {
        Route::get('/user/profile', [UserController::class, 'getProfile']);
        Route::post('/user/profile', [UserController::class, 'updateProfile']);
    });


});