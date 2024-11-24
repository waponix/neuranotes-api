<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('user/init', function (Request $request) {
    $user = new User;
    $user->password = Hash::make('@dminUser123');
    $user->email = 'eric.bermejo.reyes@gmail.com';
    $user->name = 'Eric Reyes';
    $user->save();

    return response()->json([
        'status' => 'ok',
        'user' => $user,
    ], 200);
});
