<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\NoteController;

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
        Route::get('/refresh', 'refresh');

        Route::middleware('auth:api')->group(function () {
            Route::get('/profile', 'profile');
        });

    });
    
});

Route::controller(NoteController::class)->group(function () {
       
    Route::middleware('auth:api')->group(function () {
        Route::get('/note/{noteId}', 'get');
        Route::post('/note', 'create');
        Route::put('/note/{noteId}', 'update');
        Route::delete('/note/{noteId}', 'delete');
        Route::post('/note/{noteId}/pin', 'pin');
        Route::post('/note/{noteId}/unpin', 'unpin');
        Route::post('/note/{noteId}/star', 'star');
        Route::post('/note/{noteId}/unstar', 'unstar');
    });

});

Route::controller(ChatController::class)->group(function () {
       
    Route::middleware('auth:api')->group(function () {
        Route::post('/chat', 'chat');
    });

});

Route::fallback(function () {
    return response()->json([
        'error' => 'resource not found'
    ], 404);
});