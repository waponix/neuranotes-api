<?php

use App\Http\Controllers\AssistantController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
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
        Route::post('/refresh', 'refresh');

        Route::middleware('auth:api')->group(function () {
            Route::get('/profile', 'profile');
        });

    });
    
});

Route::controller(NoteController::class)->group(function () {

    Route::middleware('auth:api')->group(function () {
        Route::get('/notes', 'list');
        Route::get('/notes/{noteId}', 'get');
        Route::post('/notes', 'create');
        Route::put('/notes/{noteId}', 'update');
        Route::delete('/notes/{noteId}', 'delete');
        Route::post('/notes/{noteId}/pin', 'pin');
        Route::post('/notes/{noteId}/unpin', 'unpin');
        Route::post('/notes/{noteId}/star', 'star');
        Route::post('/notes/{noteId}/unstar', 'unstar');
    });

});

Route::controller(AssistantController::class)->group(function () {
    
    Route::middleware('auth:api')->group(function () {
        Route::post('/assistant/notes/query', 'generalNotesQuery');
    });

});

Route::fallback(function () {
    return response()->json([
        'error' => 'resource not found'
    ], 404);
});