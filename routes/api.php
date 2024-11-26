<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function () {
    
    Route::controller(AuthController::class)->group(function () {
        
        Route::post('/login', 'login');
        Route::post('/register', 'register');

        Route::middleware('auth:api')->group(function () {
            Route::get('/refresh', 'refresh');
            Route::get('/profile', 'profile');
        });

    });
    
});

Route::fallback(function () {
    return response()->json([
        'error' => 'resource not found'
    ], 404);
});