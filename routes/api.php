<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\PostController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// account
Route::post('/register', [AccountController::class, 'register']);
Route::post('/login', [AccountController::class, 'authenticate']);
Route::post('/account/update_password', [AccountController::class, 'updatePassword']);
Route::post('/logout', [AccountController::class, 'logout']);

// post
Route::post('/store', [PostController::class, 'store']);
Route::post('/update', [PostController::class, 'update']);
Route::post('/destroy', [PostController::class, 'destroy']);
Route::get('/get', [PostController::class, 'get']);
Route::get('/paginate', [PostController::class, 'paginate']);
Route::get('/get/random', [PostController::class, 'getRandomPost']);