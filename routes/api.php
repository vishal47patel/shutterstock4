<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\UserController;    

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::controller(RegisterController::class)->group(function(){
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('forgot-password', 'forgotPassword');
    Route::post('reset-password', 'resetPassword')->name('password.reset');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(UserController::class)->group(function () {
        Route::post('update-profile', 'updateProfile');
        Route::post('change-password', 'changePassword');
        Route::delete('user/delete', 'deleteUser');
        Route::post('user/restore', 'restoreUser');
        Route::delete('user/force-delete', 'forceDeleteUser');
        Route::get('users', 'userList');
        Route::get('/user-stats','userStats'); 
        Route::patch('user/status', 'updateStatus');      
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
